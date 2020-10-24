<?php
namespace GDO\Core;
/**
 * Used by types that have collections of fields.
 * E.g.: GDT_Fields, GDT_Bar, GDT_Response, GDT_Form, GDT_Table->headers, GDO
 * @author gizmore
 * @version 6.05
 */
trait WithFields
{
	###############
	### Factory ###
	###############
	/**
	 * @param GDT ...$fields
	 * @return self
	 */
	public static function makeWith(GDT ...$fields)
	{
		return self::make()->addFields($fields);
	}
	
	##############
	### Fields ###
	##############
	/**
	 * @var \GDO\Core\GDT[]
	 */
	public $fields = [];
	public function addField(GDT $field=null) { if ($field) $this->fields[$field->name] = $field; return $this; }
	public function addFields(array $fields=null)
	{
		if ($fields)
		{
			foreach ($fields as $field)
			{
				$this->fields[$field->name] = $field;
			}
		}
		return $this;
	}
	
	/**
	 * Return all fields in this collection.
	 * @return \GDO\Core\GDT[]
	 */
	public function getFields() { return $this->fields; }
	public function fieldCount() { return $this->fields ? count($this->fields) : 0; }
	
	/**
	 * Return a field by name.
	 * @param string $name
	 * @return \GDO\Core\GDT
	 */
	public function getField($name) { return @$this->fields[$name]; }
	public function removeField($name) { unset($this->fields[$name]); }
	public function removeFields() { $this->fields = []; }

	################
	### Iterator ###
	################
	public function withFields($callback) { $this->_withFields($this, $callback); }
	public function _withFields(GDT $field, $callback)
	{
		call_user_func($callback, $field);
		if ($fields = $field->getFields())
		{
			foreach ($fields as $field)
			{
				$this->_withFields($field, $callback);
			}
		}
	}
	
	public function renderJSON()
	{
		$json = [];
		foreach ($this->fields as $gdoType)
		{
			if ($data = $gdoType->renderJSON())
			{
				$json[$gdoType->name] = $data;
			}
		}
		return $json;
	}

	##############################
	### Get Fields Recursively ###
	##############################
	public function getFieldsRec()
	{
		$fields = [];
		$this->_getFieldsRec($fields, $this);
		return $fields;
	}
	
	private function _getFieldsRec(array &$fields, GDT $gdt)
	{
		foreach ($gdt->fields as $_gdt)
		{
			$fields[$_gdt->name] = $_gdt;
			$uses = class_uses($_gdt);
			if (in_array('GDO\Core\WithFields', $uses, true))
			{
				$this->_getFieldsRec($fields, $_gdt);
			}
		}
	}
	
}
