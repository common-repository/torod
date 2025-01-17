<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitc4876d8f1e5ac5d7da5e052aca7396c0
{
    public static $files = array (
        'ad155f8f1cf0d418fe49e248db8c661b' => __DIR__ . '/..' . '/react/promise/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'Y' => 
        array (
            'Yemlihakorkmaz\\TorodMmar\\' => 25,
        ),
        'R' => 
        array (
            'React\\Promise\\' => 14,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Stream\\' => 18,
            'GuzzleHttp\\Ring\\' => 16,
            'GuzzleHttp\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Yemlihakorkmaz\\TorodMmar\\' => 
        array (
            0 => __DIR__ . '/../../..' . '/src',
        ),
        'React\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/promise/src',
        ),
        'GuzzleHttp\\Stream\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/streams/src',
        ),
        'GuzzleHttp\\Ring\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/ringphp/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitc4876d8f1e5ac5d7da5e052aca7396c0::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitc4876d8f1e5ac5d7da5e052aca7396c0::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitc4876d8f1e5ac5d7da5e052aca7396c0::$classMap;

        }, null, ClassLoader::class);
    }
}
