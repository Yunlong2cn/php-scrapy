<?php
defined('PS_DEBUG') or define('PS_DEBUG', true);
defined('PS_ENV') or define('PS_ENV', 'dev');

require(__DIR__ . '/vendor/autoload.php');

yunlong2cn\ps\Router::run([]);