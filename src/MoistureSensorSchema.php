<?php


namespace App;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class MoistureSensorSchema
 *
 * @package App
 */
class MoistureSensorSchema {

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
                'id' => new Assert\Optional([new Assert\PositiveOrZero()]),
                'name' => [new Assert\NotBlank([])],// Custom rule.
                'sensorReading' =>  [new Assert\NotBlank([])],
                'sensorLocation' => new Assert\Optional(),
                'sensorID' => [new AssertMoistureSensorID()], // Custom rule.
                'batteryStatus' => new Assert\Optional([new Assert\Length(['min' => 1]), new Assert\PositiveOrZero()]),
            ]);
        }
    }
}
