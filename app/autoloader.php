<?php
/**
 * Class autoloader
 *
 * @class Autoloader
 * @namespace app
 */
namespace app;

spl_autoload_register(['app\Autoloader', 'process']);

class Autoloader {

    /**
     * @param string $className
     */
    public static function process( $className ){
        $dir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
        $file = $dir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

        if ( file_exists($file) ) {
            include_once( $file );
        }
    }
}