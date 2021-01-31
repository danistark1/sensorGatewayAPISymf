<?php

namespace App;

use App\Logger\MonologDBHandler;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;

/**
 * Class WeatherStationLogger
 *
 * @package App
 */
class WeatherStationLogger extends MonologDBHandler {

    /**
     * WeatherStationLogger constructor.
     *
     * @param EntityManagerInterface $em
     * @param int $level
     * @param bool $bubble
     */
    public function __construct(EntityManagerInterface $em, $level = Logger::API, $bubble = true) {
        parent::__construct($em, $level, $bubble);
    }

    /**
     * Log a message to weather_logger table.
     *
     * @param $message
     * @param array $context
     * @param $level
     * @param $level_name
     * @param array $extra
     */
    public function log($message, array $context, $level, array $extra = []) {
        $level_name = Logger::getLevelName($level);
        $record = [
            'message' => $message,
            'level' => $level,
            'level_name' =>  $level_name,
            'extra' => $extra,
            'context' => $context
        ];
        $this->handle($record);
    }
}
