<?php
namespace GDO\DB;

use GDO\Core\GDO;
use GDO\Core\GDT_Hook;
use GDO\File\FileUtil;
use GDO\Core\Module_Core;

/**
 * Cache is a global object cache, where each fetched object (with the same key) from the database results in the same instance.
 * This way you can never have two dangling out of sync users in your application.
 * It also saves a bit mem.
 * Of course this comes with a slight overhead.
 * As GDO6 was written from scratch with this in mind, the overhead is quite small.
 * 
 * Suprising is the additional use of memcached (did not plan this) which adds a second layer of caching.
 * 
 * There are a few global memcached keys scattered across the application, fetching all rows or similiar stuff.
 * Those GDOs usually dont use memcached on a per row basis and gdoMemcached is false.
 * 
 * gdo_modules
 * gdo_country
 * gdo_language
 * 
 * The other memcached keys work on a per row basis with table_name_id as key.
 * 
 * @author gizmore
 * @version 6.10.3
 * @since 5.0.0
 * @license MIT
 */
class Cache
{
	private static $MEMCACHED; # Memcached server
	
	/**
	 * @var GDO[]
	 */
	private static $RECACHING = [];

	public $pkNames;   # Primary Key Column Names
    public $pkColumns; # Primary Key Columns
    public $tableName; # Cached transformed table name

	#################
	### Memcached ###
	#################
    /**
     * @var $all GDO[] All rows. @see GDO->allCached()
     */
    public $all;       # 
    public $allExpire; # Expiration time for allCached()
    
    /**
	 * @TODO no result should return null?
	 * @param string $key
	 * @return boolean
	 */
    public static function get($key) { return GDO_MEMCACHE ? self::$MEMCACHED->get(MEMCACHEPREFIX.$key) : false; }
	public static function set($key, $value, $expire=null) { if (GDO_MEMCACHE) self::$MEMCACHED->set(MEMCACHEPREFIX.$key, $value, $expire); }
	public static function remove($key) { if (GDO_MEMCACHE) self::$MEMCACHED->delete(MEMCACHEPREFIX.$key); }
	public static function flush() { if (GDO_MEMCACHE) self::$MEMCACHED->flush(); }
	public static function init()
	{
		if (GDO_MEMCACHE)
		{
			self::$MEMCACHED = new \Memcached();
			self::$MEMCACHED->addServer(GDO_MEMCACHE_HOST, GDO_MEMCACHE_PORT);
		}
	}
	
	#################
	### GDO Cache ###
	#################
	/**
	 * The table object is fine to keep clean?
	 * @var GDO
	 */
	private $table;
	
	/**
	 * @var GDO
	 */
	private $dummy;
	
	/**
	 * Full classname
	 * @var string
	 */
	private $klass;
	
	/**
	 * The single identity GDO cache
	 * @var GDO[]
	 */
	public $cache = [];

	public function __construct(GDO $gdo)
	{
		$this->table = $gdo;
		$this->klass = $gdo->gdoClassName();
		$this->tableName = strtolower($gdo->gdoShortName());
	}
	
	public static function recacheHooks()
	{
        if (GDO_IPC || (GDO_IPC === 'db'))
        {
            foreach (self::$RECACHING as $gdo)
            {
                GDT_Hook::callWithIPC('CacheInvalidate', $gdo->table()->cache->klass, $gdo->getID());
            }
        }
	}

	public function getDummy()
	{
	    return $this->dummy ? $this->dummy : $this->newDummy();
	}
	
	private function newDummy()
	{
		$this->dummy = new $this->klass();
		return $this->dummy;
	}
	
	/**
	 * Try GDO Cache and Memcached.
	 * @param string $id
	 * @return GDO
	 */
	public function findCached(...$ids)
	{
		$id = implode(':', $ids);
		if (!isset($this->cache[$id]))
		{
			if ($mcached = self::get($this->tableName . $id))
			{
				$this->cache[$id] = $mcached;
			}
			else
			{
			    return false;
			}
		}
		return $this->cache[$id];
	}
	
