<?php
// namespace Controllers;


require __DIR__ . '/../../../autoload.php';
require __DIR__ . '/../../../malkusch/php-autoloader/autoloader.php';
require_once __DIR__ . "/../constants.php";
/**
 * Controller for all external communications of the BTPost System
 *
 */

class BTF
{
    private $accountID;

    public function __construct()
    {
        $config = require(__DIR__ . '/../config.php');

        foreach ($config as $class => $conf) {
            if (class_exists($class)) {
                $class::setStdConfig($conf);
            }
        }
    }
}
