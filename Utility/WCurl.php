<?php

namespace Utility;

class WCurl
{

    private $_ch = null;
    private $_method = null;
    private $_query = null;
    private $_headers = null;
    private $_url = null;
    private $_payload = null;

    public $responseHeaders = array();

    public function __construct($method, $url, $query = '', $payload = '', $headers = array(), $options = array())
    {
        //debug(self::_requestUri($url, $query));

        $this->_ch = curl_init(self::_requestUri($url, $query));

        $this->_method = $method;
        $this->_url = $url;
        $this->_query = $query;
        $this->_payload = $payload;
        $this->_headers = $headers;
        $this->_setOptions($options);
    }

    private static function _requestUri($url, $query)
    {
        if (empty($query)) {
            return $url;
        }
        if (is_array($query)) {
            return "$url?".http_build_query($query);
        } else {
            return "$url?$query";
        }
    }

    private function _setOptions($curlOpts = array())
    {

        $default_curl_opts = array
        (
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'wcurl',
            CURLOPT_CONNECTTIMEOUT => 9000,
            CURLOPT_TIMEOUT => 9000,
            CURLOPT_VERBOSE => 1,
            CURLINFO_HEADER_OUT => true
        );

        if ('GET' == $this->_method) {
            $default_curl_opts[CURLOPT_HTTPGET] = true;
        } else {
            // Disable cURL's default 100-continue expectation
            if ('POST' == $this->_method) {
                $default_curl_opts[CURLOPT_POST] = true;
                if ($this->_headers==null) {
                    array_push($this->_headers, 'Expect:');
                }
            } else {
                $default_curl_opts[CURLOPT_CUSTOMREQUEST] = $this->_method;
            }

            if (!empty($this->_payload)) {
                $payload = $this->_payload;
                //debug($payload);
                if (is_array($payload)) {
                    $payload = http_build_query($payload);
                    //echo $payload;
                    if ($this->_headers==null) {
                        array_push($this->_headers, 'Content-Type: application/x-www-form-urlencoded; charset=utf-8');
                    }
                }
                $default_curl_opts[CURLOPT_POSTFIELDS]= $payload;
            }
        }
        if (!empty($this->_headers)) {
            $default_curl_opts[CURLOPT_HTTPHEADER] = $this->_headers;
        }
        $overriden_opts = $curlOpts + $default_curl_opts;
        foreach ($overriden_opts as $curl_opt => $value) {
            curl_setopt($this->_ch, $curl_opt, $value);
        }
        //debug($default_curl_opts);
    }

    private static function _parseResponseHeaders($msg_header)
    {
        $multiple_headers = preg_split("/\r\n\r\n|\n\n|\r\r/", trim($msg_header));
        $last_response_header_lines = array_pop($multiple_headers);
        $response_headers = array();

        $header_lines = preg_split("/\r\n|\n|\r/", $last_response_header_lines);
        list(, $response_headers['http_status_code'], $response_headers['http_status_message']) = explode(' ', trim(array_shift($header_lines)), 3);
        foreach ($header_lines as $header_line) {
            set_time_limit(0);
            list($name, $value) = explode(':', $header_line, 2);
            $response_headers[strtolower($name)] = trim($value);
        }

        return $response_headers;
    }

    public static function get($url, $query = '', $payload = '', $headers = array(), $options = array())
    {
        return self::request('GET', $url, $query, $payload, $headers, $options);
    }

    public static function post($url, $query = '', $payload = '', $headers = array(), $options = array())
    {
        return self::request('POST', $url, $query, $payload, $headers, $options);
    }
    public static function put($url, $query = '', $payload = '', $headers = array(), $options = array())
    {
        return self::request('PUT', $url, $query, $payload, $headers, $options);
    }
    public static function request($method, $url, $query = '', $payload = '', $headers = array(), $options = array())
    {
        $wCurl = new self($method, $url, $query, $payload, $headers, $options);
        return $wCurl->send();
    }

    public function send()
    {
        $response = curl_exec($this->_ch);
        $curl_info = curl_getinfo($this->_ch);
        $errno = curl_errno($this->_ch);
        $error = curl_error($this->_ch);
        curl_close($this->_ch);
        try {
            if ($errno) {
                throw new WCurlException($error, $errno);
            }
            $header_size = $curl_info["header_size"];
            $msg_header = substr($response, 0, $header_size);
            $msg_body = substr($response, $header_size);
            $this->responseHeaders = self::_parseResponseHeaders($msg_header);
            return array('header' => $this->responseHeaders, 'body' => $msg_body, 'info' => $curl_info);
        } catch (Exception $e) {
            return array('header' => null, 'body' =>null, 'info' =>$curl_info);
        }

    }


    public static function post1($url, $cookie_path = null, $method = null, $postVars = null, $isupload = false, $header = array(), $isdownload = false, $filename = '')
    {
        $useragent="Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)";
        $cookie_path_fname=$cookie_path;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        if ($method=="POST") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars);
            if ($isupload) {
                //debug($postVars);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars);
            } else {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/x-www-form-urlencoded'));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postVars);
            }
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // return response
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3000);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:9050');
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        //curl_setopt($ch, CURLOPT_REFERER, 'http://www.tradus.com/sell');
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_path_fname);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_path_fname);
        if ($isdownload) {
            $fp = fopen($filename, "w+");
            curl_setopt($ch, CURLOPT_FILE, $fp);
        }

        $result['body'] = curl_exec($ch);
        $result['header']=curl_getinfo($ch);
        try {
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
            debug($message);
            return $result;
        }
        curl_close($ch);
        return $result;
    }

    public static function multi_get($urls = null, $type = '%PDF')
    {
        $ch = array();
        $result = array();
        $cmh = curl_multi_init();
        foreach ($urls as $i => $url) {
            $ch[$i] = curl_init();
            curl_setopt($ch[$i], CURLOPT_URL, $url);
            curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch[$i], CURLOPT_HEADER, 0);
            curl_multi_add_handle($cmh, $ch[$i]);       //add singe curl as a handle to curl_multi_exec
        }
        $running=null;
        set_time_limit(200);
        do {
            $mrc = curl_multi_exec($cmh, $running);         //execute curl_multi_exec
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($running && $mrc == CURLM_OK) {
            // Wait for activity on any curl-connection
            if (curl_multi_select($cmh) == -1) {
                usleep(1);
            }
            // Continue to exec until curl is ready to
            // give us more data
            do {
                $mrc = curl_multi_exec($cmh, $running);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($urls as $i => $url) {
            $tmp = curl_multi_getcontent($ch[$i]);
            if (strpos($tmp, $type) !== false) {
                $result[$i] = $tmp;
            }

            curl_multi_remove_handle($cmh, $ch[$i]);
            curl_close($ch[$i]);
        }
        //debug(array_keys($result));
        curl_multi_close($cmh);         //close curl connection

        return $result;
    }
}
