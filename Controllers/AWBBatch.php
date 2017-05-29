<?php
/**
 * Controller responsible for management and use of AWBs
 *
 */
namespace Controllers;

// #TODO handle case of  0000 getting converted to 0 in db
// #TODO Cleanup created temp files once used
use \Model\AWBBatch as AWBBatchModel;
use \Controllers\Base as BaseController;
use \Cache\CacheManager as CacheManager;
use \Utility\FileManager as FileManager;

class AWBBatch extends BaseController
{
    const UPLOAD = 'upload';
    const INVALID = 'invalid';
    const VALID = 'valid';
    const AVAILABLE = 'available';
    const ASSIGNED = 'assigned';
    const FAILED = 'failed';
    const EVENT_USED = 'used';
    const EVENT_FAILED = 'failed';
    /**
     * function to return the Cache instance
     * @return CacheManager Object
     */
    private static function redis()
    {
        return CacheManager::getInstance();
    }
    
    /**
     * Create a new AWB Batch record in the DB and process the AWB files.
     *
     * @param string $filePath Local path of the file on disk
     * @param string $courierCompanyID Couier Company for which the
     * AWB Batch is being created
     * @param string $accountID Account ID for which the
     * AWB Batch is to be created [optional; default 0]
     *
     * @throws Exception in case the $courierCompanyID is invalid
     * @throws Exception in case the $filePath is invalid/ empty
     *
     * @return batchId created
     *
     */

    public function createBatch($filePath, $courierCompanyID, $accountID = 0)
    {
        if (!is_file($filePath)) {
            throw new \Exception("File not found at given path $filePath");
        }
        if (filesize($filePath) == 0) {
            throw new \Exception("File empty at path $filePath");
        }

        $this->model = new AWBBatchModel();
        $this->model->setCourierCompanyId($courierCompanyID);
        $this->model->setAccountId($accountID);
        $this->model->setTotalCount(FileManager::lineCount($filePath));
        $this->model->setValidCount(FileManager::lineCount($filePath));
        $this->model->setInvalidCount(0);
        $this->model->setFailedCount(0);
        $this->model->setAssignedCount(0);
        $this->model->setStatus('PENDING');
        $this->model->setAvailableCount(FileManager::lineCount($filePath));
        $this->model->save();
        $this->saveToPersistentStore($filePath, self::UPLOAD);
        $this->processFile();


        return $this->model->getId();
    }

