<?php
/**
 * @author Dani Stark(danistark1.ca@gmail.com).
 */
namespace App\Controller;

use App\Entity\SensorEntity;
use App\Entity\WeatherLoggerEntity;
use App\Entity\WeatherReportEntity;
use App\WeatherConfiguration;
use App\WeatherStationLogger;
use App\Repository\SensorRepository;
use DateInterval;
use DateTime;
use Exception;
use Monolog\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
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
     * @var WeatherConfiguration
     */
    private $config;

    private $time_start;

    /**
     * SensorController constructor.
     *
     * @param SensorRepository|null $sensorRepository
     * @param WeatherStationLogger $logger
     * @param WeatherConfiguration $config
     */
    public function __construct(SensorRepository $sensorRepository, WeatherStationLogger $logger, WeatherConfiguration $config) {
        $this->response  = new Response();
        $this->response->headers->set('Content-Type', 'application/json');

        $this->request  = new Request();
        $this->logger = $logger;
        $this->sensorRepository = $sensorRepository;
        $this->config = $config;
        $this->response->headers->set('weatherStation-version', $this->config->getConfigKey('application.version'));
        $this->time_start = microtime(true);

    }

    /**
     * Get latest/oldest weatherData by sensorName
     * Params: order => asc/desc,
     *         orderField => humidity/temperature/station_id,room (insert_date_time by default)
     *         sensorName => (get by configured sensor names)
     *
     * @param Request $request
     * @Cache(maxage=5, public=true)
     * @return Response
     * @Route("/weatherstation/api/ordered", methods={"GET"}, name="get_by_ordered")
     */
    public function getByOrdered(Request $request): Response {
        if ($this->response->isNotModified($request)) {
            return $this->response;
        }
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
        $this->updateResponseHeader();
        $this->response->setETag(md5($this->response->getContent()));
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
     * @Cache(maxage=5, public=true)
     * @return Response
     * @Route("/weatherstation/api/id/ordered/{id}", methods={"GET"}, requirements={"id"="\d+"}, name="get_by_id_ordered")
     */
    public function getByIDOrdered(Request $request, int $id): Response {
        if ($this->response->isNotModified($request)) {
            return $this->response;
        }
        $operation = $request->get('operation') ?? null;
        $field = $request->get('field') ?? null;
        $value = $request->get('value') ?? null;
        $isOrderValid = !empty($operation) && !empty($field) && !empty($value);
        if ($isOrderValid) {
            $validSensorConfig = empty($this->config->getConfigs()['sensor']['config']) ? false : true;
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
        $this->updateResponseHeader();
        $this->response->setETag(md5($this->response->getContent()));
        return $this->response;
    }

    /**
     * Get all weatherData by stationID
     *
     * @param int $id The room id.
     * @param Request $request
     * @return Response
     * @Route("/weatherstation/api/id/{id}", methods={"GET"}, requirements={"id"="\d+"}, name="get_by_id")
     * @Cache(maxage=5, public=true)
     */
    public function getByID(int $id, Request $request): Response {
       if ($this->response->isNotModified($request)) {
            return $this->response;
       }
        $validSensorConfig = empty($this->config->getConfigs()['sensor']['config']) ? false : true;
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
        $this->updateResponseHeader();
        $this->response->setETag(md5($this->response->getContent()));
        return $this->response;
    }

    /**
     * Adds execution time to the response.
     */
    private function updateResponseHeader() {
        $time_end = microtime(true);
        $execution_time = ($time_end - $this->time_start);
        $this->response->headers->set('weatherStation-responseTime', $execution_time);
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
     * @param Request $request
     * @return Response
     * @Cache(maxage=5, public=true)
     * @Route("weatherstation/api/name/{name}", methods={"GET"}, requirements={"name"="\w+"}, name="get_by_name")
     */
    public function getByName(string $name, Request $request): Response {
        if ($this->response->isNotModified($request)) {
            return $this->response;
        }
        $validSensorConfig = empty($this->config->getConfigs()['sensor']['config']) ? false : true;
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
        $this->updateResponseHeader();
        $this->response->setETag(md5($this->response->getContent()));
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
        //TODO Validate delete params.
        $this->sensorRepository->delete($params);
        $this->response->setStatusCode(self::STATUS_OK);
        $this->updateResponseHeader();
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
        $validSensorConfig = empty($this->config->getConfigs()['sensor']['config']) ? false : true;
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
                        'interval' => $this->config->getConfigKey('pruning.records.interval') ?? $interval,

                        ];
                    $paramsReportData = [
                        'tableName' => WeatherReportEntity::class,
                        'dateTimeField' => 'lastSentDate',
                        'interval' => $this->config->getConfigKey('pruning.report.interval') ?? 2,

                    ];
                    $paramsLoggerData = [
                        'tableName' => WeatherLoggerEntity::class,
                        'dateTimeField' => 'insertDateTime',
                        'interval' => $this->config->getConfigKey('pruning.logs.interval') ?? 1,

                    ];
                    $this->sensorRepository->delete($paramsLoggerData);
                    $this->sensorRepository->delete($paramsSensorData);
                    $this->sensorRepository->delete($paramsReportData);
                    $this->response->setStatusCode(self::STATUS_OK);
                } else {
                    $this->response->setStatusCode(self::STATUS_EXCEPTION);
                }

            }
        }
        $this->updateResponseHeader();
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
        if (!isset($this->config->getConfigs()['sensor']['config'][$station])) {
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
        $sensorData = array_flip($this->config->getConfigs()['sensor']['config']);
        if (!isset($sensorData[$stationID])) {
            $valid = false;
            $this->response->setContent(self::VALIDATION_STATION_ID);
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $this->logger->log(self::VALIDATION_STATION_ID, ['id' => $stationID, 'sender' => $sender], Logger::ALERT);
        }
        return $valid;
    }
}
