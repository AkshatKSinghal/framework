<?php
/**
 * Controller responsible for management and use of AWBs
 * 
 */
namespace Controllers;

// #TODO handle case of  0000 getting converted to 0 in db
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
	 * @return void
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
		$this->model->save();
		$this->saveToPersistentStore($filePath, self::UPLOAD);
		$this->processFile();
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
		while(!feof($fp)) {
			$awb = trim(fgets($fp));
			$type = self::VALID;
			if (CacheManager::existsInSet($existingBatchesSet, $awb)) {
				$type = self::INVALID;
				$awb = $awb . FS . "DUPLICATE";
			}
			$fileName = $type . "AWBFile";
			$counter = $type . "Count";
			file_put_contents($$fileName, $awb . PHP_EOL, FILE_APPEND);
			$$counter++;
		}
		fclose($fp);
		$courier = new \Controllers\CourierCompany($this->model->getCourierCompanyId());
		// #TODO
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
		// #TODO get status from DB #done
 		$status = $this->model->getStatus();
		if ($status == $operation) {
			throw new \Exception("Operation already running");
		} else if ($status != $allowedState) {
			throw new \Exception("Cannot obtain lock for $operation operation from $status state");
		}
		// #TODO update status to $operation #done
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
		$key = $this->getRedisSetKey('available');
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
		$this->model->updateAvailableCount(-1);
		$this->model->updateAssignedCount(1);
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
	    // var_dump(debug_backtrace());
		$this->getLock('UPDATING', 'PROCESSED');
		// $this->getAWB();

		$fileTypes = array(self::AVAILABLE, self::ASSIGNED, self::FAILED);
		$used = array();
		foreach ($fileTypes as $fileType) {
			$$fileType = $this->getFromPersistentStore($fileType);
			$used[$fileType] = array();
		}
		// #TODO Handle too large log file
		// #TODO process the log file abd update to S3
		$file = fopen('/home/browntape/Desktop/btpost/log.txt', 'r');
		$availableFile = fopen($available, 'r');
		$assignedFile = fopen($assigned, 'w');
		$failedFile = fopen($failed, 'w');
		if ($availableFile) {
			$availableArray = explode("\n", fread($availableFile, filesize($available)));
		}
		$logAwb = [];
		while (!feof($file)) {
			$row = fgets($file);
			$row = trim($row);
			if ($row != '') {
				$rowData = explode('|', $row);
				// string:  time|awb|event;
				$logAwb[trim($rowData[1])] = trim($rowData[2]);
			}				
		}
		echo 'available';
		var_dump($availableArray);
		echo 'log';
		var_dump($logAwb);
		foreach ($logAwb as $awb => $event) {
			echo 'log awb:';
			var_dump(trim($awb));
			echo 'search '. array_search(trim($awb), $availableArray);
			echo 'search in array '. in_array(trim($awb), $availableArray);
			if (array_search($awb, $availableArray)) {
				switch ($event) {
					case 'used':
						$insertFileName = $assigned;
						break;
					case 'failed':
						$insertFileName = $failed;
						break;
				}
				echo 'file name';
				echo $insertFileName;

				file_put_contents($insertFileName, $awb . PHP_EOL, FILE_APPEND);
			}			
		}
		foreach ($fileTypes as $fileType) {
			$this->saveToPersistentStore($$fileType, $fileType);
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

	private function logAWBEvent($event, $awb)
	{
		file_put_contents($this->getLogFile(), time() . FS . $awb . FS . $event . PHP_EOL, FILE_APPEND);
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
		// #TODO Move the S3 code to separate class
		// shell_exec("aws s3 cp $filePath $remoteFilePath");
		shell_exec("cp $filePath $remoteFilePath");
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
		$localFilePath = TMP . DS . $this->model->getId() . $type . '.txt';
		// shell_exec("s3 cp $remoteFilePath $localFilePath");
		shell_exec("cp $remoteFilePath $localFilePath");
		// #TODO Check if the copy was successful, else throw exception
		if (!file_exists($localFilePath)) {
			// throw new S3Exception("S3 Copy file not successful " . $localFilePath);
		}
		return $localFilePath;
	}

	private function loadExistingBatches()
	{
		$batches = $this->model->findByCourier();
		//findByCourier has to return all batches according to the courierID and accountID
		// $batches = $this->AWBBatch->find('all', //condition for processed AWB batches created within x time for the same courier company and account id);
		//not pending
		$proccessingSetName = $this->getRedisSetKey("processing");
		foreach($batches as $AWBBatchId) {
			$AWBBatch = new AWBBatch([$AWBBatchId]);
			$AWBBatch->loadFile($proccessingSetName);	
		}
		return $proccessingSetName;
	}

	private function getTempFile($type)
	{
		$dir = TMP . "/temp/";
		FileManager::verifyDirectory($dir);
		$filename = $this->model->getId() . $type . '.txt';
		
		return $dir . $filename;
	}

	private function getS3Path($type)
	{
		// return "s3://btpost/awb/$type/{$this->model->getId()}.txt";
		FileManager::verifyDirectory(TMP . "/$type");
		return TMP . "/$type/{$this->model->getId()}.txt";
	}

	private function getRedisSetKey($type)
	{
		return "{$this->model->getId()}_$type";
	}

	private function getLogFile()
	{
		$dir = TMP . "/logs/";
		FileManager::verifyDirectory($dir);
		return $dir . "{$this->model->getId()}.log";
	}
}

/**
* 
*/
class S3Exception extends \Exception
{

}