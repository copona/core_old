<?php

// HTTP
define('HTTP_SERVER', 'http://default2/');

// HTTPS
define('HTTPS_SERVER', 'http://default2/');

// DIR
define('DIR_APPLICATION', '/app/core/catalog/');
define('DIR_SYSTEM', '/app/core/system/');
define('DIR_IMAGE', '/app/storage/image/');
define('PATH_IMAGE', 'storage/image/'); //@todo move to config
define('DIR_LANGUAGE', '/app/core/catalog/language/');
define('DIR_TEMPLATE', '/app/themes/');
define('DIR_CONFIG', '/app/core/system/config/');
define('DIR_CACHE', '/app/storage/cache/');
define('DIR_DOWNLOAD', '/app/storage/download/');
define('DIR_LOGS', '/app/storage/logs/');
define('DIR_MODIFICATION', '/app/storage/modification/');
define('DIR_UPLOAD', '/app/storage/upload/');

// CACHE
define('CACHE_EXPIRE', 3600);

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', 'database');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_DATABASE', 'copona');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');
