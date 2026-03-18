<?php

/**
 * Test bootstrap file
 * Sets up the test environment and autoloading
 */

// Define the base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer autoloader (handles PSR-4 for App\ and Tests\ namespaces)
require_once BASE_PATH . '/vendor/autoload.php';

// Enable strict error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Use same database for tests as development
if (!defined('TEST_DB_HOST')) {
    define('TEST_DB_HOST', getenv('DB_HOST') ?: 'db');
    define('TEST_DB_NAME', getenv('DB_NAME') ?: 'crud_app');
    define('TEST_DB_USER', getenv('DB_USER') ?: 'crud_user');
    define('TEST_DB_PASSWORD', getenv('DB_PASSWORD') ?: 'crud_password');
}
