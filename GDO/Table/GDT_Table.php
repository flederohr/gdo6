<?php
namespace GDO\Table;

use GDO\Util\Common;
use GDO\DB\ArrayResult;
use GDO\Core\GDO;
use GDO\DB\Query;
use GDO\DB\Result;
use GDO\Core\GDT;
use GDO\Core\GDT_Template;
use GDO\UI\GDT_SearchField;
use GDO\UI\WithHREF;
use GDO\UI\WithTitle;
use GDO\UI\WithActions;
use GDO\Core\WithFields;
use GDO\Util\Classes;
use GDO\Core\GDOException;
use GDO\Core\Logger;
use GDO\Core\Debug;

/**
 * A filterable, searchable, orderable, paginatable, sortable collection of GDT[] in headers.
 * 
 * WithHeaders GDT control provide the filtered, searched, ordered, paginated and sorted.
 * GDT_Pagemenu is used for paginatable.
 * 
 * Supports queried Result and ArrayResult.
 * 
 * Searched can crawl multiple fields at once via huge query.
 * Filtered can crawl on individual fields.
 * Ordered enables ordering by fields.
 * Paginated enables pagination via GDT_Pagemenu.
 * Sorted enables drag and drop sorting via GDT_Sort and Table::Method::Sorting.
 * 
 * The GDT that are used for this are stored via 'WithHeaders' trait.
 * The Header has a name that is used in $REQUEST vars.
 * $_REQUEST[$headerName][f] for filtering
 * $_REQUEST[$headerName][o][field]=1|0 for multordering in tables
 * $_REQUEST[$headerName][order_by] for single ordering in lists 
 * $_REQUEST[$headerName][search] for searching
 * $_REQUEST[$headerName][page] for pagenum
 * $_REQUEST[$headerName][ipp] for items per page
 * $_REQUEST[$headerName][s][ID]=[ID] for sorting (planned)
 * 
 * @author gizmore
 * 
 * @version 6.10.3
 * @since 6.0.0
 * 
 * @see GDO
 * @see GDT
 * @see GDT_PageMenu
 * @see Result
 * @see ArrayResult
 * @see MethodQueryTable
 */
class GDT_Table extends GDT
{
	use WithHREF;
	use WithTitle;
	use WithHeaders;
	use WithActions;
	use WithFields;
	
	public function defaultName() { 'table'; }

	###########
	### GDT ###
	###########
	protected function __construct()
	{
	    parent::__construct();
	    $this->action = @$_SERVER['REQUEST_URI'];
	    $this->makeHeaders();
	}
	
	#####################
	### Header fields ###
	#####################
	public function getHeaderFields()
	{
	    return $this->headers ? $this->headers->getFields() : [];
	}
	
	public function getHeaderField($name)
	{
	    return $this->headers->getField($name);
	}
	
	################
	### Endpoint ###
	################
	public $action;
	public function action($action=null) { $this->action = $action; return $this; }

	##############
	### Footer ###
	##############
	public $footer;
	public function footer($footer) { $this->footer = $footer; return $this; }
	
	##################
	### Hide empty ###
	##################
	public $hideEmpty = false;
	public function hideEmpty($hideEmpty=true)
	{
	    $this->hideEmpty = $hideEmpty;
	    return $this;
	}
	
	####################### 
	### Default headers ###
	#######################
	public function setupHeaders($searched=false, $paginated=false, $ordered=false, $filtered=false, $sorted=false)
	{
	    if ($searched)
	    {
	        $this->addHeader(GDT_SearchField::make('search'));
	    }
	    
	    if ($paginated)
	    {
	        $this->addHeader(GDT_PageNum::make('page'));
	        $this->addHeader(GDT_IPP::make('ipp'));
	    }
	}
	
	######################
	### Drag&Drop sort ###
	######################
	public $sorted;
	private $sortableURL;
	public function sorted($sortableURL=null)
	{
		$this->sorted = $sortableURL !== null;
		$this->sortableURL = $sortableURL;
		return $this;
	}
	
	#################
	### Searching ###
	#################
	public $searched;
	public function searched($searched=true) { $this->searched =$searched; return $this; }
	
	#################
	### Filtering ###
	#################
	public $filtered;
	public function filtered($filtered=true) { $this->filtered = $filtered; return $this; }
	
	################
	### Ordering ###
	################
	public $ordered;
	public $orderDefault;
	public $orderDefaultAsc;
	public function ordered($ordered=true, $defaultOrder=null, $defaultAsc=true)
	{
		$this->ordered = $ordered;
		$this->orderDefault = $defaultOrder;
		$this->orderDefaultAsc = $defaultAsc;
		return $this;
	}
	
	##################
	### Pagination ###
	##################
	/** @var $pagemnu GDT_PageMenu **/
	public $pagemenu;
	public function paginateDefault($href=null)
	{
		return $this->paginated(true, $href, Module_Table::instance()->cfgItemsPerPage());
	}

