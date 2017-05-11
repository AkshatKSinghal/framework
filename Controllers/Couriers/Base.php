<?php

namespace Controllers\Couriers;

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

    private static function curlOptions($options)
    {
        $default = array(
            CURLOPT_AUTOREFERER => true,
            //CURLOPT_COOKIEFILE => $this->_cookieJar,
            //CURLOPT_COOKIEJAR =>  $this->_cookieJar,
            CURLOPT_USERAGENT => static::getUserAgent(),
            CURLOPT_TIMEOUT => 2000000,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_MAXREDIRS => 50
        );
    
        return $options + $default;
    }

    public static function options()
    {
        $o = new \stdClass();
        $o->query = array();
        $o->payload = array();
        $o->headers = array();
        $o->referer = '';
        $o->fileName = '';
        $o->isdownload = false;
        $o->upload = false;
        $o->options = array();
        $o->success = function ($r) {
            return true;
        };
        $o->throw = true;
        return $o;
    }

    protected static function callbacks($response, $options)
    {
        $response['success'] = true;
        if ($response['header']['http_status_code'] >= 400) {
            $response['success'] = false;
        }
    
        if ($response['success'] && isset($options->success)) {
            $method = $options->success;
            $response['success'] = $method($response);
        }
        
        if ($options->throw && $response['success']===false) {
            debug('call failed ');
        }
                
        return $response;
    }

    protected static function get($url, $options = null)
    {
        if ($options == null) {
            $options = static::options();
        }
        
        $opts = static::curlOptions($options->options);
        $opts[CURLOPT_REFERER] = $options->referer;
        $response = \Utility\WCurl::get($url, $options->query, $options->payload, $options->headers, $opts);
        return static::callbacks($response, $options);
    }

    protected static function xpath($content)
    {
        return new \DOMXPath(static::dom($content));
    }

    protected static function dom($content)
    {
        $dom = new \DOMDocument();
        $dom->strictErrorChecking = false;
        $dom->loadHTML($content);
        return $dom;
    }

    protected static function getUserAgent()
    {
        $agents = array(
            'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.2 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1468.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1467.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1464.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:24.0) Gecko/20100101 Firefox/24.0',
            'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:23.0) Gecko/20130406 Firefox/23.0',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:23.0) Gecko/20131011 Firefox/23.0',
            'Mozilla/5.0 (compatible; MSIE 10.6; Windows NT 6.1; Trident/5.0; InfoPath.2; SLCC1; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729; .NET CLR 2.0.50727) 3gpp-gba UNTRUSTED/1.0',
            'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)',
            'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))'
        );
        return $agents[rand(0, count($agents)-1)];
    }
}
