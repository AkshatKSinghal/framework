<?php

namespace Model;

/**
* CRUD for AWB Batch
*/
use \DB\DB as DBManager;

class AWBBatch extends \Model\Base
{
    protected static $tableName = 'awb_batches';

    /**
     * Function to find the awb batch by courier_company_id for the given account_id
     * @return mixed awb_batch found
     */
    public function findByCourier()
    {
        $searchCondition = array(
            'courier_company_id' => $this->getCourierCompanyId(),
            'account_id' => $this->getAccountId(),
            'status' => 'PROCESSED'
            );
        $response = (new DBManager())->search($this->tableName(), $searchCondition, array($this->primaryKeyName()));
        $data = [];
        while ($row = $response->fetch_assoc()) {
            $data[] = $row[$this->primaryKeyName()];
        }
        return $data;
    }

    /**
     * Function to savse the awb batch record in the db
     * @param bool $validate
     * @param array $fields
     * @return void
     */
    public function save($validate = true, $fields = [])
    {
        parent::save($validate, $fields);
    }
}
