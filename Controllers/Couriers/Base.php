<?php

namespace Controllers\Couriers;
/**
*
*/
class Base
{
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
}
