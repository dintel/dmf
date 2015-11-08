<?php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$container = include 'config/services.php';
$dispatcher = $container->get('Application\Dispatcher\Dispatcher');
echo $dispatcher->dispatch();
