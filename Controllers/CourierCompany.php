<?

class CourierCompany
{
	private var $name;
	private var $shortCode;
	private var $status;
	private var $info;

	/*
	*
	*/
	public function	validAWBBatch($filePath)
	{
		return $filePath;
	}

	/**
	 * Function to return if the Courier Company allows AWB pre-allocation or not
	 * 
	 * @return bool $allowed based on the settings saved for the courier company
	 */
	public function preallocateAWBAllowed()
	{
		// return value based on the DB config
	}
}