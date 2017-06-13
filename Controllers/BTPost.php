<?php
// namespace Controllers;


require __DIR__ . '/../vendor/autoload.php';
// require_once/* __DIR__ . */'/var/www/html/btapp/app/Vendor/btpost/vendor/malkusch/php-autoloader/autoloader.php';
require __DIR__ . '/../vendor/malkusch/php-autoloader/autoloader.php';
require_once __DIR__ . "/../constants.php";
/**
 * Controller for all external communications of the BTPost System
 *
 */

class BTPost
{
    private $accountID;

    public function __construct()
    {
        $config = require_once(__DIR__ . '/../config.php');

        foreach ($config as $class => $conf) {
            if (class_exists($class)) {
                // $classInst = new $class();
                // $classInst->setStdConfig($conf);
                $class::setStdConfig($conf);
            }
        }
    }

    /**
     * Function to upload AWB batch into a courier company
     *
     * @param string $filePath Path of file on the disk
     *
     * @return mixed $response AWB Batch Number if file is valid
     * Error message in case the file has errors
     * Error message in case Courier Company ID is invalid
     */
    public function uploadAWB($filePath, $courierCompanyID, $accountID)
    {
        // Check courier company ID, return error if invalid
                // upload awb;
        $batchExecute = new \Controllers\AWBBatch([]);
        $batchId = $batchExecute->createBatch($filePath, $courierCompanyID, $accountID);
        return $batchId;
    }


    /**
     * Function to perform basic validations on the file
     * i.e. Duplicates within the file, Invalid Characters, Empty lines etc
     *
     * @param string $filePath Path of the file on disk
     *
     * @throws Exception in case the file contains duplicates or invalid characters
     *
     * @return void
     */
    private function basicValidateFile($filePath)
    {
        #TODO Check for duplicates, throw exception in case duplicates within file are found
    }


    #TODO Put complete list of required parameters
    /**
     * Function to create a new courier company
     *
     * @param string $name Name of the courier company
     * @param
     *
     * @return mixed $response Courier company information in case the creation is successful
     * Error message in case the operation is unsuccessful
     */
    public function createCourierCompany($name, $shortCode, $comments, $logoURL)
    {
        $company = new \Controllers\CourierCompany([]);
        $companyId = $company->create([
            'name' => $name,
            'short_code' => $shortCode,
            'comments' => $comments,
            'logo_url' => $logoURL,
            'status' => 'ACTIVE',
        ]);
        return $companyId;
    }

    public function createCourierService($courierCompanyId, $credentials, $pincodes, $settings, $status, $serviceType, $orderType)
    {
        $company = new \Controllers\CourierService([]);
        $companyServiceId = $company->create([
            'courier_company_id' => $courierCompanyId,
            'credentials_required_json' => $credentials,
            'pincodes' => $pincodes,
            'settings' => $status,
            'service_type' => $serviceType,
            'order_type' => $orderType,
            'status' => 'ACTIVE'
        ]);
        return $companyServiceId;
    }

    public function createCourierServiceAccount($data)
    {
        $ship = new \Controllers\CourierServiceAccount([]);
        return ($ship->create([
            'account_id' => $data['account_id'],
            'courier_service_id' => $data['service_id'],
            'awb_batch_mode' => 'USER',
            'credentials' => [],
            'pincodes' => '',
            'status' => 'ACTIVE',
        ]));
    }

    public function getAccountId()
    {
        return $this->accountID;
    }


    /**
     * Function to track shipment via BTPost Reference ID
     *
     * @param string $refId BTPost Reference ID
     *
     * @return mixed $response Tracking information as returned from trackShipmentViaAWB
     * @see trackShipmentViaAWB
     */
    public function trackShipment($refId)
    {
        #TODO check if the reference number belongs to the account
        #TODO get AWB and Courier Company ID
        //
        $ship = new \Controllers\ShipmentDetail([]);
        $statusArray = $ship->trackShipment([
            'ref_id' => $refId
        ]);

        return $statusArray;
    }

    /**
     * API to track the AWB for given courier company
     *
     * @param string $awb AWB to be tracked
     * @param string $courierCompanyID ID of the courier company
     * @param string $orderRef order_ref for the shipment
     *
     * @return mixed $reponse Tracking information for the AWB number
     */
    private function trackShipmentViaAWB($accountId, $courierCompanyID, $orderRef)
    {
        #TODO Create courierCompany instance
        $ship = new \Controllers\ShipmentDetail([]);
        $statusArray = $ship->trackShipmentByRef([
            'account_id' => $accountId,
            'order_ref' => $orderRef,
            'courier_service_id' => $courierCompanyID
        ]);
        return $statusArray;
    }

