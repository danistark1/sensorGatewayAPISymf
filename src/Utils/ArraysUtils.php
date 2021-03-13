<?php

namespace App\Utils;


use Monolog\Logger;

class ArraysUtils {

    /**
     * Validate Emails.
     *
     * @param array $emailsArray
     * @return bool
     */
    public static function validateEmails(array $emailsArray): bool {
        $valid = true;
        foreach($emailsArray as $key => $value) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $valid = false;
                break;
            }
        }
        return $valid;
    }

    /**
     * Sort array be column name.
     *
     * @param array $arr
     * @param string $col
     * @param int $dir
     * @return array
     */
    public static function arraySortByColumn(array &$arr, string $col, $dir = SORT_ASC) {
        $sort_col = array();
        foreach ($arr as $key=> $row) {
            $sort_col[$key] = $row[$col];
        }
        array_multisort($sort_col, $dir, $arr);
        return $sort_col;
    }
}
