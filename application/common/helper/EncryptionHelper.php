<?php


namespace app\common\helper;

use app\common\constant\EncryptionConstant;

trait EncryptionHelper
{

    /**
     * 密码加密
     * @param $password
     * @return string
     */
    private function md5_encryption($password)
    {
        return md5(md5($password).EncryptionConstant::MD5_KEY);
    }

    /**
     * 加密方法
     * @param $code
     * @return string
     */
    private function base64Encryption($code)
    {
        $begin = EncryptionConstant::BEGIN_KEY;
        $end = EncryptionConstant::END_KEY;
        $new_str = base64_encode($begin.$code.$end);
        return $new_str;
    }

    /**
     * 解密方法
     * @param $code
     * @return string
     */
    private function base64Decryption($code)
    {
        $begin = EncryptionConstant::BEGIN_KEY;
        $end = EncryptionConstant::END_KEY;
        $new_str = base64_decode($code);
        $b = mb_strpos($new_str,$begin) + mb_strlen($begin);
        $e = mb_strpos($new_str,$end) - $b;
        return mb_substr($new_str,$b,$e);
    }
}