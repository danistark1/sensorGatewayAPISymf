<?php

namespace App;

use App\Controller\SensorController;
use App\Logger\SensorGatewayLogger;
use Throwable;

class SensorGatewayException extends \Exception
{
    public function __construct(
        SensorGatewayLogger $logger,
        $message = "",
        $code = 0,
        Throwable $previous = null
    )
    {
       parent::__construct($message, $code, $previous);

    }


}