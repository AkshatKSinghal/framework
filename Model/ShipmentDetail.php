<?php

namespace Model;

use DB\DB;
/**
* CRUD for Couriers
*/
class ShipmentDetail extends Base
{
    protected static $tableName = 'shipment_details';
    // protected $uniqueKeys = ['name', 'short_code'];
    protected static $searchableFields = ['order_ref', 'courier_service_account_id'];

    public function validate()
    {
    }

    public function setCourierRefAWb($awb, $courierRef, $operation)
    {
        switch ($operation) {
            case 'set':
                $query[] = "DELETE FROM courier_references_awbs"
                ." WHERE awb = " . $awb;
            case 'add':
                $query[] = "INSERT INTO courier_references_awbs"
                ." (awb, courier_id) VALUES ( '" .$awb."', '". $courierRef . "')";
                break;
            case 'remove':
                $query[] = "DELETE FROM courier_references_awbs"
                ." WHERE awb = " . $awb
                ." AND courier_id = " . $courierRef . ")";
                break;
            default:
                throw new \Exception("Invalid operation in setCourierRefAWb: " . $operation);
                break;
        }
        foreach ($query as $q) {
            DB::executeQuery($q);
        }
    }
}
