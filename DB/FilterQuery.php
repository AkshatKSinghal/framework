<?php

namespace DB;
/**
* Class for creating filters
*/
class FilterQuery
{
	private $field;
	private $operator;
	private $value;

	private $query;

	function __construct($field, $value, $operator = "=")
	{
		$this->field = $field;
		$this->value = $value;
		$this->operator = $operator;
	}

	public function getSQL($mysqli)
	{
		$value = $this->escape($this->value, $mysqli);
		switch ($this->operator) {
			case '=':
			case 'IN':
				$operator = is_array($this->value) ? 'IN' : '=';
				break;
			case '!=':
			case 'NOT IN':
				$operator = is_array($this->value) ? 'NOT IN' : '!=';
				break;
			case '<':
			case '>':
			case '<=':
			case '>=':
				$this->checkArray();
				$operator = $this->operator;
				break;
			case 'LIKE':
			case 'NOT LIKE':
				$this->checkArray();
				$position = strpos($value, '%');
				if ($position !== 0 && $position != strlen($value)-1 ) {
					throw new Exception("Like operation requires % to be at start or beginning of string");
				}
				break;
			case 'NULL':
			case 'NOT NULL':
				$operator = 'IS';
				$value = $this->operator;
				break;
			default:
				throw new Exception("Unknown operator {$this->operator}");
				break;
		}
		return " `$this->field` $operator $value";
	}

	private function escape($value, $mysqli)
	{
		if (is_array($value)) {
			foreach ($value as $i => $item) {
				$value[$i] = $mysqli->real_escape_string($item);
			}
			$value = "('". implode("', '", $value) . "')";
		} else {
			$value = "'" . $mysqli->real_escape_string($value) . "'";
		}
		return $value;
	}

	private function checkArray()
	{
		if (is_array($this->value)) {
			throw new Exception("Array not allowed for {$this->operator}");
		}
	}
}