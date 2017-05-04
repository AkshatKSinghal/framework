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
        if ($response->num_rows > 0) {
            // output data of each row
            $data = [];
            while($row = $response->fetch_assoc()) {
                // $rowData['id'] = $row['id'];
                // $rowData['courierCompanyID'] = $row['courier_company_id'];
                // $rowData['accountID'] = $row['account_id'];
                // $rowData['status'] = $row['status'];
                // $rowData['valid_count'] = $row['valid_count'];
                // $rowData['invalid_count'] = $row['invalid_count'];
                $data[] = $row['id'];
            }
        } else {
            // echo "0 results";
        }
        return $data;
    	// return [2,3,4];
        //returns ids
    	//write query to find by $courierCompanyID and $accountID
    }

    public function getTableName()
    {
        return$this->tableName;
    }
}