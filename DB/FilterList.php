<?php
namespace DB;

/**
* 
*/
class FilterList
{
	private $operator;
	private $parts;

	public function __construct($operator, $parts)
	{
		$this->operator = $operator;
		$this->parts = $parts;
		if (!is_array($this->parts) || empty($this->parts)) {
			throw new \Exception("Parts needs to be non empty array");
		}
		#TODO Validate if all elements of parts are of type FilterQuery or FilterList
	}

	public function getSQL($mysqli)
	{
		$parts = [];
		foreach ($this->parts as $part) {
			$parts[] = $part->getSQL($mysqli);
		}
		return implode(" {$this->operator} ", $parts);
	}
}