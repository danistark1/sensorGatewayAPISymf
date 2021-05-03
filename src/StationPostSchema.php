<?php


namespace App;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class StationPostSchema
 *
 * @package App
 */
class StationPostSchema {

    public static $schema = [];

    public function __construct() {
        self::setSchema();
    }

    /**
     * Recipe Schema.
     */
    public static function setSchema() {
        if (empty(self::$schema)) {
            self::$schema = new Assert\Collection([
                'room' => [new AssertSensorName()], // Custom rule.
                'temperature' =>  [new Assert\NotBlank([])],
                'humidity' => [new Assert\NotBlank([])],
                'station_id' => new Assert\Optional([new Assert\Length(['min' => 1])]),
                'battery_status' => new Assert\Optional([new Assert\Length(['min' => 1]), new Assert\PositiveOrZero()]),
            ]);
        }
    }
}
