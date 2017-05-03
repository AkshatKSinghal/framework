<?php

namespace Model;
/**
* CRUD for AWB Batch
*/
// require 'Base.php';
use \DB\DB as DBManager;

class AWBBatch extends \Model\Base
{
	// protected $id;
	// protected $courierCompanyID;
	// protected $accountID;
	// protected $status;
 //    protected $validCount;
 //    protected $invalidCount;
    protected $tableName = 'AWBBatch';

    public function findByCourier()
    {
        DBManager::getInstance();
        $response = DBManager::executeQuery('select * from ' . $this->tableName . ' where courier_company_id = 1');
        print_r($response);
        die();
        return [1];
    	// return [2,3,4];
        //returns ids
    	//write query to find by $courierCompanyID and $accountID
    }
}