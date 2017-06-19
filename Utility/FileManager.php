<?php

namespace Utility;

/**
* Class for managing file and disk related tasks
*/
class FileManager
{
    private $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }
    
    public static function verifyDirectory($path)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new \Exception("Could not create a folder. Please contact admin.");
            }
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
