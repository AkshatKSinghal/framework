<?php

namespace Model;
/**
* CRUD for AWB Batch
*/
// require 'Base.php';
use \DB\DB as DBManager;

class AWBBatch extends \Model\Base
{
    protected static $tableName = 'AWBBatch';

    public function findByCourier()
    {
// <<<<<<< HEAD
//         DBManager::getInstance();
//         $response = DBManager::executeQuery('select * from ' . $this->tableName . ' where courier_company_id = 1');
//         $data = [];
//         if ($response->num_rows > 0) {
//             // output data of each row
//             while($row = $response->fetch_assoc()) {
//                 $data[] = $row['id'];
//             }
// =======
        $searchCondition = array(
            'courier_company_id' => $this->get('courierCompanyID'),
            'account_id' => $this->get('accountID'),
            );
        $response = DBManager::search($this->tableName(), $searchCondition, array($this->primaryKey()));
        $data = [];
        while($row = $response->fetch_assoc()) {
            $data[] = $row[$this->primaryKey()];
        }
        return $data;
    }
}