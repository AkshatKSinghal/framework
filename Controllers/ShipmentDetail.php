<?php

namespace Controllers;

use \Model\ShipmentDetail as ShipmentDetailModel;
use \Controllers\Base as BaseController;
use \Controllers\CourierServiceAccount as CourierServiceAccount;
use \Controllers\AWBBatch as AWBBatch;

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
        $orderInfo = [
            'pickup_address' => $checkedData['pickup_address'],
            'drop_address' => $checkedData['drop_address'],
            'shipment_details' => $checkedData['shipment_details']
        ];
        $courierService = new CourierService([$checkedData['courier_service_id']]);
        $serviceType = $courierService->model->getServiceType();
        $checkedData['shipment_type'] = $serviceType;
        $preAllocateAWB = $courierService->preallocateAWBAllowed();
        $courierResponse = '';
        #TODO courier cariers to be dynamically assigned instead of gati hardcode

        switch ($preAllocateAWB) {
            case 'pre':
                $courierServiceAccount = CourierServiceAccount::getByAccountAndCourierService($checkedData['account_id'], $checkedData['courier_service_id']);
                $awbDetail = $courierServiceAccount->getAWB();
                $awb = $awbDetail['awb'];
                $checkedData['courier_service_account_id'] = $courierServiceAccount->model->getId();
                try {
                    $courierResponse = \Controllers\Couriers\Gati::bookShipment($orderInfo, $serviceType, $awb);
                } catch (\Exception $e) {                    
                    //mark awb as not assigned
                    $awbBatch = new AWBBatch([$awbDetail['awbBatchId']]);
                    $awbBatch->logAWBEvent('failed', $awb);
                    $awbBatch->updateTableForFailedAwb();
                    throw new \Exception("Courier rejected awb", 1);
                    
                }
                break;

            case 'post':
                try {
                    $courierResponse = \Controllers\Couriers\Gati::bookShipment($orderInfo, $serviceType);
                } catch (\Exception $e) {
                    //mark awb as not assigned
                    $awbBatch = new AWBBatch([]);
                    $awbBatch->logAWBEvent('failed', $awb);
                    $awbBatch->updateTableForFailedAwb();
                    throw new \Exception("Courier rejected awb", 1);
                }
                break;
        }
        $checkedData['courier_service_details'] = $courierResponse['details'];
        $checkedData['courier_service_reference_number'] = $courierResponse['awb'];
        return $this->addShipmentTODB($checkedData, $awb);
    }

    public function addShipmentRequest($request)
    {
        if (!isset($request['awb'])) {
            throw new \Exception("AWB not found", 1);            
        }
        $awb = $request['awb'];
        $checkedData = $this->checkFields($request);
        $courierService = new CourierService([$checkedData['courier_service_id']]);
        $serviceType = $courierService->model->getServiceType();
        $checkedData['shipment_type'] = $serviceType;
        return $this->addShipmentTODB($checkedData, $awb);
    }

    private function addShipmentTODB($data, $awb)
    {
        $data['status'] = 'ACTIVE';
        $btPostId =  $this->setIndividualFields($data);
        $response = [
            'status' => 'SUCCESS',
            'message' => 'Couier booked',
            'data' => [
                'awb' => $awb,
                'courier' => 'gati',
                'ref_id' => $btPostId,
                'label' => 'label'
            ]
        ];
        return $response;
    }

    protected function setIndividualFields($data)
    {
        $model = new ShipmentDetailModel();
        // $insertData['courier_service_account_id'] = $courierArray['id'];
        $mapArray = [
            'order_meta' => ['pickup_address', 'drop_address', 'shipment_details', 'cod_value'],
            'order_ref' => 'order_ref',
            'courier_service_account_id' => 'courier_service_account_id',
            'courier_service_details' => 'courier_service_details',
            'courier_service_reference_number' => 'courier_service_reference_number',
            'status' => 'status',
            'shipment_type' => 'shipment_type',
        ];

        foreach ($mapArray as $dbField => $mergeFields) {
            $resultFields = [];
            if (is_array($mergeFields)) {            
                foreach ($mergeFields as $mergeField) {
                    $resultFields[$mergeField] = $data[$mergeField];
                }
                $insertData = json_encode($resultFields);
            } else {
                $insertData = $data[$dbField];
            }
            $key = str_replace('_', '', ucwords($dbField, '_'));
            $functionName = 'set'.$key;
            $model->$functionName($insertData);
        }
        $model->setLastUpdated(date("Y/m/d"));
        $model->setCreated(date("Y/m/d"));
        $model->setDateEntry(date("Y/m/d"));
        #TODO move last updated time to save funciton in base model
        $model->validate();
        return $model->save();
    }

    public function trackShipment($request)
    {
        $shipId = $request['id'];
        $ship = new ShipmentDetail([$shipId]);
    }

    public function getById($id)
    {
        $shipmentDetails = new ShipmentDetailModel($id);
        return $courier;
    }
}
