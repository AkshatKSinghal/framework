<?php

namespace Utility;

use \SimpleXMLElement;

/**
* Class for Validating mandatory and optional fields against an array
*/
class SimpleXMLElementWrapper
{
    public static function arrayToXML($data)
    {
        $xml_data = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
        // function call to convert array to xml
        foreach ($data as $key => $value) {
           if (is_numeric($key)) {
               $key = 'item'.$key; //dealing with <0/>..<n/> issues
           }
           if (is_array($value)) {
               $subnode = $xml_data->addChild($key);
               array_to_xml($value, $subnode);
           } else {
               $xml_data->addChild("$key", htmlspecialchars("$value"));
           }
        }
        return $xml_data;
    }
}
