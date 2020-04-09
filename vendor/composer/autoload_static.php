<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit52762a95ea338af13e588ef2519e8e1e
{
    public static $files = array (
        '1cfd2761b63b0a29ed23657ea394cb2d' => __DIR__ . '/..' . '/topthink/think-captcha/src/helper.php',
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '25072dd6e2470089de65ae7bf11d3109' => __DIR__ . '/..' . '/symfony/polyfill-php72/bootstrap.php',
        '320cde22f66dd4f5d3fd621d3e88b98f' => __DIR__ . '/..' . '/symfony/polyfill-ctype/bootstrap.php',
        'f598d06aa772fa33d905e87be6398fb1' => __DIR__ . '/..' . '/symfony/polyfill-intl-idn/bootstrap.php',
        'a9ed0d27b5a698798a89181429f162c5' => __DIR__ . '/..' . '/khanamiryan/qrcode-detector-decoder/lib/Common/customFunctions.php',
    );

    public static $prefixLengthsPsr4 = array (
        't' =>
            array (
                'think\\composer\\' => 15,
                'think\\captcha\\' => 14,
                'think\\' => 6,
            ),
        'a' =>
            array (
                'app\\' => 4,
            ),
        'Z' =>
            array (
                'Zxing\\' => 6,
            ),
        'S' =>
            array (
                'Symfony\\Polyfill\\Php72\\' => 23,
                'Symfony\\Polyfill\\Mbstring\\' => 26,
                'Symfony\\Polyfill\\Intl\\Idn\\' => 26,
                'Symfony\\Polyfill\\Ctype\\' => 23,
                'Symfony\\Component\\PropertyAccess\\' => 33,
                'Symfony\\Component\\OptionsResolver\\' => 34,
                'Symfony\\Component\\Mime\\' => 23,
                'Symfony\\Component\\Inflector\\' => 28,
                'Symfony\\Component\\HttpFoundation\\' => 33,
            ),
        'M' =>
            array (
                'MyCLabs\\Enum\\' => 13,
            ),
        'E' =>
            array (
                'Endroid\\QrCode\\' => 15,
                'Endroid\\Installer\\' => 18,
            ),
        'D' =>
            array (
                'DASPRiD\\Enum\\' => 13,
            ),
        'B' =>
            array (
                'BaconQrCode\\' => 12,
            ),
    );

    public static $prefixDirsPsr4 = array (
        'think\\composer\\' =>
            array (
                0 => __DIR__ . '/..' . '/topthink/think-installer/src',
            ),
        'think\\captcha\\' =>
            array (
                0 => __DIR__ . '/..' . '/topthink/think-captcha/src',
            ),
        'think\\' =>
            array (
                0 => __DIR__ . '/../..' . '/thinkphp/library/think',
                1 => __DIR__ . '/..' . '/topthink/think-image/src',
            ),

        'app\\' =>
            array (
                0 => __DIR__ . '/../..' . '/application',
            ),
        'Zxing\\' =>
            array (
                0 => __DIR__ . '/..' . '/khanamiryan/qrcode-detector-decoder/lib',
            ),
        'Symfony\\Polyfill\\Php72\\' =>
            array (
                0 => __DIR__ . '/..' . '/symfony/polyfill-php72',
            ),
        'Symfony\\Polyfill\\Mbstring\\' =>
            array (
                0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
            ),
        'Symfony\\Polyfill\\Intl\\Idn\\' =>
            array (
                0 => __DIR__ . '/..' . '/symfony/polyfill-intl-idn',
            ),
        'Symfony\\Polyfill\\Ctype\\' =>
            array (
                0 => __DIR__ . '/..' . '/symfony/polyfill-ctype',
            ),
        'Symfony\\Component\\PropertyAccess\\' =>
            array (
                0 => __DIR__ . '/..' . '/symfony/property-access',
            ),
        'Symfony\\Component\\OptionsResolver\\' =>
            array (
                0 => __DIR__ . '/..' . '/symfony/options-resolver',
            ),
        'Symfony\\Component\\Mime\\' =>
            array (
                0 => __DIR__ . '/..' . '/symfony/mime',
            ),
        'Symfony\\Component\\Inflector\\' =>
            array (
                0 => __DIR__ . '/..' . '/symfony/inflector',
            ),
        'Symfony\\Component\\HttpFoundation\\' =>
            array (
                0 => __DIR__ . '/..' . '/symfony/http-foundation',
            ),
        'MyCLabs\\Enum\\' =>
            array (
                0 => __DIR__ . '/..' . '/myclabs/php-enum/src',
            ),
        'Endroid\\QrCode\\' =>
            array (
                0 => __DIR__ . '/..' . '/endroid/qrcode/src',
            ),
        'Endroid\\Installer\\' =>
            array (
                0 => __DIR__ . '/..' . '/endroid/installer/src',
            ),
        'DASPRiD\\Enum\\' =>
            array (
                0 => __DIR__ . '/..' . '/dasprid/enum/src',
            ),
        'BaconQrCode\\' =>
            array (
                0 => __DIR__ . '/..' . '/bacon/bacon-qr-code/src',
            ),
    );

    public static $prefixesPsr0 = array (
        'P' =>
            array (
                'PHPExcel' =>
                    array (
                        0 => __DIR__ . '/..' . '/phpoffice/phpexcel/Classes',
                    ),
            ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit52762a95ea338af13e588ef2519e8e1e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit52762a95ea338af13e588ef2519e8e1e::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit52762a95ea338af13e588ef2519e8e1e::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
