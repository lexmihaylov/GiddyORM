<?php
$classMap = require __DIR__ . '/classmap.php';

spl_autoload_register(function($name) use ($classMap) {
    if(isset($classMap[$name])) {
        require $classMap[$name];
    }
});