<?php

return array(
    'TeaRouter' => array(
        'openRouteRules' => true,
        'routeRules' => array(
            '/(.+)\/says\/(.+)/' => '/main/index/${1}/${2}'
        )
    ),
    'TeaModel' => array(
        'arrayResult' => true,
        'defaultConnection' => 'default',
        'connections' => array(
            'default' => array(
                'dsn' => 'mysql:host=127.0.0.1;dbname=test;',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8', // if charset has been defined in dsn, this will be invalid.
                'tablePrefix' => 'tb_',
                'aliasMark' => '->',
                'tableColumnLinkMark' => '-',
                'persistent' => true,
                'emulatePrepare' => true,
                'autoConnect' => true,
            )
        )
    )
);
