<?php

namespace App\Helpers;

/**
 * a data helper
 */
class Data
{
    /**
     * - support query:
     *    -    DATA::get($objOrArr, 'field1', 'subField2', ...moreFields)
     *    -    DATA::get($objOrArr, 'field1.subField2.moreFields...')
     */
    public static function get($objOrArr = null, ...$fields)
    {
        // validate
        if (!$objOrArr) return null; // END
        // handle
        //    fields
        $finalFields = [];
        foreach ($fields as $field) {
            $finalFields = array_merge($finalFields, StrHelper::contains($field, '.') ? explode('.', $field) : [$field]);
        }
        //    value
        $lastObjOrArr = $objOrArr;
        foreach ($finalFields as $field) {
            $lastObjOrArr = is_array($lastObjOrArr) ? ($lastObjOrArr[$field] ?? null) : ($lastObjOrArr->$field ?? null);
        }
        return $lastObjOrArr;
    }

    /**
     * - in case empty data will return an empty array []
     * - support query:
     *    -    DATA::getArr($objOrArr, 'field1', 'subField2', ...moreFields)
     *    -    DATA::getArr($objOrArr, 'field1.subField2.moreFields...')
     */
    public static function getArr($objOrArr = null, ...$fields)
    {
        return self::get($objOrArr, ...$fields) ?? [];
    }
}
