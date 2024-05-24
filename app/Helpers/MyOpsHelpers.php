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
     * @return CustomCollection
     */
    function collect(array $arr): CustomCollection
    {
        return new CustomCollection($arr);
    }
}