	public function paginated($paginated=true, $href=null, $ipp=0)
	{
		$ipp = $ipp <= 1 ? Module_Table::instance()->cfgItemsPerPage() : (int)$ipp;
		if ($paginated)
		{
		    $href = $href === null ? $this->action : $href;
			$this->pagemenu = GDT_PageMenu::make('page');
			$this->pagemenu->headers($this->headers);
			$this->pagemenu->href($href);
			$this->pagemenu->ipp($ipp);
			$o = $this->headers->name;
			$this->pagemenu->page($this->headers->getField('page')->getRequestVar("$o", '1', 'page'));
			$this->href($href);
		}
		return $this->ipp($ipp);
	}

	####################
	### ItemsPerPage ###
	####################
	private $ipp = 10;
	public function ipp($ipp)
	{
		$this->ipp = $ipp;
		return $this;
	}
	
	###
	public $result;
	public function result(Result $result)
	{
		if (!$this->fetchAs)
		{
			$this->fetchAs = $result->table;
		}
		$this->result = $result;
		return $this;
	}
	
	/**
	 * @return Result
	 */
	public function getResult()
	{
		if (!$this->result)
		{
			if (!($this->result = $this->queryResult()))
			{
				$this->result = new ArrayResult([], $this->gdtTable);
			}
		}
		return $this->result;
	}
		
	public $query;
	public function query(Query $query)
	{
		if (!$this->fetchAs)
		{
			$this->fetchAs = $query->table;
		}
		$this->query = $this->getFilteredQuery($query);
		return $this;
	}
	
	public $countQuery;
	public function countQuery(Query $query)
	{
	    $this->countQuery = $this->getFilteredQuery($query->copy());
// 	    $tablequery = $this->getFilteredQuery($query)->noOrder()->buildQuery();
// 	    $this->countQuery = (new Query($query->table))->select('COUNT(*)')->from(" ( $tablequery ) querytable");
	    return $this;
	}
	
	private $filtersApplied = false;
	public function getFilteredQuery(Query $query)
	{
		if ($this->filtered)
		{
		    $rq = $this->headers->name;
		    foreach ($this->getHeaderFields() as $gdoType)
		    {
		        if ($gdoType->filterable)
		        {
		            $gdoType->filterQuery($query, $rq);
		        }
		    }
		}
		
		if ($this->searched)
		{
		    $s = $this->headers->name;
		    if (isset($_REQUEST[$s]['search']))
		    {
		        if ($searchTerm = trim($_REQUEST[$s]['search'], "\r\n\t "))
		        {
		            $this->bigSearchQuery($query, $searchTerm);
		        }
		    }
		}
		
		return $this->getOrderedQuery($query);
	}
	
	private function getOrderedQuery(Query $query)
	{
	    $headers = $this->headers;
	    $o = $headers ? $headers->name : 'o';
	    
	    $hasCustomOrder = false;
	    
	    if ($this->ordered)
	    {
	        # Convert single to multiple fake
	        if (isset($_REQUEST[$o]['order_by']))
	        {
	            unset($_REQUEST[$o]['o']);
	            $by = $_REQUEST[$o]['order_by'];
	            $_REQUEST[$o]['o'][$by] = $_REQUEST[$o]['order_dir'] === 'ASC';
// 	            unset($_REQUEST[$o]['order_by']);
// 	            unset($_REQUEST[$o]['order_dir']);
	        }
	        
	        if ($this->headers)
	        {
	            if ($cols = Common::getRequestArray($o))
	            {
	                if ($cols = @$cols['o'])
	                {
	                    $o = '1';
	                    foreach ($cols as $name => $asc)
	                    {
	                        if ($field = $headers->getField($name))
	                        {
	                            if ($field->orderable)
	                            {
	                                if ( (Classes::class_uses_trait($field, 'GDO\\DB\\WithObject')) &&
	                                    ($field->orderFieldName() !== $field->name) )
	                                {
	                                    $query->joinObject($field->name, 'JOIN', "o{$o}");
	                                    $query->order("o{$o}.".$field->orderFieldName(), !!$asc);
	                                }
	                                else
	                                {
	                                    $query->order($field->orderFieldName(), !!$asc);
	                                }
	                                $hasCustomOrder = true;
	                            }
	                        }
	                    }
	                }
	            }
	        }
	    }
	    
	    if (!$hasCustomOrder)
	    {
	        if ($this->orderDefault)
	        {
	            $query->order($this->orderDefault, $this->orderDefaultAsc);
	        }
	    }
	    
	    return $query;
	}
	
