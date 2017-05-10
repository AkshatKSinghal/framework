<?php

namespace Utility;

// use \SimpleXMLElement;

/**
*
*/
class SimpleXMLElementWrapper
{
    private static function addNode($data, &$xml_data)
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item'.$key; //dealing with <0/>..<n/> issues
            }
            if (is_array($value)) {
                $subnode = $xml_data->addChild($key);
                self::addNode($value, $subnode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

    public static function arrayToXML($data)
    {
        $xml_data = new \SimpleXMLElement('<?xml version="1.0"?><gati></gati>');
        self::addNode($data, $xml_data);
        return $xml_data->asXML();
    }
}
