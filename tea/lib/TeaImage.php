<?php

/**
 * TeaImage class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package lib
 */
class TeaImage {

    public function __construct() {
        if (!extension_loaded('gd')) {
            throw new Exception('TeaImage requires gd extension loaded.');
        }
    }

}

class TeaImageThumbnail {

}

class TeaImageWatermark {

}

class TeaImageCaptcha {
    
}