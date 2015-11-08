<?php
use Zend\Config\Factory as ConfigFactory;

return ConfigFactory::fromFiles(
    glob('config/autoload/{global,local,test}.php', GLOB_BRACE)
);
