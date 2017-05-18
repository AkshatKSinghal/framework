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
                    'multiple' => true
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
    /**
     * Function to handle bookshipment request in turn calls addSHipmentTODB function
     * @param mixed $request array contaning all the data related to the shipment
     * @return mixeed $response array contaning the status and other meta data related to the shipment booked
     */
    public function bookShipment($request)
    {
        $checkedData = $this->checkFields($request);
        $orderInfo = [
            'pickup_address' => $checkedData['pickup_address'],
            'drop_address' => $checkedData['drop_address'],
            'shipment_details' => $checkedData['shipment_details'],
            'order_ref' => $checkedData['order_ref']
        ];
        $courierService = new CourierService([$checkedData['courier_service_id']]);
        $serviceType = $courierService->getServiceType();
        $checkedData['shipment_type'] = $checkedData['shipment_details']['type'];
        $preAllocateAWB = $courierService->preallocateAWBAllowed();
        $courierResponse = '';
        $courierShortCode = $courierService->getCourierCompanyShortCode();
        // $courierName = '\Controllers\Couriers\\' . ucfirst(array_search($courierShortCode, $this->shortCodeMap));
        $courierName = '\Controllers\Couriers\\' . 'Gati';

        $courierServiceAccount = CourierServiceAccount::getByAccountAndCourierService($checkedData['account_id'], $checkedData['courier_service_id']);
        $credentials = $courierServiceAccount->getCredentials();
        switch ($preAllocateAWB) {
            case 'pre':
                $awbDetail = $courierServiceAccount->getAWB();
                $awb = $awbDetail['awb'];
                $checkedData['courier_service_account_id'] = $courierServiceAccount->getId();
                try {
                    $courierResponse = $courierName::bookShipment($orderInfo, $serviceType, $credentials, $awb);
                } catch (\Exception $e) {
                    if (stripos($e->getMessage(), 'INTERNAL ERROR java.lang.NumberFormatException') !== false || stripos($e->getMessage(), 'Docket was already uploaded') !== false) {
                        $awbBatch = new AWBBatch([$awbDetail['awbBatchId']]);
                        $awbBatch->logAWBEvent('failed', $awb);
                        $awbBatch->updateTableForFailedAwb();
                    }
                    throw new \Exception($e->getMessage() . " Courier rejected awb in pre allocation for awb". $awb, 1);
                }
                break;

            case 'post':
                try {
                    $courierResponse = $courierName::bookShipment($orderInfo, $serviceType, $credentials);
                } catch (\Exception $e) {
                    if (stripos($e->getMessage(), 'INTERNAL ERROR java.lang.NumberFormatException') !== false || stripos($e->getMessage(), 'Docket was already uploaded') !== false) {
                        $awbBatch = new AWBBatch([]);
                        $awbBatch->logAWBEvent('failed', $awb);
                        $awbBatch->updateTableForFailedAwb();
                    }
                    throw new \Exception($e->getMessage() . " Courier rejected awb post allocation for awb". $awb, 1);
                }
                break;
        }
        $checkedData['courier_service_details'] = $courierResponse['data']['details'];
        $checkedData['courier_service_reference_number'] = $courierResponse['data']['awb'];
        return $this->addShipmentTODB($checkedData, $awb);
    }

    /**
     *  Function to handle addShipment api request in turn calls addSHipmentTODB function
     * @param mixed $request array contaning all the data related to the shipment
     * @return mixeed $response array contaning the status and other meta data related to the shipment booked
     */
    public function addShipmentRequest($request)
    {
        if (!isset($request['awb'])) {
            throw new \Exception("AWB not found", 1);
        }
        $awb = $request['awb'];
        $checkedData = $this->checkFields($request);
        
        $courierService = new CourierService([$checkedData['courier_service_id']]);
        $serviceType = $courierService->getServiceType();
        $checkedData['shipment_type'] = $checkedData['shipment_details']['type'] == '' ? 'FORWARD': $checkedData['shipment_details']['type'] ;
        $courierServiceAccount = CourierServiceAccount::getByAccountAndCourierService($checkedData['account_id'], $checkedData['courier_service_id']);
        $checkedData['courier_service_account_id'] = $courierServiceAccount->getId();
        $checkedData['courier_service_details'] = '';
        $checkedData['courier_service_reference_number'] = $awb;
        return $this->addShipmentTODB($checkedData, $awb);
    }

    /**
     *  Function to handle addSHipment details in db
     * @param mixed $data the incoming data to be inserted in to shpment table
     * @param string $awb awb assiged to the shipment
     * @return mixed $response array contaning the status and other meta data related to the shipment booked
     */
    private function addShipmentTODB($data, $awb)
    {
        $data['status'] = 'PENDING';
        $btPostId =  $this->setIndividualFields($data);
        $response = [
            'status' => 'SUCCESS',
            'message' => 'Couier booked',
            'data' => [
                'awb' => $awb,
                'courier' => 'Gati',
                'ref_id' => $btPostId,
                'label' => 'label'
            ]
        ];
        return $response;
    }

    /**
     * Function to set all db fields and save the object in db
     * @param array $data
     * @return string $id modelId for the object inserted
     */
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
            $arr = explode('_', $dbField);
            $valueArr = [];
            foreach ($arr as $value) {
                $valueArr[] = ucfirst($value);
            }
            $ucdbField = implode('_', $valueArr); 
            $key = (str_replace('_', '', /*ucwords($dbField, '_')*/$ucdbField));
            // $key = str_replace('_', '', ucwords($dbField, '_'));
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

    /**
     * Function to hadnle track shipment api request
     * @param mixed $request array contaning request data and ref_id
     * @return mixed $response contaning status and tracking details
     */
    public function trackShipment($request)
    {
        if (!isset($request['ref_id'])) {
            throw new \Exception("Reference id not found", 1);
        }
        $shipId = $request['ref_id'];
        $ship = new ShipmentDetail([$shipId]);
        $awb = $ship->model->getCourierServiceReferenceNumber();

        $courierShortCode = $ship->getCourierShortCode();
        $courierName = '\Controllers\Couriers\\' . ucfirst(array_search($courierShortCode, $this->shortCodeMap));
        try {
            $courierResponse = reset($courierName::trackShipment([$awb]));
            $ship->model->setStatus($courierResponse['status']);
            $ship->model->save();
            return [
                'Status' => 'SUCCESS',
                'message' => 'Shipment status is ' . $courierResponse['status'] . '.',
                'data' => $courierResponse
            ];
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Function to handle track shipment using order ref and account id request
     * @param mixed $request containing account_id, order_ref, courier_service_id
     * @return mixed $response contaning status and tracking details
     */
    public function trackShipmentByRef($request)
    {
        if (!isset($request['account_id'])) {
            throw new \Exception("account_id id not found");
        }
        if (!isset($request['order_ref'])) {
            throw new \Exception("order_ref not found");
        }
        if (!isset($request['courier_service_id'])) {
            throw new \Exception("courier_service_id not found");
        }
        $courierServiceAccount = CourierServiceAccount::getByAccountAndCourierService($request['account_id'], $request['courier_service_id']);

        $shipments = $courierServiceAccount->getShipmentFromOrderRef($request['order_ref']);

        foreach ($shipments as $shipment) {
            $ship = new ShipmentDetail([$shipment['id']]);
            $courierShortCode = $ship->getCourierShortCode();
            $trackId = $ship->model->getCourierServiceReferenceNumber();
            $trackingIds[$courierShortCode][] = $trackId;
        }

        $courierResponse = [];
        foreach ($trackingIds as $shortCode => $trackId) {
            $courierName = '\Controllers\Couriers\\' . ucfirst(array_search($shortCode, $this->shortCodeMap));
            try {
                $courierResponse = $courierName::trackShipment($trackId);
                print_r($courierResponse);
                // die;
                $courierResponse = reset($courierName::trackShipment([$awb]));
                $ship->model->setStatus($courierResponse['status']);
                $ship->model->save();
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return [
            'Status' => 'SUCCESS',
            'message' => 'Shipment status is ' . $courierResponse['status'] . '.',
            'data' => $courierResponse
        ];
    }

    /**
     * Function to get shipment details object using order ref and courierServiceAccountId
     * @param string $orderRef
     * @param string $courierServiceAccountId
     * @return type
     */
    public static function getFromOrderRefCourierServiceAccount($orderRef, $courierServiceAccountId)
    {
        $model = self::getModelClass();
        $modelObj = new $model;

        $shipments = $modelObj->getByParam([
            'order_ref' => $orderRef,
            'courier_service_account_id' => $courierServiceAccountId
        ]);
        return $shipments;
    }

    /**
     * Function to assign awb for multiple bookings from seller end.
     * @param mixed $request containing an array of orders containing the order_ref, account_id and courier_service_id. can be multiple.
     * @return mixed $response containing the awb assigned and courier service used and the status(PENDING).
     */
    public function assignAwbSellerUpload($request)
    {
        $response = [];
        foreach ($request as $order) {
            $checkedData = $this->checkFields($order);
            $orderRef = $order['order_ref'];
            $courierService = new CourierService([$checkedData['courier_service_id']]);

            $courierServiceAccount = CourierServiceAccount::getByParams([
                'account_id' => $checkedData['account_id'],
                'courier_service_id' => $checkedData['courier_service_id'],
                'awb_batch_mode' => 'USER'
            ]);
            if ($courierServiceAccount) {
                $awbDetail = $courierServiceAccount->getAWB();
                $checkedData['courier_service_account_id'] = $courierServiceAccount->getId();

                $checkedData['courier_service_details'] = '';
                $checkedData['courier_service_reference_number'] = $awbDetail['awb'];
                $checkedData['shipment_type'] = 'FORWARD';
                $response[]  = $this->addShipmentTODB($checkedData, $awbDetail['awb']);
            } else {
                $response[] = [
                    'status' => 'FAILED',
                    'MSG' => 'No AWb batch found'
                ];
            }
        }
        return $response;
    }

    /**
     * Function to update the shipment status.
     * @param string $status the status to be set
     * @return void
     */
    public function updatedStatus($status)
    {
        $this->model->setStatus($status);
        $this->model->save();
    }

    /**
     * Function get the model instance by id
     * @param string $id
     * @return mixed the model instance corresponding to the given id
     */
    public function getById($id)
    {
        $shipmentDetails = new ShipmentDetailModel($id);
        return $shipmentDetails;
    }

    /**
     * Function to get short_code for the courier company using the courier service account id
     * @return string $return the short code for the courier company
     */
    public function getCourierShortCode()
    {
        $courierServiceAccount = new CourierServiceAccount([$this->model->getCourierServiceAccountId()]);
        return $courierServiceAccount->getCourierCompanyShortCode();
    }
}
