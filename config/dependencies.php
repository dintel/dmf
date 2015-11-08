<?php
return [
    'services' => [
        'config' => include __DIR__ . '/config.php',
    ],
    'invokables' => [
        'Application\Security\Authentication' => 'Application\Security\Authentication',
        'Application\Security\Acl' => 'Application\Security\Acl',
        'Application\Dispatcher\Dispatcher'=> 'Application\Dispatcher\Dispatcher',
        'Application\Json\Request'=> 'Application\Json\Request',
        'Application\Dispatcher\LogRequest' => 'Application\Dispatcher\LogRequest',
        'Application\Dispatcher\Error' => 'Application\Dispatcher\Error',
        // Controllers
        'auth' => 'Application\Controller\Auth',
        'bootstrap' => 'Application\Controller\Bootstrap',
        'user' => 'Application\Controller\User',
    ],
    'factories' => [
        'Zend\Log\Logger' => 'Zend\Log\LoggerServiceFactory',
        'MongoObject\Mapper' => 'MongoObject\MapperFactory',
        'Application\Authentication\AuthenticationService' => 'Application\Authentication\AuthenticationServiceFactory',
        'Application\Mail\Mail' => 'Application\Mail\MailFactory',
    ],
    'aliases' => [
        'dispatcher' => 'Application\Dispatcher\Dispatcher',
        'log' => 'Zend\Log\Logger',
        'mapper' => 'MongoObject\Mapper',
        'request' => 'Application\Json\Request',
    ],
    'initializers' => [
        'ServiceLocatorAwareInitializer' => function ($instance, \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator) {
            if ($instance instanceof \Zend\ServiceManager\ServiceLocatorAwareInterface) {
                $instance->setServiceLocator($serviceLocator);
            }
        }
    ],
];
