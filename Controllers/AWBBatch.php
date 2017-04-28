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
	const FILE_PATH = '~/Desktop/awb.csv';

	const EVENT_USED = 'used';
	const EVENT_FAILED = 'failed';
	
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
		// $this->model = '#TODO Create entry in DB';
		$this->model = new AWBUpload();
		$this->model->courierCompanyID = $courierCompanyID;
		$this->model->accountID = $accountID;
		$this->model->save();
		$this->saveToPersistentStore($filePath, self::UPLOAD);
		$this->processFile();
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
		#TODO need to think of lock at account and courier level.
		$batchId = $this->model->getId();
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
			$$counter++;
		}
		$courier = new CourierCompany($this->getCourierCompanyId());
		$awbFile = $courier->validateAWBFile($validAWBFile, $invalidAWBFile);
		#TODO update the values of $validCount and $invalidCount after validation by Courier class
		$this->saveToPersistentStore($validAWBFile, self::VALID);
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
	 * 
	 * @param $type string (optional) default value
	 * @param $customKey string (optional) name of the set to be loaded into
	 * 
	 * @throws Exception in case the AWB file fetch fails
	 * 
	 * @return void
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
			throw new Exception("No AWBs available in Batch");
		}
		if ($this->redis->sCard($key) == 0) {
			$this->loadFile();
		}
		$awb = $this->redis->sPop($key);
		if ($awb === false) {
			throw new Exception("No AWBs available in Redis");
		}
		$this->logAWBEvent(self::EVENT_USED, $awb);
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
		$fileTypes = array(self::AVAILABLE, self::ASSIGNED, self::FAILED);
		$used = array();
		foreach ($fileTypes as $fileType) {
			$$fileType = $this->getFromPersistentStore($fileType);
			$used[$fileType] = array();
		}
		#TODO Handle too large log file
		#TODO process the log file abd update to S3
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


	#TODO Move the S3 code to separate class
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
		#TODO Move the S3 code to separate class
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
		$localFilePath = TMP . DS . $this->model->getId() . $type . rand();
		// shell_exec("s3 cp $remoteFilePath $localFilePath");
		shell_exec("cp $remoteFilePath $localFilePath");
		#TODO Check if the copy was successful, else throw exception
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
		$batches = $this->model->find();
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
		// return "s3://btpost/awb/$type/{$this->model->getId()}.txt";

		//the following code will be removed when s3 is started using
		// $path = "~/Desktop/btpost/awb/$type/{$this->model->getId()}.txt";
		// if (!is_dir($path) || !file_exists($path)) {

		// }

		return "~/Desktop/btpost/awb/$type/{$this->model->getId()}.txt";
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