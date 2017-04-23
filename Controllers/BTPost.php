<?

/**
 * Controller for all external communications of the BTPost System
 * 
 */

class BTPost
{
	public static function uploadAWB($filePath, $accountId, $courierCompanyID)
	{
		// Check courier company ID, throw exception if invalid
	}

	private static function basicValidateFile($filePath)
	{
		//Check for duplicates, throw exception in case duplicates within file are found
	}
}