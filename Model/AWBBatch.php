<?php

namespace Model;
/**
* CRUD for AWB Batch
*/
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
            'courier_company_id' => $this->getCourierCompanyID(),
            'account_id' => $this->getAccountID(),
            );
        $response = DBManager::search($this->tableName(), $searchCondition, array($this->primaryKeyName()));
        $data = [];
        while($row = $response->fetch_assoc()) {
            $data[] = $row[$this->primaryKeyName()];
        }
        return $data;
    }
}