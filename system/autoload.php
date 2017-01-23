<?php

// Autoloader
if (is_file(__DIR__ . '/../../vendor/autoload.php')) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

function library($class) {
    $file = __DIR__ . 'library/' . str_replace('\\', '/', strtolower($class)) . '.php';

    if (is_file($file)) {
        include_once(modification($file));

        return true;
    } else {
        return false;
    }
}

spl_autoload_register('library');
spl_autoload_extensions('.php');