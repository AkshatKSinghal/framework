<?php

namespace Utility;

/**
* Class for managing file and disk related tasks
*/
class FileManager
{
	
	public static function verifyDirectory($path)
	{
		if (!is_dir($path)) {
			mkdir($path, 0777, true);
		}
	}
}