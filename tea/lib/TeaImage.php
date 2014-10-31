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

    /**
     * Common font file, this will be set in constructor.
     * @var string
     */
    public static $commonFontFile;

    /**
     * Image type to gd output function map.
     * @var array
     */
    protected static $_imgOutputFuncMap = array(
        'gif' => 'imagegif',
        'jpg' => 'imagejpeg',
        'jpe' => 'imagejpeg',
        'jpeg' => 'imagejpeg',
        'png' => 'imagepng',
        'wbmp' => 'imagewbmp',
        'webp' => 'imagewebp',
        'xbm' => 'imagexbm'
    );

    /**
     * Captcha value.
     * @var string
     */
    private $_captchaVal;

    /**
     * Gd resource.
     * @var resource
     */
    private $_gdRes;

    /**
     * Constructor, set default common font file.
     */
    public function __construct() {
        if (!extension_loaded('gd')) {
            throw new Exception('TeaImage requires gd extension loaded.');
        }
        if (defined('APP_PATH')) {
            $defaultFont = APP_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR . 'STHeiti-Light.ttc';
            if (file_exists($defaultFont)) {
                $this->setCommonFontFile($defaultFont);
            }
        }
    }

    /**
     * Destructor, destroy gd resource.
     */
    public function __destruct() {
        imagedestroy($this->_gdRes);
    }

    /**
     * Set common font file.
     * @param string $fontFile Font file path.
     * @return $this
     */
    public function setCommonFontFile($fontFile) {
        if (is_file($fontFile)) {
            self::$commonFontFile = $fontFile;
        } else {
            return false;
        }
        return $this;
    }

    /**
     * Load image to gd resource.
     * @param string $file Image file path.
     * @return $this
     */
    public function loadImage($file) {
        $gdRes = imagecreatefromstring(file_get_contents($file));
        if ($gdRes === false) {
            return false;
        } else {
            $this->_gdRes = $gdRes;
            return $this;
        }
    }

    /**
     * Get gd resource image width.
     * @return int
     */
    public function getImageWidth() {
        return imagesx($this->_gdRes);
    }

    /**
     * Get gd resource image height.
     * @return int
     */
    public function getImageHeight() {
        return imagesy($this->_gdRes);
    }

    /**
     * Thumbnail image.
     * @param int $width Thumbnail width.
     * @param int $height Thumbnail height.
     * @return $this
     */
    public function thumbnail($width, $height) {
        $newGdRes = imagecreatetruecolor($width, $height);
        $status = imagecopyresampled($newGdRes, $this->_gdRes, 0, 0, 0, 0, $width, $height, $this->getImageWidth(), $this->getImageHeight());
        if ($status) {
            $this->_gdRes = $newGdRes;
            return $this;
        }
        return false;
    }

    /**
     * Watermark image with text.
     * @param string $text Watermark text.
     * @param string|array $position String indicates position, see $this->getCalcPositions(), or array indicates left and top.
     * @param array $options Watermark text options, see self::watermarkTextOptions().
     * @return $this
     */
    public function watermarkText($text, $position = 'RB', $options = array()) {
        $options = array_merge(self::watermarkTextOptions(), $options);
        $rgbColor = $this->getRgbColor($options['fontColor']);
        list($red, $green, $blue) = array($rgbColor['red'], $rgbColor['green'], $rgbColor['blue']);
        $alpha = isset($options['fontAlpha']) ? $this->rgbaAlphaToGdAlpha(floatval($options['fontAlpha'])) : $this->rgbaAlphaToGdAlpha(1);
        $fontColor = imagecolorallocatealpha($this->_gdRes, $red, $green, $blue, $alpha);
        $textBox = $this->calculateTextBox($text, $options['fontFile'], floatval($options['fontSize']), floatval($options['fontAngle']));
        $left = $textBox['left'];
        $top = $textBox['top'];
        $calcPositions = $this->getCalcPositions($textBox['width'], $textBox['height']);
        if (is_array($position)) {
            $left = isset($position[0]) ? intval($position[0]) + $left : $left;
            $top = isset($position[1]) ? intval($position[1]) + $top : $top;
        } else if (is_string($position) && array_key_exists($position, $calcPositions)) {
            $calcPosition = $calcPositions[$position];
            $left = $calcPosition[0] + $left;
            $top = $calcPosition[1] + $top;
        }
        $textPositions = imagettftext($this->_gdRes, floatval($options['fontSize']), floatval($options['fontAngle']), $left, $top, $fontColor, $options['fontFile'], $text);
        if (is_array($textPositions)) {
            return $this;
        }
        return false;
    }

    /**
     * WatermarkText options.
     * @return array
     */
    public static function watermarkTextOptions() {
        return array(
            'fontSize' => 12,
            'fontAngle' => 0,
            'fontColor' => array(0, 0, 0),
            'fontAlpha' => 1,
            'fontFile' => self::$commonFontFile
        );
    }

    /**
     * Watermark image with image.
     * @param string $file Watermark image file path.
     * @param string|array $position String indicates position, see $this->getCalcPositions(), or array indicates left and top.
     * @param float $alpha Watermark image alpha, from 0 to 1.
     * @return $this
     */
    public function watermarkImage($file, $position = 'RB', $alpha = 1) {
        $newGdRes = imagecreatefromstring(file_get_contents($file));
        $srcWidth = imagesx($newGdRes);
        $srcHeight = imagesy($newGdRes);
        $left = 0;
        $top = 0;
        $calcPositions = $this->getCalcPositions($srcWidth, $srcHeight);
        if (is_array($position)) {
            $left = isset($position[0]) ? intval($position[0]) : $left;
            $top = isset($position[1]) ? intval($position[1]) : $top;
        } else if (is_string($position) && array_key_exists($position, $calcPositions)) {
            $calcPosition = $calcPositions[$position];
            $left = $calcPosition[0];
            $top = $calcPosition[1];
        }
        $alpha = $this->rgbaAlphaToImageAlpha(floatval($alpha));
        $status = $this->imagecopymerge_alpha($this->_gdRes , $newGdRes, $left, $top, 0, 0, $srcWidth, $srcHeight, $alpha);
        return $status ? $this : false;
    }

    /**
     * Make captcha image.
     * @param int $width Captcha image width.
     * @param int $height Captcha image height.
     * @param array $options Captcha options, see self::captchaOptions().
     * @return $this
     */
    public function captcha($width = 100, $height = 30, $options = array()) {
        $options = array_merge(self::captchaOptions(), $options);
        $this->_captchaVal = null;
        $this->_gdRes = imagecreatetruecolor($width, $height);
        // fill background
        $bgRgbColor = $this->getRgbColor($options['bgColor']);
        $bgColor = imagecolorallocate($this->_gdRes, $bgRgbColor['red'], $bgRgbColor['green'], $bgRgbColor['blue']);
        imagefill($this->_gdRes, 0, 0, $bgColor);
        // draw points
        for ($i = 0; $i < $options['pointsNum']; $i++) {
            $x = mt_rand(0, $width);
            $y = mt_rand(0, $height);
            $color = imagecolorallocate($this->_gdRes, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($this->_gdRes, $x, $y, $color);
        }
        // draw lines
        for ($i = 0; $i < $options['linesNum']; $i++) {
            $x1 = mt_rand(0, $width / 3);
            $y1 = mt_rand(0, $height);
            $x2 = mt_rand($width / 3, $width);
            $y2 = mt_rand(0, $height);
            $color = imagecolorallocate($this->_gdRes, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imageline($this->_gdRes, $x1, $y1, $x2, $y2, $color);
        }
        // draw arcs
        for ($i = 0; $i < $options['arcsNum']; $i++) {
            $cx = mt_rand($width / 3,  2 * $width / 3);
            $cy = mt_rand(0, $height);
            $w = mt_rand(0, $width);
            $h = mt_rand(0, $height);
            $s = mt_rand(0, 360);
            $e = mt_rand(0, 360);
            $color = imagecolorallocate($this->_gdRes, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagearc($this->_gdRes, $cx, $cy, $w, $h, $s, $e, $color);
        }
        // set font color
        $fontRgbColor = $this->getRgbColor($options['fontColor']);
        $fontColor = imagecolorallocate($this->_gdRes, $fontRgbColor['red'], $fontRgbColor['green'], $fontRgbColor['blue']);
        // get random characters
        $text = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
        $textLen = mb_strlen($text);
        $chars = preg_split('/(?<!^)(?!$)/u', $text);
        $randChars = array();
        for ($i = 0; $i < $options['charsCount']; $i++) {
            $randNum = mt_rand(0, $textLen - 1);
            $randChars[$i] = $chars[$randNum];
        }

        // $randStr = implode('', $randChars);
        // $textBox = $this->calculateTextBox($randStr, $options['fontFile'], floatval($options['fontSize']), 0);
        // $calcPositions = $this->getCalcPositions($textBox['width'], $textBox['height']);
        // $left = $calcPositions['MM'][0] + $textBox['left'];
        // $top = $calcPositions['MM'][1] + $textBox['top'];
        // imagettftext($this->_gdRes, floatval($options['fontSize']), 0, $left, $top, $fontColor, $options['fontFile'], $randStr);

        // render each character
        $prevCharWidth = 0;
        $left = 0;
        foreach ($randChars as $char) {
            $fontAngle = 0;
            $textBox = $this->calculateTextBox($char, $options['fontFile'], floatval($options['fontSize']), $fontAngle);
            $calcPositions = $this->getCalcPositions($textBox['width'], $textBox['height']);
            $left += ($calcPositions['LM'][0] + $textBox['left'] + $options['charLeftPadding'] + $prevCharWidth);
            $top = ($calcPositions['LM'][1] + $textBox['top']);
            imagettftext($this->_gdRes, floatval($options['fontSize']), $fontAngle, $left, $top, $fontColor, $options['fontFile'], $char);
            $prevCharWidth = $textBox['width'];
        }

        $this->_captchaVal = implode('', $randChars);
        return $this;
    }

    /**
     * get captcha value.
     * @return mixed
     */
    public function getCaptchaVal() {
        return $this->_captchaVal;
    }

    /**
     * Captcha options
     * @return array
     */
    public static function captchaOptions() {
        return array(
            'bgColor' => array(255, 255, 255),
            'charsCount' => 5,
            'charLeftPadding' => 8,
            'pointsNum' => 60,
            'linesNum' => 3,
            'arcsNum' => 1,
            'fontColor' => array(0, 0, 0),
            'fontSize' => 12,
            'fontFile' => self::$commonFontFile
        );
    }

    /**
     * Output image.
     * @param int $constImageType IMAGETYPE_XXX constant.
     */
    public function output($constImageType = IMAGETYPE_PNG) {
        $imgType = image_type_to_extension($constImageType, false);
        $mimeType = image_type_to_mime_type($constImageType);
        if (array_key_exists($imgType, self::$_imgOutputFuncMap)) {
            header('Content-type: ' . $mimeType);
            $func = self::$_imgOutputFuncMap[$imgType];
            call_user_func_array($func, array($this->_gdRes));
        }
    }

    /**
     * Save image.
     * @param string $file Save file path.
     * @return bool
     */
    public function save($file) {
        $parts = explode('.', $file);
        $type = array_pop($parts);
        $status = false;
        if (array_key_exists(strtolower($type), self::$_imgOutputFuncMap)) {
            $func = self::$_imgOutputFuncMap[$type];
            $status = call_user_func_array($func, array($this->_gdRes, $file));
        }
        return $status;
    }

    /**
     * Get calculated positions with source image width and height.
     * @param int $srcWidth Source image width.
     * @param int $srcHeight Source image height.
     * @return array Array indicates different positions.
     */
    protected function getCalcPositions($srcWidth, $srcHeight) {
        list($targetWidth, $targetHeight) = array($this->getImageWidth(), $this->getImageHeight());
        list($middleX, $middleY) = array(($targetWidth - $srcWidth) / 2, ($targetHeight - $srcHeight) / 2);
        return array(
            'LT' => array(0, 0),
            'LM' => array(0, $middleY),
            'LB' => array(0, ($targetHeight - $srcHeight)),
            'MT' => array($middleX, 0),
            'MM' => array($middleX, $middleY),
            'MB' => array($middleX, ($targetHeight - $srcHeight)),
            'RT' => array(($targetWidth - $srcWidth), 0),
            'RM' => array(($targetWidth - $srcWidth), $middleY),
            'RB' => array(($targetWidth - $srcWidth), ($targetHeight - $srcHeight))
        );
    }

    /**
     * Calculate text box information.
     * @param string $text String to calculated.
     * @param string $fontFile Font file path.
     * @param float $fontSize  Font size.
     * @param float $fontAngle Font angle.
     * @return array Array indicates text box information.
     */
    protected function calculateTextBox($text, $fontFile, $fontSize, $fontAngle) {
        $rect = imagettfbbox($fontSize, $fontAngle, $fontFile, $text);
        $minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
        $minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
        $maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));
        return array(
            'left' => abs($minX) - 1,
            'top' => abs($minY) - 1,
            'width' => $maxX - $minX,
            'height' => $maxY - $minY,
            'box' => $rect
        );
    }

    /**
     * Get rgb color array.
     * @param string|array $val Hex color string or rgb color array.
     * @return array
     */
    protected function getRgbColor($val) {
        $red = 0;
        $green = 0;
        $blue = 0;
        if (is_array($val)) {
            $red = isset($val[0]) ? intval($val[0]) : $red;
            $green = isset($val[1]) ? intval($val[1]) : $green;
            $blue = isset($val[2]) ? intval($val[2]) : $blue;
        } else if (is_string($val)) {
            $rgbArr = $this->hexColorToRgbColor($val);
            if (is_array($rgbArr)) {
                $red = $rgbArr['red'];
                $green = $rgbArr['green'];
                $blue = $rgbArr['blue'];
            }
        }
        return array(
            'red' => $red,
            'green' => $green,
            'blue' => $blue
        );
    }

    /**
     * Hex color to rgb color.
     * @param string $hexStr Hex color string.
     * @param bool $returnStr Return as string or not.
     * @param string $seperator The seperator for rgb color if $returnStr is true.
     * @return string|array
     */
    protected function hexColorToRgbColor($hexStr, $returnStr = false, $seperator = ',') {
        $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
        $rgbArr = array();
        if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
            $colorVal = hexdec($hexStr);
            $rgbArr['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArr['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArr['blue'] = 0xFF & $colorVal;
        } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
            $rgbArr['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArr['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArr['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        } else {
            return false; //Invalid hex color code
        }
        return $returnStr ? implode($seperator, $rgbArr) : $rgbArr; // returns the rgb string or the associative array
    }

    /**
     * Rgba alpha to image alpha for function imagecopymerge().
     * @param float $rgbaAlpha Alpha number from 0 to 1.
     * @return int Image alpha for function imagecopymerge().
     */
    protected function rgbaAlphaToImageAlpha($rgbaAlpha) {
        if ($rgbaAlpha < 0) {
            $rgbaAlpha = 0;
        } else if ($rgbaAlpha > 1) {
            $rgbaAlpha = 1;
        }
        return round($rgbaAlpha * 100);
    }

    /**
     * Rgba alpha to gd alpha for function imagecolorallocatealpha().
     * @param float $rgbaAlpha Alpha number from 0 to 1.
     * @return int Gd alpha for function imagecolorallocatealpha().
     */
    protected function rgbaAlphaToGdAlpha($rgbaAlpha) {
        if ($rgbaAlpha < 0) {
            $rgbaAlpha = 0;
        } else if ($rgbaAlpha > 1) {
            $rgbaAlpha = 1;
        }
        return round(127 - $rgbaAlpha * 127);
    }

    /**
     * PNG ALPHA CHANNEL SUPPORT for imagecopymerge() by Sina Salek.
     * @return bool
     */
    protected function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);

        // copying relevant section from background to the cut resource
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

        // copying relevant section from watermark to the cut resource
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

        // insert cut resource to destination image
        return imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
    }

}