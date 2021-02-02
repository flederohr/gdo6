<?php
namespace GDO\Core;

use GDO\DB\GDT_Name;
use GDO\DB\GDT_Object;
use GDO\DB\GDT_String;

/**
 * Module config table.
 * @author gizmore
 * @version 6.10
 * @since 3.00
 */
final class GDO_ModuleVar extends GDO
{
	public function gdoCached() { return false; }
	###########
	### GDO ###
	###########
	public function gdoColumns()
	{
		return array(
			GDT_Object::make('mv_module_id')->table(GDO_Module::table())->notNull()->primary(),
			GDT_Name::make('mv_name')->primary()->notNull(),
			GDT_String::make('mv_value')->notNull(),
		);
	}
	public function getVarName() { return $this->getVar('mv_name'); }
	public function getVarValue() { return $this->getVar('mv_value'); }
	
	public static function createModuleVar(GDO_Module $module, GDT $gdt)
	{
	    $var = $gdt->getVar();
	    if ($var === null)
		{
		    $gdt->var($gdt->initial);
		    return self::removeModuleVar($module, $gdt->name);
		}
		return self::table()->blank(array(
			'mv_module_id' => $module->getID(),
		    'mv_name' => $gdt->name,
			'mv_value' => $var,
		))->replace();
	}
	
	public static function removeModuleVar(GDO_Module $module, $varname)
	{
		$varname = GDO::escapeS($varname);
		self::table()->deleteWhere("mv_module_id={$module->getID()} AND mv_name='$varname'");
		return self::table()->blank(array(
		    'mv_module_id' => $module->getID(),
		    'mv_name' => $varname,
		    'mv_value' => null,
		));
	}
	
}
