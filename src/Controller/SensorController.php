<?php
/**
 * @author Dani Stark(danistark1.ca@gmail.com).
 */
namespace App\Controller;

use App\Entity\RoomGateway;
use App\Entity\WeatherLogger;
use App\Entity\WeatherReport;
use App\Logger\MonologDBHandler;
use App\Repository\RoomGatewayRepository;
use DateInterval;
use DateTime;
use Exception;

use Symfony\Component\HttpFoundation\Request;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use DateTimeZone;
use App\Utils\StationDateTime;
use App\Kernel;

/**
 * Class SensorController
 *
 * @package App\Controller
 */
class SensorController extends AbstractController {

    // Status Codes
    const STATUS_OK = 200;
    const STATUS_NO_CONTENT = 204;
    const STATUS_VALIDATION_FAILED = 400;
    const STATUS_NOT_FOUND = 404;
    const STATUS_EXCEPTION = 500;

    const VALIDATION_FAILED = "Validation failed.";
    const VALIDATION_BAD_CONFIG = "No sensor data is configured in your environment file.";
    const VALIDATION_NO_RECORD = "No record found.";
    const VALIDATION_STATION_NAME = "Invalid station name.";
    const VALIDATION_STATION_ID = "Invalid station id.";
    const VALIDATION_STATION_PARAMS = "Invalid post parameters.";

    /**
     * @var Response $response
     */
    private $response;

    /**
     *
     * @var Logger object|null
     */
    private $loggerService;

    /**
     * @var Kernel
     */
    private static $kernel;

    /**
     * @var RoomGatewayRepository
     */
    private $roomGatewayRepository;

    /**
     * SensorController constructor.
     *
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel, RoomGatewayRepository $roomGatewayRepository = null) {
        $this->response  = new Response();
        self::$kernel = $kernel;
        $container = $kernel->getContainer();
        $service = $container->get('monolog.logger.app-channel');
        $this->loggerService = $service;
        $this->roomGatewayRepository = $roomGatewayRepository;
    }

    /**
     * Get weatherData by stationID
     *
     * @param Request $request
     * @return Response
     * @Route("/weatherstation/api", methods={"GET"}, name="get_by_id2")
     */
    public function getByID2(Request $request): Response {
        //$stationID= $request->query->get();
        //dump($request->get);
       //dump($stationID);
//        $validSensorConfig = empty(self::constructSensorData()) ? false : true;
//        if (!$validSensorConfig) {
//            $this->updateResponse(
//                self::VALIDATION_BAD_CONFIG,
//                self::STATUS_VALIDATION_FAILED,
//                [
//                    'loggerMsg' => self::VALIDATION_BAD_CONFIG,
//                    'loggerContext' => ['method' => __FUNCTION__],
//                    'loggerLevel' => 'critical'
//                ]
//            );
//        } else {
//            $valid = $this->validateStationID($id);
//            if ($valid) {
//                $response = $this->roomGatewayRepository->findByQuery(['station_id' => $id]);
//                $this->validateResponse($response, $id);
//            }
//
//        }
        return $this->response;
    }

    /**
     * Get weatherData by stationID
     *
     * @param int $id The room id.
     * @Route("/weatherstation/api/id/{id}", methods={"GET"}, requirements={"id"="\d+"}, name="get_by_id")
     * @return Response
     */
    public function getByID(int $id): Response {
        $validSensorConfig = empty(self::constructSensorData()) ? false : true;
        if (!$validSensorConfig) {
            $this->updateResponse(
                self::VALIDATION_BAD_CONFIG,
                self::STATUS_VALIDATION_FAILED,
                [
                    'loggerMsg' => self::VALIDATION_BAD_CONFIG,
                    'loggerContext' => ['method' => __FUNCTION__],
                    'loggerLevel' => 'critical'
                ]
            );
        } else {
            $valid = $this->validateStationID($id);
            if ($valid) {
                $response = $this->roomGatewayRepository->findByQuery(['station_id' => $id]);
                $this->validateResponse($response, $id);
            }

        }
        return $this->response;
    }

