<?php

define('DS', DIRECTORY_SEPARATOR);

spl_autoload_register(function ($name) {
    $parts = explode("\\", $name);
    $parts = array_filter($parts);
	array_shift($parts);

    /*
     * namespace calls
     */

    //compose standart namespaced path to file
    $path = '.' . DS . implode(DS, $parts) . '.php';
    if (file_exists($path)) {
        require_once $path;
        return;
    }
});
