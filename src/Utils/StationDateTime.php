<?php
/**
 * @author Dani Stark.
 */
namespace App\Utils;

use DateTimeZone;

/**
 * Class StationDateTime
 *
 * @package App\Utils
 */
class StationDateTime {

    /**
     * Return date now with preset format/timezone
     * Ex. Format:
     * DateTime Y-m-d H:i:s
     * Date Y-m-d
     * Time H:i:s
     *
     * @param string $duration
     * @param bool $formated Return the dateTime object, or a formatted date or time.
     * @param string $format
     * @param string $timeZone
     * @return string
     * @throws \Exception
     */
    public static function dateNow($duration = '', bool $formatted = false, $format = 'Y-m-d H:i:s', $timeZone = 'America/Toronto') {
        if ($duration !== '') {
            $period = new \DateInterval($duration);
        }
        $timeZone = new DateTimeZone($_ENV["TIMEZONE"] ?? $timeZone);
        $currentDateTime = new \DateTime('now', $timeZone);
        if (isset($period) && $period instanceof \DateInterval) {
            $currentDateTime->sub($period);
        }
        $formatted = $formatted === true ? $currentDateTime->format($format): $currentDateTime;
        return $formatted;
    }
}
