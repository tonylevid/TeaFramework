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

    public static function getClientIp() {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else if(getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } else if(getenv('HTTP_X_FORWARDED')) {
            $ip = getenv('HTTP_X_FORWARDED');
        } else if(getenv('HTTP_FORWARDED_FOR')) {
            $ip = getenv('HTTP_FORWARDED_FOR');
        } else if(getenv('HTTP_FORWARDED')) {
           $ip = getenv('HTTP_FORWARDED');
        } else if(getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        } else {
            $ip = '0.0.0.0';
        }
        return $ip;
    }
    
    public static function commonEncode($data, $privateKey = 'TeaFrameworkRocks') {
        $strArr = array();
        if (is_string($data)) {
            $strArr[] = $data;
        } else if (is_array($data)) {
            $strArr = $data;
        }
        $hashedArr = array();
        foreach ($strArr as $str) {
            $privateKeyHash = hash('sha256', $str, true);
            if (extension_loaded('mcrypt')) {
                $hashedArr[] = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $privateKeyHash, $str, MCRYPT_MODE_ECB));
            } else {
                $hashedArr[] = base64_encode($str);
            }
        }
        return implode(' ', $hashedArr);
    }
    
    public static function commonDecode($data, $privateKey = 'TeaFrameworkRocks') {
        $hashedArr = explode(' ', trim($data));
        $strArr = array();
        foreach ($hashedArr as $str) {
            $privateKeyHash = hash('sha256', $str, true);
            if (extension_loaded('mcrypt')) {
                $strArr[] = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $privateKeyHash, base64_decode($str), MCRYPT_MODE_ECB));
            } else {
                $strArr[] = base64_decode($str);
            }
        }
        if (count($strArr) === 1) {
            return implode(' ', $strArr);
        } else {
            return $strArr;
        }
    }

}
