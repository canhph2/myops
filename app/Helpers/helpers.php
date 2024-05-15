<?php

// === helpers functions ===


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
