<?php
namespace GDO\Core;

use GDO\UI\GDT_Panel;

/**
 * An error is a panel that additionally logs the given message.
 * @author gizmore
 * @version 6.10
 * @since 6.00
 */
class GDT_Error extends GDT_Panel
{
    public static $ERROR = 0;
    protected function __construct()
    {
        if (!self::$ERROR)
        {
            $this->name = 'error';
        }
        else
        {
            $this->name = 'error' . (++self::$ERROR);
        }
    }
    
	public function hasError() { return true; }

	public static function responseException(\Throwable $e)
	{
		Logger::logException($e);
		$html = Debug::backtraceException($e, Application::instance()->isHTML(), $e->getMessage());
		return self::responseWith('err_exception', [$html], 500, false);
	}
	
	public static function responseWith($key, array $args=null, $code=405, $log=true)
	{
		return GDT_Response::makeWith(self::with($key, $args, $code, $log))->code($code);
	}
	
	public static function with($key, array $args=null, $code=405, $log=true)
	{
	    if ($code > 200)
	    {
	        http_response_code($code);
	    }
		
		if ($log)
		{
			Logger::logError(tiso('en', $key, $args));
		}
		
		return self::make()->text($key, $args);
	}
	
	##############
	### Render ###
	##############
	public function renderCell() { return GDT_Template::php('Core', 'cell/error.php', ['field' => $this]); }
	
	public function renderJSON()
	{
	    return $this->renderText();
	}

}
