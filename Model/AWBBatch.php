<?php

namespace Model;
/**
* CRUD for AWB Batch
*/
// require 'Base.php';


class AWBBatch extends \Model\Base
{
	protected $id;
	protected $courierCompanyID;
	protected $accountID;
	protected $status;
    protected $validCount;
    protected $invalidCount;

	// function __construct()
	// {
	// 	// $this->courierCompanyID = $courierCompanyID;
	// 	// $this->accountID = $accountID;
	// 	// $this->status = 'pending';
	// 	// $this->id = 1;
	// }

    public function findByCourier()
    {
        return [1];
    	// return [2,3,4];
        //returns ids
    	//write query to find by $courierCompanyID and $accountID
    }
}