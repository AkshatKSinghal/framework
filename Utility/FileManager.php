<?php

namespace Utility;

/**
* Class for managing file and disk related tasks
*/
class FileManager
{
    private $filePath;
    private static $allowed = [
        'img' => ['image/jpg', 'image/jpeg', 'image/png','image/gif'],
        'text' => ['text/plain']
    ];
    private static $maxSize = 2 * 1000000;
    private static $tmp = btpTMP . '/img/';

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }
    
    /**
     * Function to verify if a directory exists if not it will create one.
     * @param string $path 
     * @return void
     */
    public static function verifyDirectory($path)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new \Exception("Could not create a folder. Please contact admin.");
            }
        }
    }

    /**
     * Function to get the line count of the given file path
     * @param string $filePath 
     * @return string number of lines
     */
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

    /**
     * Function to validate uploaded file
     * #TODO move this to a new File Utility class
     * @return string temporary file path
     */
    public static function validate($filePath, $fileType)
    {
        $allowedTypes = self::$allowed[$fileType];

        self::verifyDirectory(self::$tmp);
        $tmpfile = self::$tmp . $filePath;
        // move_uploaded_file( $filename , $tmpfile);
        $type = mime_content_type($filePath);
        if( ! in_array( $type, $allowedTypes ) ) {
            throw new \Exception("File type not allowed :". $type);
        }

        if (filesize($filePath) > self::$maxSize) {
            throw new \Exception("Max file size 2 MB");
        }
        return true;
    }

    /**
     * Function to get the extension from the name
     * @param string $filename 
     * @return string extension
     */
    public static function getExtensionFromName($filename)
    {
        return substr($filename, strrpos($filename, '.'));
    }
}
