<?php
namespace GDO\UI;

use GDO\Core\GDT;
use GDO\Core\GDT_Template;
use GDO\Core\WithFields;

/**
 * Simple content pane.
 * 
 * @author gizmore
 * @version 6.10.2
 * @since 6.0.0
 */
class GDT_Panel extends GDT
{
	use WithTitle;
	use WithText;
	use WithFields;
	use WithPHPJQuery;
	
	public function renderCell()
	{
	    return GDT_Template::php('UI', 'cell/panel.php', ['field' => $this]);
	}
	
	public function renderXML()
	{
	    return sprintf("<%s title=\"%s\" text=\"%s\">\n%s\n</%1\$s>\n",
	        $this->name,
	        $this->renderTitle(),
	        $this->renderText(),
	        $this->renderXMLFields());
	}

}
