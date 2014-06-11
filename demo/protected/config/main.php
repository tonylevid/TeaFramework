<?php

return array(
    'TeaRouter' => array(
    ),
    'TeaModel' => array(
        'defaultConnection' => 'default',
        'connections' => array(
            'default' => array(
                'dsn' => 'mysql:host=127.0.0.1;dbname=test;',
                'username' => 'root',
                'password' => 'Tony499549943',
                'charset' => 'utf8', // if charset has been defined in dsn, this will be invalid.
                'tablePrefix' => '',
                'tableAliasMark' => '->',
                'persistent' => true,
                'emulatePrepare' => true,
                'autoConnect' => true,
            )
        )
    )
);