	/**
	 * Build a huge where clause for quicksearch.
	 * Supports multiple terms at once, split via whitespaces.
	 * Objects that are searchable JOIN automatically and offer more searchable fields.
	 * In general, GDT_String and GDT_Int is searchable.
	 * 
	 * @TODO GDT_Enum is not searchable yet.
	 * 
	 * @param Query $query
	 * @param string $searchTerm
	 */
	public function bigSearchQuery(Query $query, $searchTerm)
	{
	    $split = preg_split("/\\s+/iD", trim($searchTerm, "\t\r\n "));
        $first = true;
	    foreach ($split as $searchTerm)
	    {
    	    $where = [];
    	    foreach ($this->getHeaderFields() as $gdt)
    	    {
    	        if ($gdt->searchable)
    	        {
    	            if ($condition = $gdt->searchQuery($query, $searchTerm, $first))
    	            {
    	                $where[] = $condition;
    	            }
    	        }
    	    }
    	    if ($where)
    	    {
    	        $query->where(implode(' OR ', $where));
    	    }
    	    $first = false;
	    }
	}
	
	/**
	 * @var int
	 */
	public $countItems = null;
	/**
	 * @return int the total number of matching rows. 
	 */
	public function countItems()
	{
		if ($this->countItems === null)
		{
		    if ($this->countQuery)
		    {
		        $this->countItems = $this->countQuery->selectOnly('COUNT(*)')->noOrder()->noLimit()->exec()->fetchValue();
		    }
		    else
		    {
		        $this->countItems = $this->getResult()->numRows();
		    }
		}
		return $this->countItems;
	}
	
	/**
	 * Query the final result.
	 * @return \GDO\DB\Result
	 */
	public function queryResult()
	{
	    if (!$this->query)
	    {
	        Logger::logDebug(Debug::backtrace('QU', false));
	    }
		return $this->query->exec();
	}
	
	/**
	 * @return GDT_PageMenu
	 */
	public function getPageMenu()
	{
		if ($this->pagemenu)
		{
		    if ($this->query)
		    {
		        if ($this->countItems === null)
		        {
        			$this->pagemenu->items($this->countItems());
		        }
		    }
		}
		return $this->pagemenu;
	}
	
	public $fetchAs;
	public function fetchAs(GDO $fetchAs=null)
	{
		$this->fetchAs = $fetchAs;
		return $this;
	}
	
	##############
	### Render ###
	##############
	public function renderCell()
	{
		if ( ($this->hideEmpty) && ($this->getResult()->numRows() === 0) )
		{
			return '';
		}
		return GDT_Template::php('Table', 'cell/table.php', ['field'=>$this, 'form' => false]);
	}
	
	public function renderForm()
	{
		return GDT_Template::php('Table', 'cell/table.php', ['field'=>$this, 'form' => true]);
	}
	
	public function renderCard()
	{
		return $this->renderCell();
	}
	
	public function renderJSON()
	{
		return array_merge($this->configJSON(), [
		    'data'=>$this->renderJSONData(),
		]);
	}
	
	public function configJSON()
	{
	    return array_merge(parent::configJSON(), [
			'tableName' => $this->getResult()->table->gdoClassName(),
			'pagemenu' => $this->pagemenu ? $this->getPageMenu()->configJSON() : null,
		    'searchable' => $this->searchable,
			'sortable' => $this->sortable,
			'sortableURL' => $this->sortableURL,
		    'filtered' => $this->filtered,
		    'filterable' => $this->filterable,
		    'orderable' => $this->orderable,
		    'orderDefaultField' => $this->orderDefault,
		    'orderDefaultASC' => $this->orderDefaultAsc,
	    ]);
	}
	
	private function renderJSONData()
	{
		$data = [];
		$result = $this->getResult();
		$table = $result->table;
		while ($gdo = $table->fetch($result))
		{
		    $dat = [];
		    foreach ($gdo->gdoColumnsCache() as $gdt)
		    {
		        if ($json = $gdt->gdo($gdo)->renderJSON())
		        {
		            foreach ($json as $k => $v)
		            {
    		            $dat[$k] = $v;
		            }
		        }
		    }
			$data[] = $dat;
		}
		return $data;
	}
	
	################
	### Page for ###
	################
	/**
	 * Calculate the page for a gdo.
	 * We do this by examin the order from our filtered query.
	 * We count(*) the elements that are before or after orderby.
	 * @param GDO $gdo
	 * @throws GDOException
	 */
	public function getPageFor(GDO $gdo)
	{
	    if ($this->result instanceof ArrayResult)
	    {
	        throw new GDOException("@TODO implement getPageFor() ArrayResult");
	    }
	    else
	    {
	        $q = $this->query->copy(); #->noJoins();
	        foreach ($q->orderBy as $i => $column)
	        {
	            $subq = $gdo->entityQuery()->from($gdo->gdoTableName()." AS sq{$i}")->selectOnly($column)->buildQuery();
	            $cmpop = $q->orderDir[$i] ? '<' : '>';
	            $q->where("{$column} {$cmpop} ( {$subq} )");
	        }
	        $q->selectOnly('COUNT(*)')->noOrder();
	        $itemsBefore = $q->exec()->fetchValue();
	        return $this->getPageForB($itemsBefore);
	    }
	}
	
	private function getPageForB($itemsBefore)
	{
	    $ipp = $this->getPageMenu()->ipp;
	    return intval( ($itemsBefore + 1) / $ipp ) + 1;
	}

}
