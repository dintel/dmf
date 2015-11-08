<?php
error_reporting(E_ALL | E_STRICT);
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

// Copy testing configuration
$srcTestConfigFile = __DIR__ . '/test.php';
$testConfigFile = __DIR__ . '/../config/autoload/test.php';
copy($srcTestConfigFile, $testConfigFile);

// Command that starts the built-in web server
$command = sprintf(
    'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
    WEB_SERVER_HOST,
    WEB_SERVER_PORT,
    WEB_SERVER_DOCROOT
);

// Execute the command and store the process ID
$output = array();
exec($command, $output);
$pid = (int) $output[0];

echo sprintf(
    '%s - Web server started on %s:%d with PID %d',
    date('r'),
    WEB_SERVER_HOST,
    WEB_SERVER_PORT,
    $pid
) . PHP_EOL;

// Wait a second for web server to start
sleep(1);

// Kill the web server when the process ends
register_shutdown_function(function() use ($pid, $testConfigFile) {
        echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
        exec('kill ' . $pid);
        unlink($testConfigFile);
    });
