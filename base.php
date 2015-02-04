<?php
// DEBUG
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 1);
// DEBUG

// configuration
require_once 'config.php';

// external libs
require_once 'libs' . DIRECTORY_SEPARATOR . 'external' . DIRECTORY_SEPARATOR . 'underscore.php';


/**
 * Constants
 */
define(FILES_DIR, 'files' . DIRECTORY_SEPARATOR);
define(BASE_URL, 'http://localhost/ir2015/');

/**
     * Class autoloader
 * 
 * @param string $className
 */
function __autoload($className) {
    require_once 'libs' . DIRECTORY_SEPARATOR . 'internal' . DIRECTORY_SEPARATOR .$className . '.php';
}