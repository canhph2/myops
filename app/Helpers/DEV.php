<?php

namespace app\Helpers;

/**
 * this is a DEV helper
 */
class DEV
{
    /**
     * get class name only, usage   DEVHelper::name(__CLASS__)
     *
     * @param string|null $classPath
     * @return string
     */
    public static function name(string $classPath = null): string
    {
        $pathArr = $classPath ? explode('\\', $classPath) : [];
        return end($pathArr);
    }

    /**
     * more detail with class and function name
     * use:
     *      DEVHelper::message($message, __CLASS__, __FUNCTION__);
     *
     * @param string $message
     * @param string|null $classPath
     * @param string|null $function
     * @return string
     */
    public static function message(string $message, string $classPath = null, string $function = null): string
    {
        return sprintf("[%s > %s]    %s", self::name($classPath), $function, $message);
    }
}
