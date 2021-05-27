<?php

namespace App\Utils;


use Monolog\Logger;

class ArraysUtils {

    /**
     * Validate Emails.
     *
     * @param string $emails
     * @return bool
     */
    public static function validateEmails(string $email): bool {
        $valid = true;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $valid = false;
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
        foreach ($arr as $key => $row) {
            $sort_col[$key] = $row[$col];
        }
        array_multisort($sort_col, $dir, $arr);
        return $sort_col;
    }
}
