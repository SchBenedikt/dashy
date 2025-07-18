<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit02f65bd1234265ebbe3a5903aeb38f9c
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'OCA\\Dashy\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'OCA\\Dashy\\' => 
        array (
            0 => __DIR__ . '/../..' . '/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'OCA\\Dashy\\AppInfo\\Application' => __DIR__ . '/../..' . '/lib/AppInfo/Application.php',
        'OCA\\Dashy\\Controller\\PageController' => __DIR__ . '/../..' . '/lib/Controller/PageController.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit02f65bd1234265ebbe3a5903aeb38f9c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit02f65bd1234265ebbe3a5903aeb38f9c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit02f65bd1234265ebbe3a5903aeb38f9c::$classMap;

        }, null, ClassLoader::class);
    }
}
