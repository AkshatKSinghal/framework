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