	public function hasID($id)
	{
		return isset($this->cache[$id]);
	}
	
	/**
	 * Only GDO Cache / No memcached initializer.
	 * @param array $assoc
	 * @return GDO
	 */
	public function initCached(array $assoc)
	{
		$this->getDummy()->setGDOVars($assoc);
		$key = $this->dummy->getID();
		if (!isset($this->cache[$key]))
		{
			$this->cache[$key] = $this->dummy->setPersisted();
			$this->newDummy();
		}
		else
		{
			$this->cache[$key]->setGDOVars($assoc);
		}
		return $this->cache[$key];
	}
	
	public function clearCache()
	{
	    $this->all = null;
	    $this->cache = [];
	}
	
	public function recache(GDO $object)
	{
		$back = $object;
		
		# GDO cache
		if ($back->gdoCached())
		{
    		$id = $object->getID();

    		# GDO single cache
			if (isset($this->cache[$id]))
			{
				$old = $this->cache[$id];
				$old->setGDOVars($object->getGDOVars());
				$back = $old;
			}
			else
			{
				$this->cache[$id] = $back;
			}
		}
		
		# Memcached
		if (GDO_MEMCACHE && $back->memCached())
		{
		    self::$MEMCACHED->replace(MEMCACHEPREFIX.$back->gkey(), $back, GDO_MEMCACHE_TTL);
		}

	    # Mark for recache
	    if ($back->recache === false)
	    {
	        self::$RECACHING[] = $back->recaching();
	    }
		
		return $back;
	}
	
	public function uncache(GDO $object)
	{
	    # Mark for recache
	    if ($object->recache === false)
	    {
	        self::$RECACHING[] = $object->recaching();
	    }
	    
	    $id = $object->getID();
	    unset($this->cache[$id]);

		if (GDO_MEMCACHE && $object->memCached())
		{
    		$className = $object->gdoClassName();
    		self::$MEMCACHED->delete(MEMCACHEPREFIX . $className . $id);
		}
	}
	
	/**
	 * memcached + gdo cache initializer
	 * @param array $assoc
	 * @return GDO
	 */
	public function initGDOMemcached(array $assoc)
	{
		$this->getDummy()->setGDOVars($assoc);
		$key = $this->dummy->getID();
		if (!isset($this->cache[$key]))
		{
			$gkey = $this->dummy->gkey();
			if (false === ($mcached = self::get(MEMCACHEPREFIX.$gkey)))
			{
				$mcached = $this->dummy->setPersisted();
				if (GDO_MEMCACHE)
				{
					self::$MEMCACHED->set(MEMCACHEPREFIX.$gkey, $mcached, GDO_MEMCACHE_TTL);
				}
    			$this->newDummy();
			}
			$this->cache[$key] = $mcached;
		}
		else
		{
			$this->cache[$key]->setGDOVars($assoc)->setPersisted();
		}
		return $this->cache[$key];
	}
	
	##################
	### File cache ###
	##################
	/**
	 * Put cached content on the file system.
	 * @param string $key
	 * @param string $content
	 * @return boolean
	 */
	public static function fileSet($key, $content, $expire=GDO_MEMCACHE_TTL)
	{
	    $path = self::filePath($key);
	    return @file_put_contents($path, $content);
	}
	
	/**
	 * Get cached content from the file system.
	 * @param string $key
	 * @return string
	 */
	public static function fileGet($key)
	{
	    $path = self::filePath($key);
	    return @file_get_contents($path);
	}
	
	public static function fileFlush($key=null)
	{
	    if ($key === null)
	    {
	        FileUtil::removeDir(GDO_PATH . 'temp/cache/');
	    }
	    else
	    {
	        return @unlink(self::filePath($key));
	    }
	}
	
	private static function filePath($key)
	{
	    return GDO_PATH . "temp/cache/{$key}";
	}
	
}

# No memcached stub shim so it won't crash.
if (!class_exists('Memcached', false))
{
	require 'Memcached.php';
}
define('MEMCACHEPREFIX', GDO_DOMAIN.Module_Core::$GDO_REVISION);
