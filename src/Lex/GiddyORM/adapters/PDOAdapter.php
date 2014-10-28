<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PDOAdapter
 *
 * @author Sammy Guergachi <sguergachi at gmail.com>
 */
namespace Lex\GiddyORM\adapters;

use PDO;

class PDOAdapterException extends \Exception {}

class PDOAdapter extends Adapter {
    public function connect($host, $username, $password, $database, $driver) {
        if (is_null(self::$_connection)) {
            self::$_connection = new PDO("$driver:host=$host;dbname=$database", $username, $password);
        }
    }
    
    public function query($query, array $params = array()) {
        $statement = self::$_connection->prepare($query);
        
        if(!$statement->execute($params)) {
            throw new PDOAdapterException(array_pop($statement->errorInfo()));
        }
        
        return $statement;
    }
    
    public function fetch_object($result, $class_name = 'stdClass') {
        $result->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $class_name);
        
        return $result->fetch();
    }
    
    public function count($result) {
        $result->count();
    }
    
    public function escape($string) {
        return self::$_connection->quote($string);
    }
    
    public function last_insert_id() {
        return self::$_connection->lastInsertId();
    }
    
    public function affected_rows() {
        return mysql_affected_rows();
    }
}
