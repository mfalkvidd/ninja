<?php

/**
 * Let the user input a number
 */
class Form_Field_Number_Model extends Form_Field_Model {

	/**
	 * @return string
	 */
	public function get_type() {
		return 'number';
	}

	/**
	 * @param $raw_data array
	 * @param $result Form_Result_Model
	 */
	public function process_data(array $raw_data, Form_Result_Model $result) {
		$name = $this->get_name();
		if (!isset($raw_data[$name]))
			throw new FormException( "Unknown field $name");
		if (!is_numeric($raw_data[$name]))
			throw new FormException( "$name is not a number field");
		$result->set_value($name, (float)$raw_data[$name]);
	}
}