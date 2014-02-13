<?php


include __DIR__ . '/Exceptions.php';
include __DIR__ . '/Util.php';
include __DIR__ . '/Relationship.php';
include __DIR__ . '/DB.php';
include __DIR__ . '/Validator.php';
include __DIR__ . '/ModelList.php';
include __DIR__ . '/Model.php';

DB::$config = array(
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'qweasd',
    'database' => 'omo',
    'adapter' => 'MySQL'
);
