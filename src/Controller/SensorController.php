<?php
namespace App\Controller;

use App\Entity\SensorEntity;
use App\Entity\SensorHistoricReadings;
use App\Entity\SensorLoggerEntity;
use App\Entity\SensorMoistureEntity;
use App\Entity\SensorReportEntity;
use App\APISchemas\MoistureSensorSchema;
use App\Repository\SensorMoistureRepository;
use App\APISchemas\StationPostSchema;
use App\GatewayCache\SensorCacheHandler;
use App\SensorConfiguration;
use App\Logger\SensorGatewayLogger;
use App\Repository\SensorRepository;
use App\Repository\SensorHistoricReadingsRepository;
use DateInterval;
use DateTime;
use Exception;
use Monolog\Logger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use DateTimeZone;
use App\Utils\SensorDateTime;
use App\Kernel;

/**
 * Class SensorController
 *
 * @author Dani Stark
 * @package App\Controller
 */
class SensorController extends AbstractController  {
    // Status Codes
    const STATUS_OK = 200;
    const STATUS_NO_CONTENT = 204;
    const STATUS_VALIDATION_FAILED = 400;
    const STATUS_NOT_FOUND = 404;
    const STATUS_EXCEPTION = 500;

    const VALIDATION_FAILED = "Validation failed.";
    const VALIDATION_FAILED_ORDER_FIELDS = "Invalid order fields.";
    const VALIDATION_BAD_CONFIG = "No sensor data is configured in your environment file.";
    const VALIDATION_NO_RECORD = "No record found.";
    const VALIDATION_STATION_NAME = "Invalid station name.";
    const VALIDATION_STATION_ID = "Invalid station id.";
    const VALIDATION_STATION_PARAMS = "Invalid post parameters.";

    // Irregular readings range.

    const LOWEST_ABNORMAL_TEMP      = 'lowest_abnormal_temp';
    const LOWEST_ABNORMAL_HUMIDITY  = 'lowest_abnormal_humidity';
    const HIGHEST_ABNORMAL_TEMP     = 'highest_abnormal_temp';
    const HIGHEST_ABNORMAL_HUMIDITY = 'highest_abnormal_humidity';

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

    /** @var SensorHistoricReadingsRepository */
    private $sensorHistoricReadingsRepository;

    /**
     * @var SensorGatewayLogger
     */
    private $logger;

    /** @var float|string Capture response execution time */
    private $time_start;

    /** @var FilesystemAdapter  */
    private $cache;

    /** @var SensorCacheHandler  */
    private $configCache;

    /**
     * SensorController constructor.
     *
     * @param SensorRepository|null $sensorRepository
     * @param SensorGatewayLogger $logger
     * @param SensorCacheHandler $cacheHandler
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(
        SensorRepository $sensorRepository,
        SensorHistoricReadingsRepository $sensorHistoricReadingsRepository,
        SensorGatewayLogger $logger,
        SensorCacheHandler $cacheHandler) {
        $this->response  = new Response();
        $this->response->headers->set('Content-Type', 'application/json');
        $this->request  = new Request();
        $this->logger = $logger;
        $this->sensorRepository = $sensorRepository;
        $this->sensorHistoricReadingsRepository = $sensorHistoricReadingsRepository;
        $this->configCache = $cacheHandler;
        $this->response->headers->set('weatherStation-version', $this->configCache->getConfigKey('application-version'));
        $this->time_start = microtime(true);
        $this->cache = new FilesystemAdapter();
        $configsDefined = $this->configCache->getAllConfigs();
        $this->checkConfiguration($configsDefined);
    }

    /**
     * Check if configuration has been defined.
     *
     * @param $configsDefined
     * @return Response
     */
    private function checkConfiguration($configsDefined): void {
        if (empty($configsDefined)) {
            $trace = (new \Exception)->getTrace();
            throw new HttpException(401, 'Something went wrong. Configuration has not been defined.');
        }
    }

