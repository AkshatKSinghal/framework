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
            if ($fieldDetail['mandatory'] && !array_key_exists($field, $requestData)) {
                throw new \Exception("Mandatory Field not found " . $arrayTrace . '->' . $field);
            } else {
                $nextRequestData = $requestData[$field];
                if ($fieldDetail['multiple']) {
                    if ($cntr == 0) {
                        $checkedData[$field] = [[]];
                    }

                    $checkedData[$field][$cntr] = array_merge($checkedData[$field][$cntr], self::checkFields($requestData[$field][$cntr], $fields[$field]['data'], $subArrayName, ++$cntr));
                    $nextRequestData = $requestData[$field][$cntr];
                }
                if (empty($fieldDetail['data'])) {
                    $checkedData[$field] = $requestData[$field];
                } else {
                    $checkedData[$field] = self::checkFields($nextRequestData, $fieldDetail['data'], $subArrayName . '->' . $field);
                }
            }
        }
        return $checkedData;
    }
}