    /**
     * Validate API response.
     */
    private function validateResponse(array $response, $sensorIdentifier) {
        $responseJson = $this->json($response)->getContent();
        if (empty($responseJson)){
            $this->response->setStatusCode(self::STATUS_NO_CONTENT);
            $this->loggerService->error(self::VALIDATION_NO_RECORD, ['id' => $sensorIdentifier]);
        } else {
            $this->response->setContent($responseJson);
            $this->response->setStatusCode(self::STATUS_OK);
        }
    }

    /**
     * Get weatherData by room name.
     *
     * @param string $name Room name
     * @Route("weatherstation/api/sensorname/{name}", methods={"GET"}, requirements={"name"="\w+"}, name="get_by_name")
     * @return Response
     */
    public function getByName(string $name): Response {
        $validSensorConfig = empty(self::constructSensorData()) ? false : true;
        if (!$validSensorConfig) {
            $this->updateResponse(
                self::VALIDATION_CONFIG,
                self::STATUS_VALIDATION_FAILED,
                [
                    'loggerMsg' => self::VALIDATION_BAD_CONFIG,
                    'loggerContext' => ['method' => __FUNCTION__],
                    'loggerLevel' => 'critical'
                ]
            );
        } else {
            $name = strtolower($name);
            $valid = $this->validateRoom($name);
            if ($valid) {
                $response = $this->roomGatewayRepository->findByQuery(['room' => $name]);
                $this->validateResponse($response, $name);
            }
        }
        return $this->response;
    }

    /**
     * Update Response object and return
     *
     * @param string $message
     * @param int $statusCode
     * @param $loggerParams
     * @return Response
     */
    private function updateResponse(string $message, int $statusCode, $loggerParams): Response {
        $this->response->setContent($message);
        $this->response->setStatusCode($statusCode);
        $this->loggerService->{$loggerParams['loggerLevel']}($loggerParams['loggerMsg'], $loggerParams['loggerContext']);
        return $this->response;
    }

    /**
     * Delete weather records based on the set interval.
     * Default is 1 day.
     *
     * @Route("weatherstation/api/delete/{parsms}", methods={"DELETE"}, name="task_delete")
     * @param string $entity The entity to delete from
     * @param string $dataTimeField Table dateTimeField to use
     * @param int $interval The interval to delete records
     * @return Response
     * @throws Exception
     */
    public function delete(array $params): Response {
//        $params = [
//            'tableName' => The Entity class,
//            'dateTimeField' => datetime field to use,
//            'interval' => ,
//
//        ];

        //TODO Validate delete parsms.
        $this->roomGatewayRepository->delete($params);
        $this->response->setStatusCode(self::STATUS_OK);
        return $this->response;
    }

