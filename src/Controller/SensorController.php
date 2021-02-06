<?php
/**
 * @author Dani Stark(danistark1.ca@gmail.com).
 */
namespace App\Controller;

use App\Entity\SensorEntity;
use App\Entity\WeatherLoggerEntity;
use App\Entity\WeatherReportEntity;
use App\WeatherStationLogger;
use App\Repository\SensorRepository;
use DateInterval;
use DateTime;
use Exception;
use Monolog\Logger;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * @var Request
     */
    private $request;

    /**
     * @var SensorRepository
     */
    private $sensorRepository;

    /**
     * @var WeatherStationLogger
     */
    private $logger;

    /**
     * SensorController constructor.
     *
     * @param SensorRepository|null $sensorRepository
     * @param WeatherStationLogger $logger
     */
    public function __construct(SensorRepository $sensorRepository, WeatherStationLogger $logger) {
        $this->response  = new Response();
        $this->response->headers->set('Content-Type', 'application/json');
        $this->request  = new Request();
        $this->logger = $logger;
        $this->sensorRepository = $sensorRepository;
    }

    /**
     * Get latest/oldest weatherData by sensorName
     * Params: order => asc/desc,
     *         orderField => humidity/temperature/station_id,room (insert_date_time by default)
     *         sensorName => (get by configured sensor names)
     *
     * @param Request $request
     * @return Response
     * @Route("/weatherstation/api/ordered", methods={"GET"}, name="get_by_ordered")
     */
    public function getByOrdered(Request $request): Response {
        $order = $request->get('order') ?? 'desc';
        $orderField = $request->get('orderField');
        $sensorName = $request->get('sensorName');
        $valid = $this->validateSensorName($sensorName);

        if (!$valid) {
            $this->response->setContent(self::VALIDATION_FAILED);
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $this->logger->log(self::VALIDATION_FAILED, [
                'method' => __FUNCTION__,
                'order' => $order,
                'orderField' => $orderField
            ], Logger::ALERT);
        } else {
            $response = $this->sensorRepository->findOrdered($sensorName, $orderField = 'insert_date_time', $order);
            $this->validateResponse($response);
        }
        return $this->response;
    }

    /**
     * Get weatherData by ID ordered by field
     * Ex. weatherstation/api/id/{6126}?field={temperature}&value={1}&operation={>}
     * Gets all records for station 6126 that have temp >=1
     *
     * Operations >, >=, <, <=, <>
     *
     * @param Request $request
     * @param int $id
     * @return Response
     * @Route("/weatherstation/api/id/ordered/{id}", methods={"GET"}, requirements={"id"="\d+"}, name="get_by_id_ordered")
     */
    public function getByIDOrdered(Request $request, int $id, WeatherStationLogger $logger): Response {
        $operation = $request->get('operation') ?? null;
        $field = $request->get('field') ?? null;
        $value = $request->get('value') ?? null;
        $isOrderValid = !empty($operation) && !empty($field) && !empty($value);
        if ($isOrderValid) {
            $validSensorConfig = empty(self::constructSensorData()) ? false : true;
            if (!$validSensorConfig) {
                $this->updateResponse(
                    self::VALIDATION_BAD_CONFIG,
                    self::STATUS_VALIDATION_FAILED,
                    [
                        'loggerMsg' => self::VALIDATION_BAD_CONFIG,
                        'loggerContext' => ['method' => __FUNCTION__],
                        'loggerLevel' => Logger::ALERT
                    ]
                );
            } else {
                $valid = $this->validateStationID($id);
                if ($valid) {
                    $response = $this->sensorRepository->findByQueryOperation(
                        [
                            'station_id' => $id,
                            'operation' => $operation,
                            'field' => $field,
                            'value' => $value
                        ]
                    );
                    $this->validateResponse($response, $id);
                }

            }
        } else {
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $this->response->setContent(self::VALIDATION_FAILED);
            $this->logger->log(self::VALIDATION_FAILED, [
                'method' => __FUNCTION__,
                'sentParams' => [
                    'operation' => $operation,
                    'field' => $field,
                    'value' => $value
                ]
            ],
                Logger::ALERT
            );
        }
        return $this->response;
    }

    /**
     * Get all weatherData by stationID
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
                    'loggerLevel' => Logger::CRITICAL
                ]
            );
        } else {
            $valid = $this->validateStationID($id, __FUNCTION__);
            if ($valid) {
                $response = $this->sensorRepository->findByQuery(['station_id' => $id]);
                $this->validateResponse($response, $id);
            }

        }
        return $this->response;
    }

    /**
     * Validate API response.
     */
    private function validateResponse(array $response, $sensorIdentifier = '') {
        $responseJson = $this->json($response)->getContent();
        if (empty($responseJson)){
            $this->response->setStatusCode(self::STATUS_NO_CONTENT);
            $this->logger->log(self::VALIDATION_NO_RECORD, ['id' => $sensorIdentifier], Logger::INFO);
        } else {
            $this->response->setContent($responseJson);
            $this->response->setStatusCode(self::STATUS_OK);
        }
    }

    /**
     * Get weatherData by room name.
     *
     * @param string $name Room name
     * @Route("weatherstation/api/name/{name}", methods={"GET"}, requirements={"name"="\w+"}, name="get_by_name")
     * @return Response
     */
    public function getByName(string $name): Response {
        $validSensorConfig = empty(self::constructSensorData()) ? false : true;
        if (!$validSensorConfig) {
            $this->updateResponse(
                self::VALIDATION_BAD_CONFIG,
                self::STATUS_VALIDATION_FAILED,
                [
                    'loggerMsg' => self::VALIDATION_BAD_CONFIG,
                    'loggerContext' => ['method' => __FUNCTION__],
                    'loggerLevel' => Logger::CRITICAL
                ]
            );
        } else {
            $name = strtolower($name);
            $valid = $this->validateSensorName($name, __FUNCTION__);
            if ($valid) {
                $response = $this->sensorRepository->findByQuery(['room' => $name]);
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
     */
    private function updateResponse(string $message, int $statusCode, $loggerParams) {
        $this->response->setContent($message);
        $this->response->setStatusCode($statusCode);
        $this->logger->log($loggerParams['loggerMsg'], $loggerParams['loggerContext'],$loggerParams['loggerLevel']);
    }

    /**
     * Delete weather records based on the set interval.
     * Default is 1 day.
     *
     * @Route("weatherstation/api/delete/{parsms}", methods={"DELETE"}, name="task_delete")
     * @param array $params
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(array $params): Response {
        //TODO Validate delete parsms.
        $this->sensorRepository->delete($params);
        $this->response->setStatusCode(self::STATUS_OK);
        return $this->response;
    }

    /**
     * Post weatherData.
     *
     * @Route("/weatherstation/api",  methods={"POST"}, name="post_by_name")
     * @param Request $request
     * @param int $interval Interval for sending weather report emails.
     * @return Response
     * @throws Exception
     */
    public function post(Request $request, int $interval = 1): Response {
        $validSensorConfig = empty(self::constructSensorData()) ? false : true;
        if (!$validSensorConfig) {
            $this->updateResponse(
                self::VALIDATION_BAD_CONFIG,
                self::STATUS_VALIDATION_FAILED,
                [
                    'loggerMsg' => self::VALIDATION_BAD_CONFIG,
                    'loggerContext' => ['method' => __FUNCTION__],
                    'loggerLevel' => Logger::CRITICAL
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
                $validRoom = $this->validateSensorName($parameters['room'], __FUNCTION__);
                $validStation = $this->validateStationID($parameters['station_id'], __FUNCTION__);
                if (!$validRoom || !$validStation) {
                    return $this->response;
                }

                $result = $this->sensorRepository->save($parameters);
                // TODO UPDATE date for both tables to be insert_date_time.
                if ($result) {
                    // Everytime a record is inserted, we want to call the delete API to delete records that are older than 1 day.
                    // keeping weather data for 24hrs.
                    //Delete  sensor data. Table room_gateway
                    $paramsSensorData = [
                        'tableName' => SensorEntity::class,
                        'dateTimeField' => 'insert_date_time',
                        'interval' => $_ENV["SENSORS_RECORDS_INTERVAL"] ?? $interval,

                        ];
                    $paramsReportData = [
                        'tableName' => WeatherReportEntity::class,
                        'dateTimeField' => 'lastSentDate',
                        'interval' => $_ENV["READINGS_REPORT_INTERVAL"] ?? 2,

                    ];
                    $this->sensorRepository->delete($paramsSensorData);
                    $this->sensorRepository->delete($paramsReportData);
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
            $this->logger->log(self::VALIDATION_STATION_PARAMS, $parameters, Logger::ALERT);
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
    private function validateSensorName(string $station, string $sender = '') {
        $valid = true;
        if (!isset(self::constructSensorData()[$station])) {
            $valid = false;
            $this->response->setContent(self::VALIDATION_STATION_NAME);
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $this->logger->log(self::VALIDATION_STATION_NAME, ['name' => $station, 'sender'=> $sender], Logger::ALERT);
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
            $this->logger->log(self::VALIDATION_STATION_ID, ['id' => $stationID, 'sender' => $sender], Logger::ALERT);
        }
        return $valid;
    }

    /**
     * Construct Sensor IDs/Names from the env. config
     *
     * @return array
     */
    public static function constructSensorData(): array {
        $sensorData = [];
        $lookupValue = 'SENSOR_CONFIG_';
        $envArray = $_ENV;
        foreach($envArray as $key => $value) {
            $sensorConfig = str_contains($key, $lookupValue);
            if ($sensorConfig) {
                $sensorData += [strtolower(substr($key,14, strlen($key)-7)) => $value];
            }
        }
        return $sensorData;
    }

    /**
     * Construct Upper/Lower Humidity & Temperature configured values from the env. config
     *
     * @return array
     */
    public static function constructNotificationsData(): array {
        $notificationsData = [];
        $envArray = $_ENV;
        foreach($envArray as $key => $value) {
            if (preg_match('(UPPER_TEMPERATURE|LOWER_TEMPERATURE|UPPER_HUMIDITY|LOWER_HUMIDITY)', $key) === 1) {
                $notificationsData += [strtolower(substr($key,0, strlen($key))) => $value];
            }
        }
        return $notificationsData;
    }
}
