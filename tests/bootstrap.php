<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Set environment variables specific to tests
$_SERVER['APP_ENV'] = 'test';
$_SERVER['SYMFONY_DEPRECATIONS_HELPER'] = 'disabled';

// Create test environment
passthru('APP_ENV=test '.dirname(__DIR__).'/bin/console cache:clear --quiet');
