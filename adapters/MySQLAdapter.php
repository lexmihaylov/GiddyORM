<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MySQLAdapter
 *
 * @author alexander
 */
require_once __DIR__ . '/Adapter.php';

class MySQLAdapterException extends Exception {}

class MySQLAdapter extends Adapter {
    public function __construct() {
        parent::__construct();
    }
    
    public function connect($host, $username, $password, $database, $driver) {
        if (is_null(self::$_connection)) {
            self::$_connection = mysql_connect($host, $username, $password);
            if (!self::$_connection) {
                throw new MySQLAdapterException(
                    "Cannot establish connection to MySQL server: " . mysql_error()
                );
            }

            if (!mysql_select_db($database, self::$_connection)) {
                throw new MySQLAdapterException(
                    mysql_error()
                );
            }

            if (!mysql_query('SET NAMES utf8', self::$_connection)) {
                throw new MySQLAdapterException(
                    mysql_error()
                );
            }
        }
    }

    public function query($query, array $params = array()) {
        foreach ($params as $column => $val) {
            $query = str_replace(":{$column}", mysql_real_escape_string($val), $query);
        }
        $result = mysql_query($query);

        return $result;
    }
}