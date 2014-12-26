<?php

/**
 * MiscHelper class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package helper
 */
class MiscHelper {
    
    public static $encodeSplitter = ' ';

    /**
     * 获取客户端IP地址
     * @return string
     */
    public static function getClientIp() {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        } else if (getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        } else if (getenv('HTTP_FORWARDED')) {
            $ip = getenv('HTTP_FORWARDED');
        } else if (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '0.0.0.0';
        }
        return $ip;
    }
    
    /**
     * 用户url的base64加密字符串。
     * @param string $data 待加密字符串。
     * @return string 加密后的字符串。
     */
    public static function base64UrlEncode($data) { 
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
    }
    
    /**
     * 用户解密self::base64UrlEncode()加密后的字符串。
     * @param string $data self::base64UrlEncode()加密后的字符串。
     * @return string 解密后的字符串
     */
    public static function base64UrlDecode($data) { 
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
    }
    
    /**
     * 公共加密字符串函数。
     * @param mixed $data 字符串或者多个字符串的数组。
     * @param string $privateKey 秘钥，默认为配置项'TeaBase.privateHashKey'。若mcrypt扩展未开启，则此函数仅用self::base64UrlEncode()加密。
     * @return string 返回加密字符串，如果$data为数组，则返回用self::$encodeSplitter连接起来的加密字符串。
     */
    public static function commonEncode($data, $privateKey = null) {
        if (empty($privateKey)) {
            $privateKey = Tea::getConfig('TeaBase.privateHashKey');
        }
        $strArr = array();
        if (!is_array($data)) {
            $strArr[] = $data;
        } else {
            $strArr = $data;
        }
        $hashedArr = array();
        foreach ($strArr as $str) {
            $privateKeyHash = hash('sha256', $privateKey, true);
            if (extension_loaded('mcrypt')) {
                $hashedArr[] = self::base64UrlEncode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $privateKeyHash, $str, MCRYPT_MODE_ECB));
            } else {
                $hashedArr[] = self::base64UrlEncode($str);
            }
        }
        return implode(self::$encodeSplitter, $hashedArr);
    }
    
    /**
     * 公共解密字符串函数。
     * @param string $data 加密后的字符串。
     * @param string $privateKey 秘钥，默认为配置项'TeaBase.privateHashKey'。若mcrypt扩展未开启，则此函数仅用self::base64UrlEncode()加密。
     * @return mixed 返回解密字符串或者多个解密字符串的数组。
     */
    public static function commonDecode($data, $privateKey = null) {
        if (empty($privateKey)) {
            $privateKey = Tea::getConfig('TeaBase.privateHashKey');
        }
        $hashedArr = explode(self::$encodeSplitter, trim($data));
        $strArr = array();
        foreach ($hashedArr as $str) {
            $privateKeyHash = hash('sha256', $privateKey, true);
            if (extension_loaded('mcrypt')) {
                $strArr[] = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $privateKeyHash, self::base64UrlDecode($str), MCRYPT_MODE_ECB));
            } else {
                $strArr[] = self::base64UrlDecode($str);
            }
        }
        if (count($strArr) === 1) {
            return implode(self::$encodeSplitter, $strArr);
        } else {
            return $strArr;
        }
    }
    
    /**
     * 加密链接参数。
     * @param array $params 链接http get请求的参数数组。
     * @param string $privateKey 秘钥，默认为配置项'TeaBase.privateHashKey'。若mcrypt扩展未开启，则此函数仅用base64_encode加密。
     * @return string 返回加密字符串。
     */
    public static function commonEncodeHttpQuery($params, $privateKey = null) {
        return self::commonEncode(http_build_query($params), $privateKey);
    }
    
    /**
     * 解密链接参数。
     * @param string $encodedQuery 加密后的http get请求字符串。
     * @param string $privateKey 秘钥，默认为配置项'TeaBase.privateHashKey'。若mcrypt扩展未开启，则此函数仅用base64_decode加密。
     * @return array 返回链接参数。
     */
    public static function commonDecodeHttpQuery($encodedQuery, $privateKey = null) {
        $params = array();
        parse_str(self::commonDecode($encodedQuery, $privateKey), $params);
        return $params;
    }
    
    /**
     * 加密数组。
     * @param array $arr 待加密的数组。
     * @param string $privateKey 秘钥，默认为配置项'TeaBase.privateHashKey'。若mcrypt扩展未开启，则此函数仅用base64_encode加密。
     * @return string 加密后的字符串。
     */
    public static function encodeArr($arr, $privateKey = null) {
        $json = json_encode($arr);
        return MiscHelper::commonEncode($json, $privateKey);
    }
    
    /**
     * 解密数组。
     * @param string $hashStr 被self::encodeArr()加密后的字符串。
     * @param string $privateKey 秘钥，默认为配置项'TeaBase.privateHashKey'。若mcrypt扩展未开启，则此函数仅用base64_encode加密。
     * @return array 解密后的数组。
     */
    public static function decodeArr($hashStr, $privateKey = null) {
        $json = MiscHelper::commonDecode($hashStr, $privateKey);
        return json_decode($json, true);
    }
    
    /**
     * 获取浏览器默认语言。
     * @param bool $lowercase 是否小写。
     * @return string 被检测到的默认语言。
     */
    public static function getDefaultLang($lowercase = false) {
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) && strlen($_SERVER["HTTP_ACCEPT_LANGUAGE"]) > 1) {
            $x = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
            $lang = array();
            $defaultLang = null;
            foreach ($x as $val) {
                if (preg_match("/(.*);q=([0-1]{0,1}.d{0,4})/i", $val, $matches)) {
                    $lang[$matches[1]] = (float) $matches[2];
                } else {
                    $lang[$val] = 1.0;
                }
            }
            $qval = 0.0;
            foreach ($lang as $key => $value) {
                if ($value > $qval) {
                    $qval = (float) $value;
                    $defaultLang = $key;
                }
            }
            return $lowercase ? strtolower($defaultLang) : $defaultLang;
        }
        return null;
    }

}
