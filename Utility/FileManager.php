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

	public static function lineCount($filePath)
	{

		$fp = fopen($filePath, 'r');
		$lineCount = 0;
		while (!feof($fp)) {
			$line = trim(fgets($fp));
			if (empty($line)) {
				continue;
			}
			$lineCount++;
		}
		fclose($fp);
		return $lineCount;
	}
}