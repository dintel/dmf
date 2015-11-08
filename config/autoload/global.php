<?php
return [
    'dispatcher' => [
        'pre' => [
            'Application\Dispatcher\LogRequest',
            'Application\Security\Authentication',
            'Application\Security\Acl',
            'Application\Json\Request',
        ],
        'exception' => 'Application\Dispatcher\Error',
        'routes' => [
            [
                'path' => '/{service}/{method}',
            ],
            [
                'path' => '/bootstrap',
                'values' => [
                    'service' => 'bootstrap',
                    'method' => 'bootstrap',
                ],
            ],
        ],
        'secure' => false,
    ],
    'mongoObjectMapper' => [
        'modelsNamespace' => 'Application\Model',
    ],
    'log' => [
        'exceptionhandler' => true,
        'errorhandler' => true,
        'writers' => [
            [
                'name' => 'stream',
                'options' => ['stream' => __DIR__ . '/../../data/log/template.log'],
            ]
        ],
    ],
    'publicPaths' => [
        '|^/auth/login$|',
        '|^/bootstrap$|',
    ],
    'acl' => [
        'admin' => [
            ['type' => 'allow', 'service' => '/.*/', 'method' => '/.*/'],
        ],
        'user' => [
            ['type' => 'allow', 'service' => '/.*/', 'method' => '/(list|get)/'],
        ],
    ],
];
