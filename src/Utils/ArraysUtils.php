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
}
