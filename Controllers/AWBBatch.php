<?
/**
 * Controller responsible for management and use of AWBs
 * 
 */

class AWBBatch extends Base
{
	private var $redis = null;
	const UPLOAD = 'upload';
	const INVALID = 'invalid';
	const VALID = 'valid';
	const AVAILABLE = 'available';
	const ASSIGNED = 'assigned';
	const FAILED = 'failed';

	/**
	 * Function to process the AWB batch and create the valid and invalid list 
	 * @throws Exception in case the batch is already being processed
	 */

	public function processFile()
	{
		$this->getProcessingLock();
		$file = $this->getFromPersistentStore('upload');
		$existingBatchesSet = $this->loadExistingBatches();
		$fp = fopen($file, 'r');
		$validAWBFile = $this->getTempFile('valid');
		file_put_contents($validAWBFile, '');
		$invalidAWBFile = $this->getTempFile('invalid');
		file_put_contents($validAWBFile, '');
		$validCount = 0;
		$invalidCount = 0;
		while($awb = fread($fp)) {
			$awb = trim($awb);
			$type = 'valid';
			if ($this->redis->existsInSet($existingBatchesSet, $awb)) {
				$type = 'invalid';
			}
			$fileName = $type . "AWBFile";
			$counter = $type . "Count";
			file_put_contents($fileName, $awb . "\t" . "DUPLICATE" . PHP_EOL, FILE_APPEND);
			$counter++;
		}
		$courier = new CourierCompany($this->getCourierCompanyId());
		$awbFile = $courier->validateAWBFile($validAWBFile, $errorFile);
		$this->markProcessed();
	}

	/**
	 * Function to obtain lock for processing the uploaded AWB file by marking
	 * the batch status as PROCESSING
	 * 
	 * @throws Exception if the lock is already in use 
	 * i.e. the batch status is already PROCESSING
	 * @throws Exception if the batch is already processed
	 * i.e. the batch status is already PROCESSED
	 * 
	 * @return void
	 */ 
	private function getProcessingLock()
	{
		//get status
		if ($status == 'PROCESSING') {
			throw new Exception("Batch already under processing");
		} else if ($status == 'PROCESSED') {
			throw new Exception("Batch already processed");
		}
		//update status
	}

	/**
	 * Function to mark the status of the batch as processed (this is not required, should be in model)
	 * 
	 * @return void
	 */
	private function markProcessed()
	{
		//update status
	}

	/**
	 * Function to get the file from S3 and load into redis set.
	 * @param $type string (optional) default value
	 * @param $customKey string (optional) name of the set to be loaded into
	 * @throws Exception in case the AWB file fetch fails
	 */ 
	public function loadFile($type = 'available', $customKey = null)
	{
		// Update the available AWB list with the existing log, if any
		if ($type == 'available') {
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
		$this->redis->exists()
		// pop from Redis, update entries for available and used count in DB
		$this->logAWBEvent('used', $awb);
	}

	public function updatePersistentStore()
	{
		//Put a lock mechanism to avoid issue due to multiple processes working on the same batch
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
		// Log timestamp, $awb, $event
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
		$localFilePath = TMP . DS . $this->id . $type . rand();
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
		$this->AWBBatch->find('all', //condition for processed AWB batches created within x time for the same courier company and account id);
		$proccessingSetName = $this->getRedisSetKey("processing");
		foreach($batches as $AWBBatch) {
			$AWBBatch->loadFile($proccessingSetName);
		}
		return $proccessingSetName;
	}

	private function getTempFile($type)
	{
		return TMP . DS . $this->id . $type . rand();
	}

	private function getS3Path($type)
	{
		return "s3://btpost/awb/$type/{$this->id}.txt";
	}

	private function getRedisSetKey($type)
	{
		return "{$this->id}_$type";
	}
}

/**
* 
*/
class S3Exception extends Exception
{

}