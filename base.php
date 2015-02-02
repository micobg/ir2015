<?php
// DEBUG
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 1);
// DEBUG

require_once 'config.php';

/**
 * Class autoloader
 * 
 * @param string $className
 */
function __autoload($className) {
    require_once 'libs/' .$className . '.php';
}