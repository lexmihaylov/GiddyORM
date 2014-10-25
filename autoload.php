<?php
$srcDir = __DIR__ . '/src/';
spl_autoload_register(function($name) use ($srcDir) {
    require $srcDir . str_replace('\\', '/', $name) . '.php';
});