 <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set("auto_detect_line_endings", true);

    require __DIR__ . '/../../autoload.php';
    require __DIR__ . '/../../malkusch/php-autoloader/autoloader.php';
    require __DIR__ . "/constants.php";
    try {
        echo '****start*******';

        $bt = new BTF([]);
        echo '******end******';
    } catch (Exception $e) {
        echo '<br>';
        echo '<pre>';
        print_r($e);
    }
