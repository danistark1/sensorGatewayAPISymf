<?php

namespace App;

use App\Logger\SensorGatewayLogger;

class SensorGatewayExceptionHandler
{

    private $logger;

    /**
     * @param SensorGatewayLogger $logger
     */
    public function __construct(SensorGatewayLogger $logger) {
        $this->logger = $logger;
    }

    public function handleException(\Throwable $e) {
        $this->logger->log($e->getMessage(), ['line' =>$e->getLine()], $e->getCode());

    }

}