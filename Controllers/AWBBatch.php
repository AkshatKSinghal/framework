<?
/**
 * Controller responsible for management and use of AWBs
 * 
 */
#TODO Create constant FS
class AWBBatch extends Base
{
	private var $redis = null;
	const UPLOAD = 'upload';
	const INVALID = 'invalid';
	const VALID = 'valid';
	const AVAILABLE = 'available';
	const ASSIGNED = 'assigned';
	const FAILED = 'failed';
	const FILE_PATH = '#TODO DEFINE THE PATH';


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
	 */

	function __construct($filePath, $courierCompanyID, $accountID = 0)
	{
		if (!is_file($filePath)) {
			throw new Exception("File not found at given path $filePath");
		}
		if (filesize($filePath) == 0) {
			throw new Exception("File empty at path $filePath");
		}
		#TODO Create entry in the DB
		$this->model = '#TODO Create entry in DB';
		$this->saveToPersistentStore($filePath, self::UPLOAD);
	}


	/**
	 * Function to process the AWB batch and create the valid and invalid list 
	 * @throws Exception in case the batch is already being processed
	 */

	public function processFile()
	{
		$this->getLock('PROCESSING', 'PENDING');
		$file = $this->getFromPersistentStore(self::UPLOAD);
		$existingBatchesSet = $this->loadExistingBatches();
		$fp = fopen($file, 'r');
		$validAWBFile = $this->getTempFile(self::VALID);
		file_put_contents($validAWBFile, '');
		$invalidAWBFile = $this->getTempFile(self::INVALID);
		file_put_contents($validAWBFile, '');
		$validCount = 0;
		$invalidCount = 0;
		while($awb = fread($fp)) {
			$awb = trim($awb);
			$type = self::VALID;
			if ($this->redis->existsInSet($existingBatchesSet, $awb)) {
				$type = self::INVALID;
				$awb = $awb . FS . "DUPLICATE";
			}
			$fileName = $type . "AWBFile";
			$counter = $type . "Count";
			file_put_contents($fileName, $awb . PHP_EOL, FILE_APPEND);
			$counter++;
		}
		$courier = new CourierCompany($this->getCourierCompanyId());
		$awbFile = $courier->validateAWBFile($validAWBFile, $errorFile);
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
		#TODO get status from DB
		if ($status == $operation) {
			throw new Exception("Operation already running");
		} else if ($status != $allowedState) {
			throw new Exception("Cannot obtain lock for $operation operation from $status state");
		}
		#TODO update status to $operation
	}


	/**
	 * Function to mark the status of the batch as processed (this is not required, should be in model)
	 * 
	 * @return void
	 */

	private function markProcessed($validCount = null, $invalidCount = null)
	{
		#TODO update status to PROCESSED, count of valid, invalid if not null
	}


	/**
	 * Function to get the file from S3 and load into redis set.
	 * @param $type string (optional) default value
	 * @param $customKey string (optional) name of the set to be loaded into
	 * @throws Exception in case the AWB file fetch fails
	 */ 

	public function loadFile($type = self::AVAILABLE, $customKey = null)
	{
		// Update the available AWB list with the existing log, if any
		if ($type == self::AVAILABLE) {
			$this->updatePersistentStore();
		}

		$key = ($customKey != null) ? $customKey : $this->getRedisSetKey($type);

		$localFilePath = $this->getFromPersistentStore($valid);
		$fp = fopen($localFilePath, 'r');
		while ($awb = fgets($fp)) {
			$this->redis->addToSet($key, trim($awb));
		}
	}

	public function getAWB()
	{
		$key = $this->getRedisSetKey('available');
		if (!$this->redis->exists($key) || ($this->redis->sCard($key) == 0 && $this->model->getAvailableCount() != 0)) {
			$this->loadFile();
		}
		$awb = $this->redis->sPop($key);
		if ($awb === false) {
			throw new Exception("No AWBs available in Batch");
		}
		$this->logAWBEvent('used', $awb);
	}

	public function updatePersistentStore()
	{
		// Lock mechanism to avoid issue due to multiple processes working on the same batch
		$this->getLock('UPDATING', 'PROCESSED');
		#TODO process the log file abd update to S3
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
		shell_exec("s3 cp $filePath $remoteFilePath");
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
		$localFilePath = TMP . DS . $this->model->getId() . $type . rand();
		// #TODO Move the S3 code to separate class
		shell_exec("s3 cp $remoteFilePath $localFilePath");
		// Check if the copy was successful, else throw exception
		return $localFilePath;
	}

	private function redisInstance()
	{
		if ($this->redis == null) {
			// Replace this function with correct function
			$this->redis = getRedisInstance();
			if ($this->redis == null) {
				throw new Exception("Unable to connect to Redis", 400);
			}
		}
		return $this->redis;
	}

	private function loadExistingBatches()
	{
		$batches = $this->AWBBatch->find('all', //condition for processed AWB batches created within x time for the same courier company and account id);
		$proccessingSetName = $this->getRedisSetKey("processing");
		foreach($batches as $AWBBatch) {
			$AWBBatch->loadFile($proccessingSetName);
		}
		return $proccessingSetName;
	}

	private function getTempFile($type)
	{
		return TMP . DS . $this->model->getId() . $type . rand();
	}

	private function getS3Path($type)
	{
		return "s3://btpost/awb/$type/{$this->model->getId()}.txt";
	}

	private function getRedisSetKey($type)
	{
		return "{$this->model->getId()}_$type";
	}

	private function getLogFile()
	{
		return self::FILE_PATH . "{$this->model->getId()}.log";
	}
}

/**
* 
*/
class S3Exception extends Exception
{

}