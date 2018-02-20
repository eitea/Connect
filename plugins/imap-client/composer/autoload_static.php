<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit1fdb3918066492744f49b1ce9adc6867
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'SSilence\\ImapClient\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'SSilence\\ImapClient\\' => 
        array (
            0 => __DIR__ . '/..' . '/ssilence/php-imap-client/ImapClient',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit1fdb3918066492744f49b1ce9adc6867::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit1fdb3918066492744f49b1ce9adc6867::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}