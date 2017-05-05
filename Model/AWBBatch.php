<?php

namespace Model;

/**
* CRUD for AWB Batch
*/
use \DB\DB as DBManager;

class AWBBatch extends \Model\Base
{
    protected static $tableName = 'awb_batches';

    public function findByCourier()
    {
        $searchCondition = array(
            'courier_company_id' => $this->getCourierCompanyId(),
            'account_id' => $this->getAccountId(),
            'status' => 'PROCESSED'
            );
        $response = DBManager::search($this->tableName(), $searchCondition, array($this->primaryKeyName()));
        $data = [];
        while ($row = $response->fetch_assoc()) {
            $data[] = $row[$this->primaryKeyName()];
        }
        return $data;
    }
}