    /**
     * Post weatherData.
     *
     * @Route("/weatherstation/api",  methods={"POST"}, name="post_by_name")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $interval Interval for sending weather report emails.
     * @return Response
     * @throws Exception
     */
    public function post(\Symfony\Component\HttpFoundation\Request $request, int $interval = 1): Response {
        $validSensorConfig = empty(self::constructSensorData()) ? false : true;
        if (!$validSensorConfig) {
            $this->updateResponse(
                self::VALIDATION_BAD_CONFIG,
                self::STATUS_VALIDATION_FAILED,
                [
                    'loggerMsg' => self::VALIDATION_BAD_CONFIG,
                    'loggerContext' => ['method' => __FUNCTION__],
                    'loggerLevel' => 'critical'
                ]
            );
        } else {
            // turn request data into an array
            $parameters = json_decode($request->getContent(), true);
            $parameters = $this->normalizeData($parameters);

            $valid = false;
            if ($parameters && is_array($parameters)) {
                $valid = $this->validatePost($parameters, __FUNCTION__);
            }
            if ($valid) {
                $validRoom = $this->validateRoom($parameters['room'], __FUNCTION__);
                $validStation = $this->validateStationID($parameters['station_id'], __FUNCTION__);
                if (!$validRoom || !$validStation) {
                    return $this->response;
                }

                $result = $this->roomGatewayRepository->save($parameters);
                if ($result) {
                    // Everytime a record is inserted, we want to call the delete API to delete records that are older than 1 day.
                    // keeping weather data for 24hrs.
                    //Delete  sensor data. Table room_gateway
                    $paramsSensorData = [
                        'tableName' => RoomGateway::class,
                        'dateTimeField' => 'insert_date_time',
                        'interval' => $_ENV["SENSORS_RECORDS_INTERVAL"] ?? $interval,

                        ];
                    $paramsReportData = [
                        'tableName' => WeatherReport::class,
                        'dateTimeField' => 'lastSentDate',
                        'interval' => $_ENV["READINGS_REPORT_INTERVAL"] ?? 2,

                    ];
                    $this->roomGatewayRepository->delete($paramsSensorData);
                    $this->roomGatewayRepository->delete($paramsReportData);
                    $this->response->setStatusCode(self::STATUS_OK);
                } else {
                    $this->response->setStatusCode(self::STATUS_EXCEPTION);
                }

            }
        }
        return $this->response;
    }

    /**
     * Normalize Post data
     *
     * @param array $parameters
     * @return array Normalized Data
     */
    private function normalizeData(array $parameters): array {
        $normalizedData = [];
        foreach($parameters as $param => $value) {
            $normalizedData += [strtolower($param) => $value];
        }
        return $normalizedData;
    }

    /**
     * Validate station post data.
     *
     * @param array $parameters
     * @return bool
     */
    private function validatePost(array $parameters, $sender): bool {
        $valid = true;
        $valid = (isset($parameters['temperature']) &&  isset($parameters['humidity']) && isset($parameters['station_id']) && isset($parameters['room'])) ? $valid : !$valid;
        if (!$valid) {
            $this->response->setContent(self::VALIDATION_STATION_PARAMS);
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $parameters['sender'] = $sender;
            $this->loggerService->error(self::VALIDATION_STATION_PARAMS, $parameters);
        }
        return $valid;
    }

    /**
     * Validate station name.
     *
     * @param string $station Station data.
     * @param string $sender Sending function.
     * @return bool|Response
     */
    private function validateRoom(string $station, string $sender = '') {
        $valid = true;
        if (!isset(self::constructSensorData()[$station])) {
            $valid = false;
            $this->response->setContent(self::VALIDATION_STATION_NAME);
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $this->loggerService->error(self::VALIDATION_NO_RECORD, ['name' => $station, 'sender'=> $sender]);
        }
        return $valid;
    }

    /**
     * Validate station id.
     *
     * @param int $stationID
     * @param string $sender Sending function.
     * @return bool
     */
    private function validateStationID(int $stationID, string $sender = ''): bool {
        $valid = true;
        $sensorData = array_flip(self::constructSensorData());
        if (!isset($sensorData[$stationID])) {
            $valid = false;
            $this->response->setContent(self::VALIDATION_STATION_ID);
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $this->loggerService->error(self::VALIDATION_STATION_ID, ['id' => $stationID, 'sender' => $sender]);
        }
        return $valid;
    }

    /**
     * Return the configured logging service.
     *
     * @return Logger|object|null
     */
    public static function getLoggerService(): Logger {
        $staticNonsense = new static(self::$kernel);
        return $staticNonsense->loggerService;
    }

    /**
     * Construct Sensor IDs/Names from the env. config
     *
     * @return array
     */
    public static function constructSensorData(): array {
        $sensorData = [];
        $lookupValue = 'SENSOR_';
        $envArray = $_ENV;
        foreach($envArray as $key => $value) {
            $sensorConfig = str_contains($key, $lookupValue);
            if ($sensorConfig) {
                $sensorData += [strtolower(substr($key,7, strlen($key)-7)) => $value];
            }
        }
        return $sensorData;
    }
}
