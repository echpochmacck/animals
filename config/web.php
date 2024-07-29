<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => 'ru-RU',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'asd',
            'baseUrl' => '',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'multipart/form-data' => 'yii\web\MultipartFormDataParser'
            ]

        ],
        'response' => [
            // ...
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => YII_DEBUG, // use "pretty" output in debug mode
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                    // ...
                ],
            ],

            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                if ($response->statusCode == 401) {
                    $response->statusCode = 401;
                    $response->data = [
                        'code' => 401,
                        'message' => 'Unathorized',
                    ];
                }
            },
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\Users',
            'enableAutoLogin' => true,
            'enableSession' => 'false'
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                'OPTIONS api/register' => 'user/options',
                'POST api/register' => 'user/register',
                'OPTIONS api/login' => 'user/options',
                'POST api/login' => 'user/login',
                'GET api/logout' => 'user/logout',
                'OPTIONS api/user' => 'user/options',
                'GET api/user' => 'user/user-info',
                'OPTIONS api/phone' => 'user/options',
                'PATCH api/phone' => 'user/phone',
                'OPTIONS api/email' => 'user/options',
                'PATCH api/email' => 'user/email',
                'OPTIONS api/user/orders' => 'user/options',
                'GET api/user/orders' => 'user/check-orders',
                'OPTIONS api/user/orders/<order_id>' => 'user/options',
                'DELETE api/user/orders/<order_id>' => 'order/delete',
                'PATCH api/user/orders/<order_id>' => 'order/rewrite',


                // [
                    
                //     'pattern' => 'GET api/search/<tag>',
                //     'route' => 'order/search',
                //     'defaults' => ['tag' => ''],
                    
                // ],

                'OPTIONS api/search/quick' => 'order/options',
                'GET api/search/quick' => 'order/quick',
                'OPTIONS api/search' => 'order/options',
                'GET api/search/' => 'order/search',
                'OPTIONS api/pets' => 'order/options',
                'GET api/pets' => 'order/pets',
                'OPTIONS api/subscription' => 'order/options',
                'POST api/subscription' => 'order/sub',
                'OPTIONS api/pet/new' => 'order/options',
                'POST api/pet/new' => 'order/new',
                'OPTIONS api/pet/<order_id>' => 'order/options',
                'GET api/pet/<order_id>' => 'order/animal-card',







                // [
                //     // 'prefix' => 'api',
                //     'class' => 'yii\rest\UrlRule',
                //     'controller' => 'order',
                //     'extraPatterns' => [
                //         'OPTIONS api/search/quick/?' => 'quick',
                //         'GET api/search/quick/?' =>'quick',
                //     ]

                // ]
            ],
        ]
    ],
    'params' => $params,
];
if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['*'],
    ];
}



return $config;
