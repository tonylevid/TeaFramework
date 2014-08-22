<?php

/**
 * TeaImgBorn class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.imgborn.com/
 * @copyright http://tonylevid.com/
 * @license http://www.imgborn.com/license/
 */
class TeaImgBorn extends TeaCommon implements TeaIImgBorn {

    /**
     * Class config.
     * @var array
     */
    public static $config = array(

    );

    private $_imgInstance;

    /**
     * Constructor, set class config.
     */
    public function __construct() {
        $this->setClassConfig(__CLASS__);
    }

    public function getDriverType() {
        
    }

    public function getImgInstance() {
        if (empty($this->_imgInstance)) {
            $this->_imgInstance = null;
        }
        return $this->_imgInstance;
    }

    public function getImgWidth() {
        return $this->getImgInstance()->getImgWidth();
    }

    public function getImgHeight() {
        return $this->getImgInstance()->getImgHeight();
    }

    public function getImgFormat() {
        return $this->getImgInstance()->getImgFormat();
    }

    public function getImgSize() {
        return $this->getImgInstance()->getImgSize();
    }

    public function getImgFilename() {
        return $this->getImgInstance()->getImgFilename();
    }

    public function getImgCreatedDate() {
        return $this->getImgInstance()->getImgCreatedDate();
    }

    public function getImgModifiedDate() {
        return $this->getImgInstance()->getImgModifiedDate();
    }

    public function getImgExif($items = array(), $langFile = null) {
        return $this->getImgInstance()->getImgExif($items, $langFile);
    }

    public function getImgExifOriginal() {
        return $this->getImgInstance()->getImgExifOriginal();
    }

    public function thumbnail($width, $height, $bestfit = false) {
        return $this->getImgInstance()->thumbnail($width, $height, $bestfit);
    }

    public function watermarkText($text, $options = array()) {
        return $this->getImgInstance()->watermarkText($text, $options);
    }

    public function watermarkImg($imgFile, $options = array()) {
        return $this->getImgInstance()->watermarkImg($imgFile, $options);
    }

    public function captcha($class = null, $options = array()) {
        return $this->getImgInstance()->captcha($class, $options);
    }

    public function save($filename) {
        return $this->getImgInstance()->save($filename);
    }

}