    // public function mapWithCourier($request)
    // {
    //     if (!isset($request['awbBatchId'])) {
    //         throw new \Exception("AWb batch id not found", 1);
    //     }
    //     if (!isset($request['courierServiceAccuntId'])) {
    //         throw new \Exception("Courier Service Account id not found", 1);
    //     }
    //     $courierServiceAccount = new CourierServiceAccount([$request['courierServiceAccuntId']]);
    //     $courierServiceAccount->mapAWBBatch($request['awbBatchId']);
    // }
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
        #TODO Add handling for huge files
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_unique($lines);
        file_put_contents($filePath, implode(PHP_EOL, $lines));
    }


    /**
     * Function to process the AWB batch and create the valid and invalid list
     *
     * @throws Exception in case the batch is already being processed
     *
     * @return void
     */

    public function processFile()
    {
        // #TODO need to think of lock at account and courier level.
        $this->getLock('PROCESSING', 'PENDING');
        $file = $this->getFromPersistentStore(self::UPLOAD);
        // within the file duplicates check
        $existingBatchesSet = $this->loadExistingBatches();
        $this->basicValidateFile($file);
        $validAWBFile = $this->getTempFile(self::VALID);
        file_put_contents($validAWBFile, '');
        $invalidAWBFile = $this->getTempFile(self::INVALID);
        file_put_contents($invalidAWBFile, '');
        $validCount = 0;
        $invalidCount = 0;
        $fp = fopen($file, 'r');
        $awb = '';
        while (!feof($fp)) {
            $awb = trim(fgets($fp));
            $type = self::VALID;
            if (CacheManager::existsInSet($existingBatchesSet, $awb)) {
                $type = self::INVALID;
                $awb = $awb . btpFS . "DUPLICATE";
            }
            $fileName = $type . "AWBFile";
            $counter = $type . "Count";
            file_put_contents($$fileName, $awb . PHP_EOL, FILE_APPEND);
            $$counter++;
        }
        fclose($fp);
        // $courier = new \Controllers\CourierCompany($this->model->getCourierCompanyId());
        // #TODO: Call validation in Courier Company
        // $awbFile = $courier->validateAWBFile($validCount, $invalidAWBFile);
        $validCount = FileManager::lineCount($validAWBFile);
        $invalidCount = FileManager::lineCount($invalidAWBFile);
        $this->saveToPersistentStore($validAWBFile, self::VALID);
        $this->saveToPersistentStore($validAWBFile, self::AVAILABLE);
        $this->saveToPersistentStore($invalidAWBFile, self::INVALID);
        $this->markProcessed($validCount, $invalidCount);
    }

    /**
     * Function to obtain lock for processing the uploaded AWB file/
     * uploading to persistent store
     *
     * @param string $operation Operation for which the lock is being obtained
     * @param string $allowedState Status on which the lock can be obtained
     *
     * @throws Exception if the lock is already in use
     * i.e. the batch status is already same as $operation
     * @throws Exception if the lock cannot be obtained on the current state
     * i.e. the batch status is not $allowedState
     *
     * @return void
     */

    private function getLock($operation, $allowedState)
    {
        $status = $this->model->getStatus();
        if ($status == $operation) {
            throw new \Exception("Operation already running");
        } elseif ($status != $allowedState) {
            throw new \Exception("Cannot obtain lock for $operation operation from $status state");
        }
        // print_r($this->model);
        $this->model->setStatus($operation);
        $this->model->save();
    }


    /**
     * Function to mark the status of the batch as processed
     * and update the count of valid, invalid and available AWBs
     * in case of initial processing
     *
     * @return void
     */

    private function markProcessed()
    {
        if ($this->model->getStatus() == 'PROCESSING') {
            $validCount = FileManager::lineCount($this->getTempFile(self::VALID));
            $invalidCount = FileManager::lineCount($this->getTempFile(self::INVALID));
            $this->model->setValidCount($validCount);
            $this->model->setAvailableCount($validCount);
            $this->model->setInvalidCount($invalidCount);
        }
        $this->model->setStatus('PROCESSED');
        $this->model->save();
    }


    /**
     * Function to get the file from S3 and load into redis set.
     *
     * @param $type string (optional) default value
     * @param $customKey string (optional) name of the set to be loaded into
     *
     * @throws Exception in case the AWB file fetch fails
     *
     * @return void
     */

    public function loadFile($customKey = null, $type = self::AVAILABLE)
    {
        // Update the available AWB list with the existing log, if any
        if ($type == self::AVAILABLE) {
            $this->updatePersistentStore();
        }
        $customKey = ($customKey != null) ? $customKey : $this->getRedisSetKey($type);
        $localFilePath = $this->getFromPersistentStore($type);
        $fp = fopen($localFilePath, 'r');
        $awbSet = [];
        $i = 0;
        while ($awb = trim(fgets($fp))) {
            $awbSet[] = $awb;
            if ($i == 1000) {
                CacheManager::addToSet($customKey, $awbSet);
                $i = 0;
                $awbSet = [];
            }
            $i++;
        }
        if ($i !=0) {
            CacheManager::addToSet($customKey, $awbSet);
            $i = 0;
            $awbSet = [];
        }
    }


    /**
     * Function to get AWB from a given AWB Batch
     *
     * @throws Exception in case no AWBs are available in the batch
     * @throws Exception in case no AWBs are available in Redis
     *
     * @return string $awb AWB from the batch
     */
    public function getAWB()
    {
        if ($this->model->getStatus() != 'PROCESSED') {
            throw new \Exception("AWB Batch currently in " . $this->model->getStatus() . " state. Cannot allocate AWB.");
        }
        $key = $this->getRedisSetKey(self::AVAILABLE);
        if ($this->model->getAvailableCount() == 0) {
            throw new \Exception("No AWBs available in Batch");
        }
        if ($this->redis()->sCard($key) == 0) {
            $this->loadFile();
        }
        $awb = $this->redis()->sPop($key);
        if ($awb === false) {
            throw new \Exception("No AWBs available in Redis");
        }
        $this->logAWBEvent(self::EVENT_USED, $awb);
        $this->model->setAvailableCount(-1, 'UPDATE');
        $this->model->setAssignedCount(1, 'UPDATE');
        $this->model->save();
        return $awb;
    }


    /**
     * Function to update the files in persistent store using the log
     *
     * @throws Exception in case the operation is already running for the batch
     *
     * @return void
     */

    private function updatePersistentStore()
    {
        // Lock mechanism to avoid issue due to multiple processes working on the same batch
        $this->getLock('UPDATING', 'PROCESSED');

        // #TODO Handle too large log file
        $logAwb = [];
        $fp = fopen($this->getLogFile(), 'r');
        while (!feof($fp)) {
            $row = trim(fgets($fp));
            if (empty($row)) {
                continue;
            }
            $rowData = explode(btpFS, $row);
            if (!isset($rowData[1]) || !isset($rowData[2])) {
                #TODO Log this
                continue;
            }
            $logAwb[trim($rowData[1])] = trim($rowData[2]);
        }
        fclose($fp);
        $files = array();
        $fileTypes = array(self::AVAILABLE, self::ASSIGNED, self::FAILED);
        foreach ($fileTypes as $fileType) {
            $files[$fileType] = $this->getFromPersistentStore($fileType);
        }
        $available = fopen($files[self::AVAILABLE], 'r');

        $files[self::AVAILABLE] = $this->getTempFile(self::AVAILABLE);
        while (!feof($available)) {
            $awb = trim(fgets($available));
            if (empty($awb)) {
                continue;
            }
            $type = isset($logAwb[$awb]) ? $logAwb[$awb] : self::AVAILABLE;
            $fileType = $type == 'used'? 'assigned' : $type;
            // if ($type == 'used') {
            //     $type = 'assigned';
            // }
            file_put_contents($files[$fileType], $awb . PHP_EOL, FILE_APPEND);
        }
        fclose($available);
        foreach ($fileTypes as $fileType) {
            $this->saveToPersistentStore($files[$fileType], $fileType);
        }
        $this->markProcessed();
    }


    /**
     * Log the AWB and associated event
     *
     * @param string $event Event associated with the AWB
     * @param string $awb AWB used/ failed etc
     *
     * @return void
     */

    public function logAWBEvent($event, $awb)
    {
        file_put_contents($this->getLogFile(), time() . btpFS . $awb . btpFS . $event . PHP_EOL, FILE_APPEND);
    }


    // #TODO Move the S3 code to separate class
    /**
     * Update the persistent store (S3) with the latest list.
     *
     * @param string $filePath Path of the file containing the AWBs
     * @param string $type Type of AWB set, can be upload, valid,
     * invalid, avialable, used, failed
     *
     * @throws S3Exception if the file copy failed
     *
     * @return void
     */

    private function saveToPersistentStore($filePath, $type)
    {
        $remoteFilePath = $this->getS3Path($type);
        shell_exec("aws s3 cp $filePath $remoteFilePath");
        // shell_exec("cp $filePath $remoteFilePath");
        // Check if the copy was successful, else throw exception
    }

    /**
     * Get the list of AWBs for given type from persistentStore (S3)
     *
     * @param string $type Type of AWBs to be retrieved from persistent store
     *
     * @throws S3Exception if the file copy failed
     * @return string $localFilePath
     */
    private function getFromPersistentStore($type)
    {
        $remoteFilePath = $this->getS3Path($type);
        $localFilePath = $this->getLocalPath($type);
        shell_exec("aws s3 cp $remoteFilePath $localFilePath");
        // shell_exec("cp $remoteFilePath $localFilePath");
        // #TODO Check if the copy was successful, else throw exception
        if (!file_exists($localFilePath)) {
            touch($localFilePath);
            // throw new S3Exception("S3 Copy file not successful " . $localFilePath);
        }
        return $localFilePath;
    }

    /**
     * Function to load awbs from existing awbBatches into cache
     * @return string $processingSetName cache set name where the awbs are loaded
     */
    private function loadExistingBatches()
    {
        $batches = $this->model->findByCourier();
        $proccessingSetName = $this->getRedisSetKey("processing");
        foreach ($batches as $AWBBatchId) {
            $AWBBatch = new AWBBatch([$AWBBatchId]);
            $AWBBatch->loadFile($proccessingSetName);
        }
        return $proccessingSetName;
    }

    private function getTempFile($type)
    {
        $dir = btpTMP . "/temp/";
        FileManager::verifyDirectory($dir);
        $filename = $this->model->getId() . $type . '.txt';
        
        return $dir . $filename;
    }

    private function getLocalPath($type)
    {
        $dir = btpTMP . "/local/";
        FileManager::verifyDirectory($dir);
        $filename = $this->model->getId() . $type . '.txt';
        return $dir . $filename;
    }

    private function getS3Path($type)
    {
        return "s3://btpost/awb/$type/{$this->model->getId()}.txt";
        // FileManager::verifyDirectory(btpTMP . "/s3/$type");
        // return btpTMP . "/s3/$type/{$this->model->getId()}.txt";
    }

    private function getRedisSetKey($type)
    {
        return "{$this->model->getId()}_$type";
    }

    private function getLogFile()
    {
        $dir = btpTMP . "/logs/";
        FileManager::verifyDirectory($dir);
        $filePath = $dir . "{$this->model->getId()}.log";
        if (!file_exists($filePath)) {
            touch($filePath);
        }
        return $filePath;
    }

    /**
     * Function to update awbBatches model in case of a failed awb assignment from the courier side.
     * @return void
     */
    public function updateTableForFailedAwb()
    {
        $this->model->setFailedCount($this->model->getFailedCount() + 1);
        $this->model->setAssignedCount($this->model->getAssignedCount() - 1);
        $this->model->save();
    }

    /**
     * Function to map or unmap a particular batch with courier_service_account_id.
     * @param string $operation operation to be performed (set/add/remove)
     * @param mixed $courierServiceArray array of courierServiceIds to be used to find courierServiceAccountId
     * @param int $accountId account to be used to find out courierServiceAccountId
     * @return mixed array of status and meta data
     */
    public function mapUnmapCourierService($operation, $courierServiceArray, $accountId)
    {
        $response = [];
        if (!in_array($operation, ['set', 'add', 'remove'])) {
            return [
                'status' => 'FAILED',
                'message' => 'Invalid operation' . $operation
            ];
        }

        $checkFields = $this->checkMapCourierFields($courierServiceArray, $accountId, $this->model->getId());
        if ($checkFields['status']) {
            return [
                'status' => 'FAILED',
                'message' => $checkFields['reason']
            ];
        }

        foreach ($courierServiceArray as $courierServiceId) {
            $courierServiceAccount = CourierServiceAccount::getByAccountAndCourierService($accountId, $courierServiceId);
            $courierServiceAccount->mapAWBBatch($this->model->getId(), $operation);
            $response['courier_service_mapped'][] = [
                'service_name' => $courierServiceAccount->getCourierCompanyName(),
                'ref_id' => $courierServiceId
            ];
        }
        $response['total'] = $this->model->getTotalCount();
        $response['valid'] = $this->model->getValidCount();
        $response['invalid'] = $this->model->getInvalidCount();
        $response['ref_id'] = $this->model->getId();
        $response['available'] = $this->model->getAvailableCount();
        $response['assigned'] = $this->model->getAssignedCount();
        $response['failed'] = $this->model->getFailedCount();
        return [
            'status' => 'success',
            'message' => 'Operation successful',
            'data' => $response
        ];
    }

    /**
     * Function to validate the courier service ids and account and also to check if a batch maps to the given accountId and courierService
     * @param mixed $courierServiceArray array of courierServiceIDs
     * @param int $accountId account_id to be check against
     * @return mixed array containing status and reason
     */
    private function checkMapCourierFields($courierServiceArray, $accountId)
    {
        $courierCompanyArray = [];
        foreach ($courierServiceArray as $courierServiceId) {
            try {
                $courierService = new CourierService([$courierServiceId]);
                $courierCompanyArray[] =  $courierService->getCourierCompany();
            } catch (\Exception $e) {
                return [
                    'status' => false,
                    'reason' => 'Invalid Courier Service Id'
                ];
            }
        }

        if (count($courierCompanyArray) > 1) {
            return [
                'status' => false,
                'reason' => 'More than one Courier Company found for the given service id'
            ];
        }

        if ($this->model->getCourierCompanyId() != $courierCompanyArray[0] || $this->model->getAccountId() != $accountId) {
            // awb not for given account id and service id;
            return [
                'status' => false,
                'reason' => 'More than one Courier Company found for the given service id'
            ];
        }
        return [
            'status' => true
        ];
    }
}

/**
*
*/
class S3Exception extends \Exception
{
}
