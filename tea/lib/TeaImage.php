<?php

/**
 * 图像类。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package lib
 */
class TeaImage {

    /**
     * 默认字体文件。
     * @var string
     */
    public static $commonFontFile;

    /**
     * 图像类型对应gd输出函数映射数组。
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
     * 验证码值。
     * @var string
     */
    private $_captchaVal;

    /**
     * gd资源。
     * @var resource
     */
    private $_gdRes;

    /**
     * 构造函数，设置默认字体为app.public.font目录下的STHeiti-Light.ttc字体文件。
     */
    public function __construct() {
        if (!extension_loaded('gd')) {
            throw new Exception('TeaImage requires gd extension loaded.');
        }
        $defaultFont = APP_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'font' . DIRECTORY_SEPARATOR . 'STHeiti-Light.ttc';
        if (file_exists($defaultFont)) {
            $this->setCommonFontFile($defaultFont);
        }
    }

    /**
     * 析构函数，销毁gd资源。
     */
    public function __destruct() {
        imagedestroy($this->_gdRes);
    }

    /**
     * 设置默认字体文件。
     * @param string $fontFile 字体文件路径。
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
     * 加载图像资源。
     * @param string $file 图像文件路径。
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
     * 获取图像宽度。
     * @return int
     */
    public function getImageWidth() {
        return imagesx($this->_gdRes);
    }

    /**
     * 获取图像高度。
     * @return int
     */
    public function getImageHeight() {
        return imagesy($this->_gdRes);
    }

    /**
     * 缩略图。
     * @param int $width 缩略图宽度。
     * @param int $height 缩略图高度。
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
     * 文字水印。
     * @param string $text 水印字符串。
     * @param string|array $position 表示位置的字符串，请参考$this->getCalcPositions()，或者表示左和上坐标的数组。
     * @param array $options 文字水印配置数组，请参考self::watermarkTextOptions().
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
     * 文字水印配置数组。
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
     * 图片水印。
     * @param string $file 水印图片文件路径。
     * @param string|array $position 表示位置的字符串，请参考$this->getCalcPositions()，或者表示左和上坐标的数组。
     * @param float $alpha 水印图片透明度，从0到1表示完全透明到完全不透明。
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
     * 验证码。
     * @param int $width 验证码图片宽度。
     * @param int $height 验证码图片高度。
     * @param array $options 验证码配置数组，请参考self::captchaOptions().
     * @return $this
     */
    public function captcha($width = 100, $height = 30, $options = array()) {
        $options = array_merge(self::captchaOptions(), $options);
        $this->_captchaVal = null;
        $this->_gdRes = imagecreatetruecolor($width, $height);
        // 填充背景
        $bgRgbColor = $this->getRgbColor($options['bgColor']);
        $bgColor = imagecolorallocate($this->_gdRes, $bgRgbColor['red'], $bgRgbColor['green'], $bgRgbColor['blue']);
        imagefill($this->_gdRes, 0, 0, $bgColor);
        // 画点
        for ($i = 0; $i < $options['pointsNum']; $i++) {
            $x = mt_rand(0, $width);
            $y = mt_rand(0, $height);
            $color = imagecolorallocate($this->_gdRes, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imagesetpixel($this->_gdRes, $x, $y, $color);
        }
        // 画直线
        for ($i = 0; $i < $options['linesNum']; $i++) {
            $x1 = mt_rand(0, $width / 3);
            $y1 = mt_rand(0, $height);
            $x2 = mt_rand($width / 3, $width);
            $y2 = mt_rand(0, $height);
            $color = imagecolorallocate($this->_gdRes, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
            imageline($this->_gdRes, $x1, $y1, $x2, $y2, $color);
        }
        // 画弧线
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
        // 设置字体颜色
        $fontRgbColor = $this->getRgbColor($options['fontColor']);
        $fontColor = imagecolorallocate($this->_gdRes, $fontRgbColor['red'], $fontRgbColor['green'], $fontRgbColor['blue']);
        // 获取随机字符串
        $text = str_shuffle($options['randomStr']);
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

        // 渲染每个字符。
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
     * 获取验证码值。
     * @return string
     */
    public function getCaptchaVal() {
        return $this->_captchaVal;
    }

    /**
     * 验证码配置数组。
     * @return array
     */
    public static function captchaOptions() {
        return array(
            'randomStr' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ这是东风路上看见老师的放假了看见老师看见的法律框架来得及',
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
     * 输出图像。
     * @param int $constImageType 表示图像格式的IMAGETYPE_XXX常量。
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
     * 保存图像。
     * @param string $file 保存图像文件路径。
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
     * 根据图像宽高计算图像相对于画布的位置字符串数组。
     * @param int $srcWidth 图像宽度。
     * @param int $srcHeight 图像高度。
     * @return array 图像相对于画布的位置字符串数组。
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
     * 计算文字盒子信息数组。
     * @param string $text 待计算的字符串。
     * @param string $fontFile 字体文件路径。
     * @param float $fontSize  字体大小。
     * @param float $fontAngle 字体角度。
     * @return array 文字盒子信息数组。
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
     * 获取rgb颜色信息数组。
     * @param string|array $val 16进制颜色或者rgb颜色数组，如 #FFFFFF 或 array(0, 0, 0)。
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
     * 16进制颜色转rgb颜色。
     * @param string $hexStr 16进制颜色字符串。
     * @param bool $returnStr 是否返回字符串，默认为false。true表示返回返回字符串，false表示返回数组。
     * @param string $seperator 如果返回字符串，此参数代表rgb颜色分隔符，默认为','。
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
     * rgba的alpha值转换成gd imagecopymerge()函数的alpha值。
     * @param float $rgbaAlpha rgba的alpha值，从0到1。
     * @return int gd imagecopymerge()函数的alpha值。
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
     * rgba的alpha值转换成gd imagecolorallocatealpha()函数的alpha值。
     * @param float $rgbaAlpha rgba的alpha值，从0到1。
     * @return int gd imagecolorallocatealpha()函数的alpha值。
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