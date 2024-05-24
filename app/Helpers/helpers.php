<?php
/**
 * This base helper
 * Should on top in the combine file
 */

// === helpers functions ===
define('ERROR_END', 1);
define('SUCCESS_END', 0);
if (!function_exists('exitApp')) {
    /**
     * @param int $code
     * @return void
     */
    function exitApp(int $code = SUCCESS_END): void
    {
        exit($code);
    }
}

if (!function_exists('d')) {
    /**
     * @param mixed ...$vars
     * @return void
     */
    function d(...$vars): void
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
    }
}

if (!function_exists('dd')) {
    /**
     * @param mixed ...$vars
     * @return void
     */
    function dd(...$vars): void
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
        die();
    }
}

// === end helpers functions ===
