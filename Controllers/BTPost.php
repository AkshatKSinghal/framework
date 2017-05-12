<?php
// namespace Controllers;

require _DIR_ . '/../vendor/autoload.php';
   require _DIR_ . '/../vendor/malkusch/php-autoloader/autoloader.php';
   require _DIR_ . "/../config.php";
/**
 * Controller for all external communications of the BTPost System
 *
 */

class BTPost
{
    private $accountID;

    public function __construct($accountID)
    {
        $this->accountID = $accountID;
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
            'name' => 'gati',
            'short_code' => '123456',
            'comments' => '$comments',
            'logo_url' => '$logoURL',
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
        ]);
        return $companyServiceId;
    }

    public function createCourierServiceAccount()
    {
        $ship = new \Controllers\CourierServiceAccount([]);
        return ($ship->create([
            'account_id' => '12',
            'courier_service_id' => '15',
            'awb_batch_mode' => 'ADMIN',
            'credentials' => [
                'code' => '54655501',
                'cust_vend_code'=>'100001',
            ],
            'pincodes' => '7',
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

    public function bookShipment()
    {
        $ship = new \Controllers\ShipmentDetail([]);
        return $ship->bookShipment([
            'order_ref' => '500000013',
            'account_id' => '12',
            'pickup_address' => [
                'name' => 'Pickup contact person name',
                'text' => '#301, Some Road Name, City Name',
                'landmark' => 'landmark text (optional)',
                'time' => 'epoch timestamp',
                'phone' => '9876543210',
                'pincode' => '110052',
                'email_id' => 'email id to be notified with updates',
                'state'=> 'Goa',
                'country'=> 'India'
            ],
            'drop_address' => [
                'name' => 'Drop contact person name',
                'pincode' => '500021',
                'text' => '#301, Some Road Name, City Name',
                'phone' => '9876543210',
                'landmark' => 'landmark text (optional)',
                'state'=> 'Goa',
                'country'=> 'India'
            ],
            'shipment_details' => [
                'orders' => [
                    [
                        'items' => [
                            [
                                'price'=> '1200.23',
                                'sku_id' => 'A152AFD',
                                'quantity' => '2',
                                'description' => 'item description (optional)'
                            ], [
                                'price'=> 'asdasd',
                                'sku_id' => 'A152AFD',
                                'quantity' => '2',
                                'description' => 'item description (optional)'
                            ]
                        ],
                        'invoice' => [
                            'ref_id' => '2017-18/ABC123',
                            'value' => '400.26',
                            'date' => '2017-04-03'
                        ]
                    ]
                ],
                'length' => '20',
                'breadth' => '30',
                'height' => '24',
                'weight' => '350',
                'tin' => '02513642510',
                'type' => 'forward',
                'reason' => 'reverse pickup reason'
            ],
            'cod_value' => '120',
            'courier_service_id' => '15',
            'awb' => '10',
        ]);
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
}
