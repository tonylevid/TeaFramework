<?php

return array(
    'TeaRouter' => array(
    ),
    'TeaModel' => array(
        'default' => array(
            'dsn' => 'mysql:host=127.0.0.1;dbname=test;',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8', // if charset has been defined in dsn, this will be invalid.
            'tablePrefix' => 'tb_',
            'persistent' => true,
            'emulatePrepare' => true,
            'autoConnect' => true,
        )
    )
);