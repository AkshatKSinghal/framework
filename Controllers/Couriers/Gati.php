<?php

namespace Controllers\Couriers;
/**
*
*/
class Gati extends Base
{
    
    /**
     * Function to book a shipment on Gati
     *
     * @param mixed $orderInfo Array containing the order information
     * @param string $serviceCode Product service code via which the shipment is to be booked
     *
     * @throws Exception in case the order information is invalid/ incomplete
     * @throws Exception in case the pincodes are not serviceable
     * @throws Exception in case the Shipment Booking call fails from the Courier API side
     *
     * @return string $awb AWB number for the booked shipment
     */
    protected static function bookShipment($orderInfo, $serviceCode, $awb)
    {
        // #TODO get AWB number
        // #TODO Call the Gati API
        // throw new \Exception("Error Processing Request", 1);
        
        $return = ['awb' => 12, 'details'=> 'asda'];
        return $return;
        // return(['awb' => '12', 'details' => 'adsasdada']);
    }

    /**
     * Function ot track a shipment on Gati
     *
     * @param string $awb AWB Number to be tracked
     *
     * @throws Exception in case the AWB number is rejected by Gati
     * @throws Exception in case the Gati API throws an unknown error
     *
     * @return mixed $trackingInfo Tracking information
     */
    protected static function trackShipment($awb)
    {
        return [
            'courier' => 'GATI',
            'awb' => $awb,
            'status' => 'IN-TRANSIT',
            'details' => [
                'timestamp' => 'EPOCH_TIMESTAMP',
                'location' => 'LOCATION_OF_UPDATE',
                'message' => 'UPDATE_MESSAGE'
            ]
        ];
    }
}
