<?php

class ORMTypeSet implements ORMTypeI {

	private $name;
	private $backend_name;
	private $options;
	private $set_class;

	public function __construct ($name, $backend_name, $options) {

		$this->name = $name;
		$this->backend_name = $backend_name;
		$this->options = $options;

		if (!isset($options[1])) {
			throw new ORMGeneratorException("Missing option (ORM object type) for ORMTypeSet");
		}

		$this->set_class = $options[1] . "Set_Model";
		$this->pool_class = $options[1] . "Set_Model";

	}

	public function get_default_value () {
		return "\"\"";
	}

	public function generate_set ($context) {
		$context->init_function( "set_{$this->name}", array('value') );

		$context->write("if(!is_a(\$value, {$this->set_class})) {");
		$context->write("throw new InvalidArgumentException('Expected {$this->set_class} for set_{$this->name}');");
		$context->write("}");

		$context->write("\$query = \$value->get_query();");
		$context->write("if(\$this->{$this->name} !== \$query) {");
		$context->write("\$this->{$this->name} = \$query;");
		$context->write("\$this->_changed[%s] = true;", $this->name);
		$context->write("}");

		$context->finish_function();
	}

	public function generate_get ($context) {
		$context->init_function("get_{$this->name}");
		$context->write("if (!\$this->{$this->name}) {");
		$context->write("return ObjectPool_Model::get_by_query(\$this->{$this->name});");
		$context->write("} else {");
		$context->write("return {$this->pool_class}::none();");
		$context->write("}");
		$context->finish_function();
	}

	public function generate_save ($context) {
		$context->write("\$values['{$this->name}'] = \$this->{$this->name};");
	}

	public function generate_iterator_set ($context) {
		$context->write("if(array_key_exists(\$prefix.'{$this->backend_name}', \$values)) {");
		$context->write("\$obj->{$this->name} = \$values[\$prefix.'{$this->backend_name}'];");
		$context->write("}");
	}

	public function generate_array_set ($context) {
		$context->write("if(array_key_exists('{$this->name}', \$values)) {" );
		$context->write("\$obj->{$this->name} = \$values['{$this->name}'];");
		$context->write("}");
	}

}
