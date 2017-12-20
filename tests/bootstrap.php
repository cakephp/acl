<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

require_once 'vendor/autoload.php';

define('ROOT', dirname(__DIR__));
define('APP_DIR', 'test_app');
define('WEBROOT_DIR', 'webroot');

define('TMP', sys_get_temp_dir() . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('SESSIONS', TMP . 'sessions' . DS);

define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);
define('CORE_TESTS', CORE_PATH . 'tests' . DS);
define('CORE_TEST_CASES', CORE_TESTS . 'TestCase');
define('TEST_APP', ROOT . DS . 'tests' . DS . 'test_app' . DS);
define('LOG_ERROR', LOG_ERR);

// Point app constants to the test app.
define('APP', TEST_APP . DS);
define('WWW_ROOT', TEST_APP . WEBROOT_DIR . DS);
define('TESTS', TEST_APP . 'tests' . DS);
define('CONFIG', TEST_APP . 'config' . DS);

//@codingStandardsIgnoreStart
@mkdir(LOGS);
@mkdir(SESSIONS);
@mkdir(CACHE);
@mkdir(CACHE . 'views');
@mkdir(CACHE . 'models');
//@codingStandardsIgnoreEnd

require CAKE . 'Core/ClassLoader.php';

$loader = new Cake\Core\ClassLoader;
$loader->register();

$loader->addNamespace('Cake\Test\Fixture', ROOT . '/vendor/cakephp/cakephp/tests/Fixture');
$loader->addNamespace('TestApp', APP . 'src');
$loader->addNamespace('PluginJs', TEST_APP . 'Plugin/PluginJs/src');

require_once CORE_PATH . 'config' . DS . 'bootstrap.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'Acl',
    'encoding' => 'UTF-8',
    'base' => false,
    'baseUrl' => false,
    'dir' => APP_DIR,
    'webroot' => WEBROOT_DIR,
    'www_root' => WWW_ROOT,
    'fullBaseUrl' => 'http://localhost',
    'imageBaseUrl' => 'img/',
    'jsBaseUrl' => 'js/',
    'cssBaseUrl' => 'css/',
    'paths' => [
        'plugins' => [TEST_APP . 'Plugin' . DS],
        'templates' => [APP . 'Template' . DS]
    ]
]);

Cache::setConfig([
    '_cake_core_' => [
        'engine' => 'File',
        'prefix' => 'cake_core_',
        'serialize' => true
    ],
    '_cake_model_' => [
        'engine' => 'File',
        'prefix' => 'cake_model_',
        'serialize' => true
    ]
]);

// Ensure default test connection is defined
if (!getenv('db_class')) {
    putenv('db_class=Cake\Database\Driver\Sqlite');
    putenv('db_dsn=sqlite::memory:');
}

ConnectionManager::setConfig('test', [
    'className' => 'Cake\Database\Connection',
    'driver' => getenv('db_class'),
    'dsn' => getenv('db_dsn'),
    'database' => getenv('db_database'),
    'username' => getenv('db_login'),
    'password' => getenv('db_password'),
    'timezone' => 'UTC',
    'quoteIdentifiers' => getenv('quoteIdentifiers'),
]);

Configure::write('Session', [
    'defaults' => 'php'
]);

Log::setConfig([
    'debug' => [
        'engine' => 'Cake\Log\Engine\FileLog',
        'levels' => ['notice', 'info', 'debug'],
        'file' => 'debug',
    ],
    'error' => [
        'engine' => 'Cake\Log\Engine\FileLog',
        'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        'file' => 'error',
    ]
]);

if (class_exists('Carbon\Carbon')) {
    Carbon\Carbon::setTestNow(Carbon\Carbon::now());
} else {
    Cake\Chronos\Chronos::setTestNow(Cake\Chronos\Chronos::now());
    Cake\Chronos\MutableDateTime::setTestNow(Cake\Chronos\MutableDateTime::now());
    Cake\Chronos\Date::setTestNow(Cake\Chronos\Date::now());
    Cake\Chronos\MutableDate::setTestNow(Cake\Chronos\MutableDate::now());
}

if (class_exists('PHPUnit_Runner_Version')) {
    class_alias('PHPUnit_Framework_TestResult', 'PHPUnit\Framework\TestResult');
    class_alias('PHPUnit_Framework_Error', 'PHPUnit\Framework\Error\Error');
    class_alias('PHPUnit_Framework_Error_Warning', 'PHPUnit\Framework\Error\Warning');
    class_alias('PHPUnit_Framework_Error_Notice', 'PHPUnit\Framework\Error\Notice');
    class_alias('PHPUnit_Framework_ExpectationFailedException', 'PHPUnit\Framework\ExpectationFailedException');
}
