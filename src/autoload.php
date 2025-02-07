<?php

const DS = DIRECTORY_SEPARATOR;

spl_autoload_register(function ($name) {
    $parts = explode("\\", $name);
    $parts = array_filter($parts);
    if ($parts[0] != 'Bolt')
        return;
    array_shift($parts);

    if (reset($parts) == 'tests')
        array_unshift($parts,'..');

    //compose standard namespaced path to file
    $path = __DIR__ . DS . implode(DS, $parts) . '.php';
    if (file_exists($path)) {
        require_once $path;
        return;
    }
});
