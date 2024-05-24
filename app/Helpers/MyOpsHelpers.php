<?php
/**
 * This is MyOps helper
 * Should above class App and below all others in the combine file
 */

// === copy Laravel helpers ===
if (!function_exists('collect')) {

    /**
     * Create a collection from the given value.
     * @param array $arr
     * @return \App\Classes\Base\CustomCollection
     */
    function collect(array $arr): \App\Classes\Base\CustomCollection
    {
        return new \App\Classes\Base\CustomCollection($arr);
    }
}
// === end copy Laravel helpers ===
