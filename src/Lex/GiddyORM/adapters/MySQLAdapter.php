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

namespace Lex\GiddyORM\adapters;

class MySQLAdapterException extends \Exception {}

class MySQLAdapter extends Adapter {
    public function __construct() {
        parent::__construct();
    }
    
    public function connect($host, $username, $password, $database, $driver) {
        if (is_null(self::$_connection)) {
            self::$_connection = new mysqli($host, $username, $password, $database);
            if (mysqli_connect_errno) {
                throw new MySQLAdapterException(
                    "Cannot establish connection to MySQL server: " . 
                        mysqli_connect_error()
                );
            }
        }
    }

    public function query($query, array $params = array()) {
        $statement = self::$_connection->prepare($query);
        
        call_user_func_array(array($statement, 'bind_param'), $params);
        
        if(!$statement->execute()) {
            throw new MySQLAdapterException($statement->error);
        }
        
        return $statement;
    }
    
    public function fetch_object($result, $class_name = 'stdClass') {
        $result->fetch_object($class_name);
    }
    
    public function count($result) {
        $result->store_result();
        return $result->num_rows;
    }
    
    public function escape($string) {
        return self::$_connection->real_escape_string($string);
    }
    
    public function last_insert_id() {
        return self::$_connection->insert_id;
    }
    
    public function affected_rows() {
        return self::$_connection->affected_rows;
    }
}
