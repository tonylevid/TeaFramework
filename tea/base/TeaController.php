<?php

/**
 * TeaController class file.
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package base
 */
class TeaController {

    public static $config = array(
        'defaultController' => 'Main',
        'defaultAction' => 'index'
    );

    public function __construct() {
        Tea::setClassConfig(__CLASS__);
    }

}