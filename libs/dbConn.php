<?php

/**
 * Connection to MySQL database
 *
 * @author micobg
 */
class dbConn {
    
    protected static $mysql;

    protected function __construct() {
        // implement Singleton
    }
    
    public static function getInstance() {        
        if (!isset(self::$mysql)) {
            try {
                self::$mysql = new PDO(DB_DSN, DB_USER, DB_PASS);
                self::$mysql->exec('SET NAMES utf8');
            } catch (PDOException $e) {
                echo 'Connection failed: ' . $e->getMessage();
            }
        }
        
        return self::$mysql;
    }
        
}