    public function addShipment($orderData, $courierId, $orderType, $serviceType)
    {
        $orderData['courier_service_id'] = $this->getOrCreateCourierService($courierId, $serviceType, $orderType);
        if (!$orderData['courier_service_id']) {
            throw new \Exception("Courier Service Not found");
        }
        // $courierServiceAccountId = $this->getOrCreateCourierAccount($accountId, $orderData['courier_service_id'], 'ADMIN');
        $ship = new \Controllers\ShipmentDetail([]);
        return $ship->addShipmentRequest($orderData);
    }

    public function bookShipment($orderData, $accountId, $courierId, $orderType, $serviceType, $credentials = null)
    {
        $orderData['courier_service_id'] = $this->getOrCreateCourierService($courierId, $serviceType, $orderType);
        $courierServiceAccountId = $this->getOrCreateCourierAccount($accountId, $orderData['courier_service_id'], $credentials, 'ADMIN');
        $ship = new \Controllers\ShipmentDetail([]);
        $res = '';
        $res = $ship->bookShipment($orderData);
        return $res;
    }

    /**
     * FUnction to handle map or unmap batch to courierServiceAccountId request
     * @param string $batchId batch id to be mapped
     * @param string $operation operation to be performed (set, add, remove)
     * @param array $courierServiceArray array of courierServiceIds to be used to find out courierServiceAccount id to be mapped
     * @param string $accountId accountIds to be used to find out courierServiceAccount id to be mapped
     * @return mixed array containing status and meta data
     */
    public function mapUnmapAWbBatches($batchId, $operation, $courierServiceArray, $accountId)
    {
        $batch = new \Controllers\AWBBatch([$batchId]);
        return $batch->mapUnmapCourierService($operation, $courierServiceArray, $accountId);
    }

    public function assignAwbSellerUpload($orderData)
    {
        $ship = new \Controllers\ShipmentDetail([]);
        return $ship->assignAwbSellerUpload($orderData);
        // return $ship->bookShipment([[
        //     'order_ref' => '500000013',
        //     'account_id' => '12',
        //     'pickup_address' => [
        //         'name' => 'Pickup contact person name',
        //         'text' => '#301, Some Road Name, City Name',
        //         'landmark' => 'landmark text (optional)',
        //         'time' => 'epoch timestamp',
        //         'phone' => '9876543210',
        //         'pincode' => '110052',
        //         'email_id' => 'email id to be notified with updates',
        //         'state'=> 'Goa',
        //         'country'=> 'India'
        //     ],
        //     'drop_address' => [
        //         'name' => 'Drop contact person name',
        //         'pincode' => '500021',
        //         'text' => '#301, Some Road Name, City Name',
        //         'phone' => '9876543210',
        //         'landmark' => 'landmark text (optional)',
        //         'state'=> 'Goa',
        //         'country'=> 'India'
        //     ],
        //     'shipment_details' => [
        //         'orders' => [
        //             [
        //                 'items' => [
        //                     [
        //                         'price'=> '1200.23',
        //                         'sku_id' => 'A152AFD',
        //                         'quantity' => '2',
        //                         'description' => 'item description (optional)'
        //                     ], [
        //                         'price'=> 'asdasd',
        //                         'sku_id' => 'A152AFD',
        //                         'quantity' => '2',
        //                         'description' => 'item description (optional)'
        //                     ]
        //                 ],
        //                 'invoice' => [
        //                     'ref_id' => '2017-18/ABC123',
        //                     'value' => '400.26',
        //                     'date' => '2017-04-03'
        //                 ]
        //             ]
        //         ],
        //         'length' => '20',
        //         'breadth' => '30',
        //         'height' => '24',
        //         'weight' => '350',
        //         'tin' => '02513642510',
        //         'type' => 'forward',
        //         'reason' => 'reverse pickup reason'
        //     ],
        //     'cod_value' => '120',
        //     'courier_service_id' => '15',
        // ]]);
    }

    public function checkCourierId($courierName)
    {
        $courier = new \Controllers\CourierCompany([]);
        $courierId = $courier->getOrCreate(['name' => $courierName]);
        return $courierId;
    }

    public function getOrCreateCourierService($courierId, $serviceType, $orderType)
    {
        $courierService = new \Controllers\CourierService([]);
        $courierServiceId = $courierService->getOrCreate([
            'courier_company_id' => $courierId,
            'service_type' => $serviceType,
            'order_type' => $orderType,
        ], false);
        return $courierServiceId;
    }
    
    public function getOrCreateCourierAccount($accountId, $courierServiceId, $credentials, $awbBatchMode = 'ADMIN')
    {
        $courierServiceAccount = new \Controllers\CourierServiceAccount([]);
        $courierServiceAccountId = $courierServiceAccount->getOrCreate([
            'account_id' => $accountId,
            'courier_service_id' => $courierServiceId,
            'awb_batch_mode' => $awbBatchMode,
            'credentials' => $credentials
        ]);
        return $courierServiceAccountId;
    }

