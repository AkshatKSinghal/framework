<?php

/**
*
*/
class Base
{
    public static function __callStatic($function, $arguments)
    {
        #TODO getCourierClassName
        if (!self::serviceSupported($function)) {
            throw new Exception("Service $function not supported for $courierClassName");
        }
        call_user_func_array(array($courierClassName, $function), $arguments);
    }

    public static function serviceSupported($service)
    {
        #TODO getCourierClassName
        return method_exists($courierClassName, $service);
    }
}
