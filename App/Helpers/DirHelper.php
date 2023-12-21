<?php

namespace App\Helpers;

class DirHelper
{
    /**
     * get home directory / get root directory of user
     * @return string
     */
    public static function getHomeDir(): string
    {
        return $_SERVER['HOME'];
    }

    public static function getWorkingDir(): string
    {
        return $_SERVER['PWD'];
    }

    /**
     * get current working directory of script
     * @return string
     */
    public static function getScriptDir(): string
    {
        return str_replace('/' . basename($_SERVER['SCRIPT_FILENAME']), '', sprintf("%s/%s", self::getWorkingDir(), $_SERVER['SCRIPT_FILENAME']));
    }

    // backup code
//    public static function getRepositoryDir()
//    {
//        return exec('git rev-parse --show-toplevel');
//    }
}
