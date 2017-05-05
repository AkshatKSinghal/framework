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
            'status' => 'PROCESSED'
            );
        $response = DBManager::search($this->tableName(), $searchCondition, array($this->primaryKeyName()));
        $data = [];
        while($row = $response->fetch_assoc()) {
            $data[] = $row[$this->primaryKeyName()];
        }
        return $data;
    }
}