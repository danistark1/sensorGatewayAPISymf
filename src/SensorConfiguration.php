<?php

namespace App;

use SplFileInfo;
//include('../config.php');

/**
 * Class WeatherConfiguration
 *
 * @package App
 */
class SensorConfiguration {
    /**
     * @var mixed
     */
    public $config;

    /**
     * WeatherConfiguration constructor.
     */
    public function __construct() {
        $this->config = include('../config.php');
    }

    /**
     * Get config by key.
     *
     * @param string $config
     * @return bool|string
     * @deprecated Use WeatherCacheHandler::getConfigKey
     */
    public function getConfigKey(string $config) {
        $result = explode('.', $config);
        $newArray = [];
        foreach($result as $key => $value) {
            $newArray[$key] = $value;
        }
        if (count($newArray) === 4 && isset($this->config[$newArray[0]][$newArray[1]][$newArray[2]][$newArray[3]])) {
           return $this->config[$newArray[0]][$newArray[1]][$newArray[2]][$newArray[3]];
        } elseif (count($newArray) === 3 && isset($this->config[$newArray[0]][$newArray[1]][$newArray[2]])) {
            return $this->config[$newArray[0]][$newArray[1]][$newArray[2]];
        } elseif(count($newArray) === 2 && isset($this->config[$newArray[0]][$newArray[1]])) {
            return $this->config[$newArray[0]][$newArray[1]];
        } else {
            return false;
        }
    }
}
