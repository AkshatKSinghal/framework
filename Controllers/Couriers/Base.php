<?php

namespace Controllers\Couriers;

use \Controllers\CourierServiceAccount;

/**
*
*/
class Base
{
    protected static $status;
    protected static $name = '';
    protected static $baseUrl;
    public static function __callStatic($function, $arguments)
    {
        #TODO getCourierClassName
        $courierClassName = get_called_class();
        if (!self::serviceSupported($courierClassName, $function)) {
            throw new Exception("Service $function not supported for $courierClassName");
        }
        return call_user_func_array(array($courierClassName, $function), $arguments);
    }

    public static function serviceSupported($courierClassName, $service)
    {
        #TODO getCourierClassName
        return method_exists($courierClassName, $service);
    }

    /**
     * Function to get the btpost status from courier company status using $status array
     * @param string $courierStatus
     * @return string $btStatus
     */
    protected function getBtStatus($courierStatus)
    {
        return $btStatus;
    }

    /**
     * Function to get the COurier company status from btpost status using $status array
     * @param string $btStatus
     * @return string $courierStatus
     */
    protected function getCourierStatus($btStatus)
    {
        return $courierStatus;
    }

    public function getCItyFromPincode($pincode)
    {
        return 'New York';
    }

    private static function object2array($object)
    {
        return json_decode(json_encode($object), 1);
    }

    public static function getCourierServcieAccount($accountId, $courierServiceId)
    {
        $courierServiceAccount = CourierServiceAccount::getByAccountAndCourierService($accountId, $courierServiceId);
        return $courierServiceAccount;
    }
}
