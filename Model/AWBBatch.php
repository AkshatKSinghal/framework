<?php

/**
* CRUD for AWB Batch
*/
class AWBBatch extends Base
{
	protected var $id;
	protected var $courierCompanyID;
	protected var $accountID;
	protected var $status;

	function __construct($courierCompanyID, $accountID)
	{
		$this->courierCompanyID = $courierCompanyID;
		$this->accountID = $accountID;
		$this->status = 'pending';
		$this->id = 1;
	}

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
    	$this[$name] = $value;
        return null;
    }
}