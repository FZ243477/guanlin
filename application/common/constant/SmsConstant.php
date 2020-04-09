<?php

namespace app\common\constant;

class SmsConstant
{
    const SMS_TYPE_REGISTER = 1; //用户注册验证码
    const SMS_TYPE_EDIT_PASSWORD = 2; //找回/修改登陆密码
    const SMS_TYPE_PAY_PASSWORD = 3; //修改/设置支付密码
    const SMS_TYPE_BINDING = 4; //第三方绑定手机号
    const SMS_TYPE_OLD_TELEPHONE = 5; //修改绑定原手机验证
    const SMS_TYPE_NEW_TELEPHONE = 6; //修改绑定新手机验证


    const SMS_TYPE_REGISTER_NAME = '您的手机验证码为：{code},为保证验证安全，请在5分钟之内进行验证。';
    const SMS_TYPE_EDIT_PASSWORD_NAME = '您正在找回/修改密码,验证码为：{code},为保证验证安全，请在5分钟之内进行验证。';
    const SMS_TYPE_PAY_PASSWORD_NAME = '您正在修改/设置支付密码,验证码为：{code},为保证验证安全，请在5分钟之内进行验证。';
    const SMS_TYPE_BINDING_NAME = '您的手机验证码为：{code},为保证验证安全，请在5分钟之内进行验证。';
    const SMS_TYPE_OLD_TELEPHONE_NAME = '您正在修改绑定原手机,验证码为：{code},为保证验证安全，请在5分钟之内进行验证。';
    const SMS_TYPE_NEW_TELEPHONE_NAME = '您正在修改绑定新手机,验证码为：{code},为保证验证安全，请在5分钟之内进行验证。';

    static $sms_type_array = [
        self::SMS_TYPE_REGISTER => self::SMS_TYPE_REGISTER_NAME,
        self::SMS_TYPE_EDIT_PASSWORD => self::SMS_TYPE_EDIT_PASSWORD_NAME,
        self::SMS_TYPE_PAY_PASSWORD => self::SMS_TYPE_PAY_PASSWORD_NAME,
        self::SMS_TYPE_BINDING => self::SMS_TYPE_BINDING_NAME,
        self::SMS_TYPE_OLD_TELEPHONE => self::SMS_TYPE_OLD_TELEPHONE_NAME,
        self::SMS_TYPE_NEW_TELEPHONE => self::SMS_TYPE_NEW_TELEPHONE_NAME,
    ];

    static function sms_type_value($sms_type)
    {
        self::$sms_type_array[$sms_type];
    }
}