    /**
     * Get latest/oldest weatherData by sensorName
     * Params: order => asc/desc,
     *         orderField => humidity/temperature/station_id,room (insert_date_time by default)
     *         sensorName => (get by configured sensor names)
     *
     * @param Request $request
     * @return Response
     * @Route("/weatherstation/api/name/ordered", methods={"GET"}, name="get_by_name_ordered")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getByNameOrdered(Request $request): Response {
        $orderDirection = $request->get('order') ?? 'desc';
        $orderField = $request->get('orderField') ?? 'insert_date_time';
        $sensorName = $request->get('sensorName');
        $validOrder = $this->validateOrderFields(['orderDirection' => $orderDirection, 'orderField' => $orderField]);
        $valid = $this->validateSensorName($sensorName, __CLASS__.__FUNCTION__);
        if ($valid && $validOrder) {
            $response = $this->sensorRepository->findOrdered($sensorName, $orderField, $orderDirection);
            $this->validateResponse($response);
        }
        $this->updateResponseHeader();
        return $this->response;
    }

    /**
     * Validate order fields
     *
     * @param array $fields
     * @return bool
     */
    private function validateOrderFields(array $fields): bool {
        $valid = $validOperation = $validDirection =  true;
        if (isset($fields['orderField'])) {
            $orderField = $fields['orderField'];
            $validField = in_array($orderField, SensorEntity::getValidFieldNames()) ?: false;
            $valid = $validField;
        }
        if (isset($fields['orderDirection'])) {
            $orderDirection = $fields['orderDirection'];
            $validDirection = ($orderDirection === 'desc' || $orderDirection === 'asc');
        }
        if (isset($fields['operation'])) {
            $validOperation  = in_array($fields['operation'], ['>', '>=', '<', '<=', '<>']);
        }
        if (!$valid || !$validOperation || $validDirection) {
            $this->updateResponse(
                self::VALIDATION_FAILED_ORDER_FIELDS,
                self::STATUS_VALIDATION_FAILED,
                ['loggerMsg' =>  self::VALIDATION_FAILED_ORDER_FIELDS,
                    'loggerContext' => [
                        'method' => __CLASS__.__FUNCTION__,
                        'orderDirection' => isset($fields['orderDirection']) ? $fields['orderDirection'] : 'not set',
                        'orderField' => $orderField,
                        'operation' => isset($fields['operation']) ? $fields['operation'] : 'not set'
                    ],
                    'loggerLevel' => Logger::ALERT
                ]
            );
        }
        return $valid && $validOperation && $validDirection;
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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getByIDOrdered(Request $request, int $id): Response {
        $operation = $request->get('operation') ?? '>';
        $field = $request->get('orderField') ??  'insert_date_time';
        $value = $request->get('value') ?? null;
        $validOrder = $this->validateOrderFields(['operation' => $operation, 'orderField' => $field]);
        $isOrderValid = !empty($operation) && !empty($field) && !empty($value) && $validOrder;
        $validStationID = $this->validateStationID($id, __CLASS__.__FUNCTION__);
        if ($isOrderValid && $validStationID) {
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
        $this->updateResponseHeader();
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
     *
     * @param array $response
     * @param string $sensorIdentifier
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
     * @Route("weatherstation/api/name/{name}", methods={"GET"}, requirements={"name"="\w+"}, name="get_by_name")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getByName(string $name, Request $request): Response {
        $name = strtolower($name);
        $validSensorConfig = $this->validateSensorName($name, __CLASS__.__FUNCTION__);
        if ($validSensorConfig) {
            $response = $this->sensorRepository->findByQuery(['room' => $name]);
            $this->validateResponse($response, $name);
            $this->updateResponseHeader();
        }
        return $this->response;
    }

    /**
     * Get all weatherData by stationID
     *
     * @param int $id The room id.
     * @param Request $request
     * @return Response
     * @Route("/weatherstation/api/id/{id}", methods={"GET"}, requirements={"id"="\d+"}, name="get_by_id")
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getByID(int $id, Request $request): Response {
        $validSensorConfig = $this->validateStationID($id, __CLASS__.__FUNCTION__);

        if ($validSensorConfig) {
            $response = $this->sensorRepository->findByQuery(['station_id' => $id]);
            $this->validateResponse($response, $id);
            $this->updateResponseHeader();
        }
        return $this->response;
    }

    /**
     * Delete weather records based on the set interval.
     * Default is 1 day.
     *
     * @Route("weatherstation/api/delete/{params}", methods={"DELETE"}, name="task_delete")
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
     * Validate incoming request.
     *
     * @param Request $request
     * @return bool $valid If request is valid.
     */
    private function validateRequest($violations = []): bool {
        $valid = true;
        if ($violations instanceof  ConstraintViolationListInterface && $violations->count() > 0) {
            $formatedViolationList = [];
            for ($i = 0; $i < $violations->count(); $i++) {
                $violation = $violations->get($i);
                $formatedViolationList[] = array($violation->getPropertyPath() => $violation->getMessage());
            }
            $this->logger->log('Schema validation failed', $formatedViolationList, Logger::ALERT);
            $this->response->setContent(json_encode($formatedViolationList));
            $this->response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            $valid = false;
        }
        return $valid;
    }



    /**
     * Post moisture readings.
     *
     * @Route("/sensorgateway/api/moisture",  methods={"POST"}, name="post_moisture_readings")
     * @param Request $request
     * @param int $interval Interval for sending moisture report emails.
     * @return Response
     * @throws Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function postMoisture(
        Request $request,
        ValidatorInterface $validator,
        MoistureSensorSchema $moisturePostSchema,
        SensorMoistureRepository $moistureRepo
    ) {
        $parameters = json_decode($request->getContent(), true);
        $normalizedData = $this->normalizeData($parameters);
        $violations = $validator->validate($parameters, $moisturePostSchema::$schema);
        $valid = $this->validateRequest($violations);
        if ($valid) {
            $result = $moistureRepo->save($normalizedData);
            if ($result) {
                $this->response->setStatusCode(self::STATUS_OK);
                $paramsMoistureData = [
                    'tableName' => SensorMoistureEntity::class,
                    'dateTimeField' => 'insertDateTime',
                    'interval' => $this->configCache->getConfigKey('pruning-moisture-interval') ?? 1,
                ];
                $paramsReportData = [
                    'tableName' => SensorReportEntity::class,
                    'dateTimeField' => 'lastSentDate',
                    'interval' => $this->configCache->getConfigKey('pruning-records-interval') ?? 2,

                ];
                // Data pruning.
                $moistureRepo->delete($paramsMoistureData);
                $this->sensorRepository->delete($paramsReportData);
            } else {
                $this->response->setStatusCode(self::STATUS_EXCEPTION);
            }
        }
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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function post(
        Request $request,
        ValidatorInterface $validator,
        StationPostSchema $stationPostSchema,
        int $interval = 1
    ): Response {
        $parameters = json_decode($request->getContent(), true);
        $parameters = $this->normalizeData($parameters);
        $violations = $validator->validate($parameters, $stationPostSchema::$schema);
        $valid = $this->validateRequest($violations);
        $validTemp  = true;
        $validHumid = true;

        if ($valid) {
            //TODO Add custom validation
            $validStationID = $this->validateStationID($parameters['station_id'], __CLASS__ . __FUNCTION__);
            if (!$validStationID) {
                return $this->response;
            }
            $result = $this->sensorRepository->save($parameters);

            try {
                // Historic sensor readings.
                /** @var SensorHistoricReadings $historicReadingsEntity */
                $historicReadingsEntity = $this->getHistoricReading($parameters, 'temperature');
                $historicReadingsEntityHumid = $this->getHistoricReading($parameters, 'humidity');
                $params = [
                    'name' => $parameters['room']
                ];
                if ($parameters['temperature'] >  ($this->configCache->getConfigKey(self::HIGHEST_ABNORMAL_TEMP) ?? 55) ||
                    $parameters['temperature'] <  ($this->configCache->getConfigKey(self::LOWEST_ABNORMAL_TEMP) ?? -50)) {
                    $this->logger->log("Irregular reading", [
                        'temperature' => $parameters['temperature'],
                        'sensor' =>  $parameters['room']], Logger::ALERT);
                    $validTemp = false;

                }
                if ($parameters['humidity'] >  ($this->configCache->getConfigKey(self::HIGHEST_ABNORMAL_HUMIDITY) ?? 100) ||
                    $parameters['humidity'] <  ($this->configCache->getConfigKey(self::LOWEST_ABNORMAL_HUMIDITY) ?? 5)) {
                    $this->logger->log("Irregular reading", [
                        'humidity' => $parameters['humidity'],
                        'sensor' =>  $parameters['room']], Logger::ALERT);
                    $validHumid = false;
                }
                if (!$validTemp || !$validHumid) {
                    return $this->response;
                }

                if ($historicReadingsEntity === null) {
                    // first time, insert lowest/highest equal to received valued
                    $params['lowest_reading'] = $parameters['temperature'];
                    $params['highest_reading'] = $parameters['temperature'];
                    $params['type'] = 'temperature';
                    $this->sensorHistoricReadingsRepository->save($params);
                }
                if ($historicReadingsEntityHumid === null) {
                    // first time, insert lowest/highest equal to received valued
                    $params['lowest_reading'] = $parameters['humidity'];
                    $params['highest_reading'] = $parameters['humidity'];
                    $params['type'] = 'humidity';
                    $this->sensorHistoricReadingsRepository->save($params);
                }
                $dt = SensorDateTime::dateNow('', false);
                if ($historicReadingsEntity) {
                    if ($parameters['temperature'] < $historicReadingsEntity->getLowestReading()) {
                        $historicReadingsEntity->setLowestReading($parameters['temperature']);
                        $historicReadingsEntity->setInsertDateLowest($dt);
                        $historicReadingsEntity->setInsertDateHighest($historicReadingsEntity->getInsertDateHighest());
                    } elseif ($historicReadingsEntity && $parameters['temperature'] > $historicReadingsEntity->getHighestReading()) {
                        $historicReadingsEntity->setHighestReading($parameters['temperature']);
                        $historicReadingsEntity->setInsertDateHighest($dt);
                        $historicReadingsEntity->setInsertDateLowest($historicReadingsEntity->getInsertDateLowest());
                    }
                    $this->sensorHistoricReadingsRepository->updateHistoricReadings($historicReadingsEntity);
                }

                if ($historicReadingsEntityHumid) {
                    if ($parameters['humidity'] < $historicReadingsEntityHumid->getLowestReading()) {
                        $historicReadingsEntityHumid->setLowestReading($parameters['humidity']);
                        $historicReadingsEntityHumid->setInsertDateLowest($dt);
                        $historicReadingsEntityHumid->setInsertDateHighest($historicReadingsEntityHumid->getInsertDateHighest());
                    } elseif ($parameters['humidity'] > $historicReadingsEntityHumid->getHighestReading()) {
                        $historicReadingsEntityHumid->setHighestReading($parameters['humidity']);
                        $historicReadingsEntityHumid->setInsertDateHighest($dt);
                        $historicReadingsEntityHumid->setInsertDateLowest($historicReadingsEntityHumid->getInsertDateLowest());
                    }
                    $this->sensorHistoricReadingsRepository->updateHistoricReadings($historicReadingsEntityHumid);
                }
            } catch(Exception $e) {
                $this->logger->log('historic data saving failed', ['errorMessage' => $e->getMessage()], Logger::CRITICAL);
            }
            // TODO UPDATE date for both tables to be insert_date_time.
            if ($result) {
                // Everytime a record is inserted, we want to call the delete API to delete records that are older than 1 day.
                // keeping weather data for 24hrs.
                //Delete  sensor data. Table room_gateway
                $paramsSensorData = [
                    'tableName' => SensorEntity::class,
                    'dateTimeField' => 'insert_date_time',
                    'interval' => $this->configCache->getConfigKey('pruning-records-interval') ?? $interval,

                ];
                $paramsReportData = [
                    'tableName' => SensorReportEntity::class,
                    'dateTimeField' => 'lastSentDate',
                    'interval' => $this->configCache->getConfigKey('pruning-report-interval') ?? 2,

                ];
                $paramsLoggerData = [
                    'tableName' => SensorLoggerEntity::class,
                    'dateTimeField' => 'insertDateTime',
                    'interval' => $this->configCache->getConfigKey('pruning-logs-interval') ?? 1,
                ];
                $this->sensorRepository->delete($paramsLoggerData);
                $this->sensorRepository->delete($paramsSensorData);
                $this->sensorRepository->delete($paramsReportData);
                $this->response->setStatusCode(self::STATUS_OK);
            } else {
                $this->response->setStatusCode(self::STATUS_EXCEPTION);
            }
        }
        $this->updateResponseHeader();
        return $this->response;
    }

