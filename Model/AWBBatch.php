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

        $searchCondition = array(
            'courier_company_id' => $this->getCourierCompanyID(),
            'account_id' => $this->getAccountID(),
            );
        $response = DBManager::search($this->tableName(), $searchCondition, array($this->primaryKey()));
        $data = [];
        echo 'model';
        while($row = $response->fetch_assoc()) {
            // print_r($row);
            $data[] = $row[$this->primaryKeyName()];
        }
        // die;
        return $data;
    }
}