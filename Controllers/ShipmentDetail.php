<?php

namespace Controllers;

use \Model\ShipmentDetail as ShipmentDetailModel;
use \Controllers\Base as BaseController;
use \Controllers\CourierServiceAccount as CourierServiceAccount;

/**
*
*/
class ShipmentDetail extends BaseController
{
    protected $fields = [
        'order_ref' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ], 
        'account_id' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'courier_service_id' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'cod_value' => [
            'mandatory' => true,
            'data' => [],
            'multiple' => false
        ],
        'pickup_address'  => [
            'mandatory' => true,
            'data' => [
                'name' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ], 
                'text' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'time' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'phone' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'pincode' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'email_id' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'state' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'country' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'landmark' => [
                    'mandatory' =>false,
                    'data' => [],
                    'multiple' => false
                ]
            ],
            'multiple' => false
        ],
        'drop_address'  => [
            'mandatory' => true,
            'data' => [
                'name' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'text' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'phone' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'pincode' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'state' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'country' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],                
                'landmark' => [
                    'mandatory' =>false,
                    'data' => [],
                    'multiple' => false
                ]
            ],
            'multiple' => false
        ],
        'shipment_details'  => [
            'mandatory' => true,
            'data' => [
                'orders' => [
                    'mandatory' => true,
                    'data' => [
                        'items' => [
                            'mandatory' => true,
                            'data' => [
                                'price' => [
                                    'mandatory' => true,
                                    'data' => [],
                                    'multiple' => false
                                ],
                                'sku_id' => [
                                    'mandatory' => true,
                                    'data' => [],
                                    'multiple' => false
                                ],
                                'quantity' => [
                                    'mandatory' => true,
                                    'data' => [],
                                    'multiple' => false
                                ],                
                                'description' => [
                                    'mandatory' =>false,
                                    'data' => [],
                                    'multiple' => false
                                ]  
                            ],
                            'multiple' => true
                        ], 
                        'invoice' => [
                            'mandatory' => true,
                            'data' => [
                                'ref_id' => [
                                        'mandatory' => true,
                                    'data' => [],
                                    'multiple' => false
                                ],
                                'value' => [
                                    'mandatory' => true,
                                    'data' => [],
                                    'multiple' => false
                                ],
                                'date' => [
                                    'mandatory' => true,
                                    'data' => [],
                                    'multiple' => false
                                ]
                            ],
                            'multiple' => false
                        ],
                    ],
                    'multiple' => false
                ],
                'length' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'breadth' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'height' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],
                'weight' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],                
                'tin' => [
                    'mandatory' =>false,
                    'data' => [],
                    'multiple' => false
                ],
                'type' => [
                    'mandatory' => true,
                    'data' => [],
                    'multiple' => false
                ],                
                'reason' => [
                    'mandatory' =>true,
                    'data' => [],
                    'multiple' => false
                ]                
            ],
            'multiple' => false
        ]
    ];

    public function bookShipment($request)
    {
        $checkedData = $this->checkFields($request);
        $modelId = $this->setIndividualFields($checkedData);
        return $modelId;
    }

    protected function setIndividualFields($data)
    {
        $model = new ShipmentDetailModel();
        $courierServiceAccount = CourierServiceAccount::getByAccountAndCourierService($data['account_id'], $data['courier_service_id']);
        
        foreach ($data as $key => $value) {
            $functionName = 'set'.$key;
            $model->$functionName($value);
        }
        $model->validate();
        return $model->save();
    }

    public function getById($id)
    {
        $shipmentDetails = new ShipmentDetailModel($id);
        return $courier;
    }
}
