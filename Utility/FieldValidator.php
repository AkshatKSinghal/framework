<?php

namespace Utility;

/**
* Class for Validating mandatory and optional fields against an array
*/
class FieldValidator
{
    public static function checkFields($requestData, $fields, $subArrayName = 'main', $cntr = 0)
    {
        $checkedData = [];
        $arrayTrace = $subArrayName;
        foreach ($fields as $field => $fieldDetail) {
            if ($fieldDetail['mandatory'] && !(array_key_exists($field, $requestData) && $requestData[$field] != '')) {
                throw new \Exception("Mandatory Field not found " . $arrayTrace . '->' . $field);
            } else {
                if (isset($requestData[$field])) {                
                    $nextRequestData = $requestData[$field];
                    if (empty($fieldDetail['data'])) {
                        $checkedData[$field] = $requestData[$field];
                    } else {
                        if ($fieldDetail['multiple']) {
                            // if (!isset($checkedData[$field])) {
                            //     $checkedData[$field] = [];
                            // }
                            foreach ($requestData[$field] as $requestField) {
                                $checkedData[$field][] = self::checkFields($requestField, $fields[$field]['data'], $subArrayName);
                            }
                            $nextRequestData = $requestData[$field][$cntr];
                        } else {
                            $checkedData[$field] = self::checkFields($nextRequestData, $fieldDetail['data'], $subArrayName . '->' . $field);
                        }
                    }
                }
            }
        }
        return $checkedData;
    }
}
