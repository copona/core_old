<?php

define('DIR_ROOT', realpath(__DIR__ . '/../'));

//Composer autoload
require_once __DIR__ . '/system/autoload.php';

//@TODO move to other local
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

use \Copona\Core\System\Framework;

$framework = new Framework();

$framework->boot();
$framework->start();
$framework->output();