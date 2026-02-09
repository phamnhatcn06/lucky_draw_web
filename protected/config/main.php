<?php

return [
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'Lucky Draw',
    'defaultController' => 'site/show',
    'import' => [
        'application.models.*',
        'application.components.*',
    ],
    'components' => [
        'db' => [
            'connectionString' => 'mysql:host=127.0.0.1;dbname=lucky_draw',
            'emulatePrepare' => true,
            'username' => 'root',
            'password' => '123456a@',
            'charset' => 'utf8mb4',
        ],

        'urlManager' => [
            'urlFormat' => 'path',
            'showScriptName' => false,
            'rules' => [
                '' => 'site/show',

                'api/prizes' => 'api/prizes',
                'api/status' => 'api/status',
                'api/spin' => 'api/spin',

                'admin' => 'admin/index',
                'admin/login' => 'admin/login',
                'admin/logout' => 'admin/logout',
                'admin/savePrizes' => 'admin/savePrizes',
                'admin/uploadParticipants' => 'admin/uploadParticipants',
                'admin/resetWinners' => 'admin/resetWinners',
                'api/latest' => 'api/latest',
                'show' => 'site/show',
            ],
        ],

        'user' => [
            'allowAutoLogin' => true,
            'loginUrl' => ['admin/login'],
        ],

        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
    ],

    'params' => [
        'allow_multi_win' => false, // false = CHỈ TRÚNG 1 LẦN

        // Admin login (demo). Khuyến nghị đổi ngay.
        'adminUser' => 'admin_vptd',
        'adminPass' => 'vptd15102012',
    ],
];