    /**
     * Function to get allthe courier services associated with the account id
     * @param string $accountId seller account id to be used to search
     * @return array contaning courier services and other details
     */
    public function getCouriersByAccountId($accountId)
    {
        $courierAccount = new CourierServiceAccount([]);
        $courierAccounts = $courierAccount->getCouriersByAccountId($accountId);
    }

    public function getCouriers($status, $user, $accountId = null)
    {
        if ($user == 'admin') {
            $courierCompany = new \Controllers\CourierCompany([]);
            $adminCompanies = $courierCompany->getAdminCouriers($status);        
        } else {
            $combineAWB = [];
            $courierAccounts = \Controllers\CourierServiceAccount::getByParams(['account_id' => $accountId, 'status' => 'ACTIVE', 'aws_batch_mode' => 'USER']);
            if (!($courierAccounts)) {
                return [];
            }
            foreach ($courierAccounts as $courierAccount) {
                $account = new \Controllers\CourierServiceAccount([$courierAccount['id']]);
                $service = $account->getCourierService($status);
                if (strcasecmp($service->getStatus(), 'active') != 0) {
                    // debug($service);
                    continue;
                }
                $company = $service->getCourierCompany();
                $companyDetails = $company->getDetails();
                if (strcasecmp($companyDetails['status'], 'active') != 0) {
                    debug($companyDetails['id']);
                    continue;
                }
                $serviceDetails = $service->getDetails();
                $awbCount = $codCount = $nonCodCount = 0;
                $batches = $account->getAllAWBBatches();
                foreach ($batches as $batch) {
                    $awbBatchObject = new \Controllers\AWBBatch([$batch]);
                    $awbData['id'] = $batch;
                    $awbData['service_types'] = [$serviceDetails['service_type']];
                    $awbData['timestamp'] = time();
                    $awbData['status'] = $awbBatchObject->getStatus();
                    $awbData['count'] = $awbBatchObject->getAvailableCount();
                    switch ($serviceDetails['order_type']) {
                        case 'cod':
                            $codCount += $awbData['count'];
                            break;

                        case 'prepaid':
                            $nonCodCount += $awbData['count'];
                            break;
                    }
                    $awbCount += $awbData['count'];
                    $combineAWB[$batch] = $awbData;
                }

                if (isset($returnComp[$companyDetails['id']])) {
                    if (isset($returnComp[$companyDetails['id']]['services'][$serviceDetails['id']])) {
                        $returnComp[$companyDetails['id']]['services'][$serviceDetails['id']]['awb'] += $awbCount;
                    } else {
                        $returnComp[$companyDetails['id']]['services'][$serviceDetails['id']] = $serviceDetails;
                    }
                } else {
                    $companyDetails['services'][$serviceDetails['id']] = $serviceDetails;
                    $returnComp[$companyDetails['id']] = $companyDetails;                    
                }

                $returnComp[$companyDetails['id']]['awb_data'][] = $awbData;
                // $adminCompanies[$companyDetails['id']] = $companyDetails; 
                // $adminCompanies[$companyDetails['id']] = $companyDetails; 
            }
            $adminCompanies['couriers'] = $returnComp;
        }
        return $adminCompanies;
    }

    /**
     * Function to add courier and courier service from admin panel
     * @return bool true/false if added or not
     */
    public function addCourierAdmin($request)
    {
        try {
            $courierId = $this->createCourierCompany($request['name'], $request['short_code'], isset($request['comments']) ? $request['comments'] : '', isset($request['logo_url']) ? $request['logo_url'] : '');

            $credentialsRequired = ($request['fields']);
            foreach ($request['services'] as $service) {
                $serviceId = $this->createCourierService($courierId, $credentialsRequired, '', '', [], $service['service_type'], $service['order_type']);
            }            
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());            
        }
        return true;
    }

    /**
     * Function to save the general tab setting for the coourier company
     * @param  $request data from the user panel 
     * @return bool
     */
    public function saveCourierGeneral($request)
    {
        $courier = new \Controllers\CourierCompany([$request['id']]);
        $courier->saveData($request);
    }

    /**
     * Function to update courier services from ui
     * @param mixed $request
     * @return void
     */
    public function updateCourierServices($request)
    {
        $courier = new \Controllers\CourierCompany([$request['id']]);
        $courier->updateServices($request['services']);
    }

    public function uploadAWBFromUi($file, $courierCompanyID, $accountID)
    {
        $destinationDir = btpTMP . '/local/awbs/';
        \Utility\FileManager::verifyDirectory($destinationDir);
        $destination = $destinationDir  . $_FILES['file']['name'];
        move_uploaded_file( $_FILES['file']['tmp_name'] , $destination );
        if (!\Utility\FileManager::validate($destination, 'text')) {
            throw new \Exception("File not valid");            
        }
        $this->uploadAWB($destination, $courierCompanyID,$accountID);
    }
}
