<?php

/**
 * TeaIImgBorn interface file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.imgborn.com/
 * @copyright http://tonylevid.com/
 * @license http://www.imgborn.com/license/
 */
interface TeaIImgBorn {

    public function getImgWidth();

    public function getImgHeight();

    public function getImgFormat();

    public function getImgSize();

    public function getImgFilename();

    public function getImgCreatedDate();

    public function getImgModifiedDate();

    public function getImgExif(array $items = array(), $langFile = null);

    public function getImgExifOriginal();

    public function thumbnail($width, $height, $bestfit = false);

    public function watermarkText($text, array $options = array());

    public function watermarkImg($imgFile, array $options = array());

    public function captcha($class = null, array $options = array());

    public function save($filename);
    
}