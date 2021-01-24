<?php
namespace GDO\UI;

use GDO\Core\GDT;

/**
 * A simple paragraph.
 * 
 * @author gizmore
 * @version 6.10
 * @since 6.00
 */
class GDT_Paragraph extends GDT
{
    use WithText;

	public function renderCell()
	{
	    return sprintf('<p class="gdt-paragraph">%s</p>', $this->renderText()); 
	}

}
