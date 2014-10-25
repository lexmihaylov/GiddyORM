<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Adapter
 *
 * @author alexander
 */
namespace Lex\GiddyORM\adapters;

abstract class Adapter {
    protected static $_connection = null;
    private static $__instance = null;
    
    public function __construct() {}
    
    public static function instance() {
        if(is_null(self::$__instance)) {
            self::$__instance = new static;
        }
        
        return self::$__instance;
    }
    
    public static function connection() {
        return self::$_connection;
    }


    abstract function connect($host, $username, $password, $database, $driver);
    abstract function query($query, array $params = array());
}