    /**
     * Get historic sensor readings.
     *
     * @param $parameters
     * @param $type
     * @return \App\Entity\SensorHistoricReadings|null
     */
    private function getHistoricReading($parameters, $type) {
        return $this->sensorHistoricReadingsRepository->findOneBy(['name' => $parameters['room'], 'type' => $type]);
    }

    /**
     * Normalize Post data
     *
     * @param array $parameters
     * @return array Normalized Data
     */
    private function normalizeData(array $parameters, $format = 'pascaleCase'): array {
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
        $valid = (isset($parameters['temperature']) && isset($parameters['humidity']) && isset($parameters['station_id']) && isset($parameters['room'])) ? $valid : !$valid;
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
     * @param string $stationName
     * @param string $sender Sending function.
     * @return bool|Response
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function validateSensorName(string $stationName, string $sender = '') {
        $valid = true;
        if (!$this->configCache->isConfigSetKey('sensor-config-'.$stationName)) {
            $valid = false;
            $this->updateResponse(
                self::VALIDATION_STATION_NAME,
                self::STATUS_VALIDATION_FAILED,
                ['loggerMsg' => self::VALIDATION_STATION_NAME,
                    'loggerContext' => [
                        'method' => $sender,
                        'stationName' => $stationName
                    ],
                    'loggerLevel' => Logger::ALERT
                ]
            );
        }
        return $valid;
    }

    /**
     * Validate station id.
     *
     * @param int $stationID
     * @param string $sender Sending function.
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function validateStationID(int $stationID, string $sender = ''): bool {
        $valid = true;
        if (!$this->configCache->isConfigSetValue($stationID)) {
            $valid = false;
            $this->updateResponse(
                self::VALIDATION_STATION_ID,
                self::STATUS_VALIDATION_FAILED,
                ['loggerMsg' =>  self::VALIDATION_STATION_ID,
                    'loggerContext' => [
                        'method' => $sender,
                        'stationID' => $stationID
                    ],
                    'loggerLevel' => Logger::ALERT
                ]
            );
        }
        return $valid;
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

}
