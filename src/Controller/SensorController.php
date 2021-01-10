<?php
/**
 * @author Dani Stark.
 */
namespace App\Controller;

use App\Entity\RoomGateway;
use App\Entity\WeatherReport;
use App\Repository\RoomGatewayRepository;
use DateInterval;
use DateTime;
use Exception;
use http\Client\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use DateTimeZone;

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

    /**
     * Get weatherData by stationID
     *
     * @param int $id The room id.
     * @Route("/weatherstationapi/{id}", methods={"GET"}, requirements={"id"="\d+"}, name="get_by_id")
     * @return Response
     */
    public function getByID(int $id): Response {
        $response = new Response();
        $sensorData = self::constructSensorData();
        $validSensorConfig = empty($sensorData) ? false : true;
        if (!$validSensorConfig) {
            $response->setContent('No sensor data is configured in your environment file.');
            $response->setStatusCode(self::STATUS_VALIDATION_FAILED);
        } else {
            $valid = $this->validateStationID($id, $sensorData);
            if (!$valid) {
                $response->setContent('Invalid Room ID.');
                $response->setStatusCode(self::STATUS_VALIDATION_FAILED);

            } else {
                $room = $this->getDoctrine()->getRepository(RoomGateway::class)->findBy(['station_id' => $id]);
                $serializer = $this->get('serializer');
                $data = $serializer->serialize($room, 'json');
                if (empty($room)){
                    $response->setStatusCode(self::STATUS_NO_CONTENT);
                } else {
                    $response->setContent($data);
                    $response->setStatusCode(self::STATUS_OK);
                }

            }
        }
        return $response;
    }

    /**
     * Get weatherData by room name.
     *
     * @param string $name Room name
     * @Route("weatherstationapi/{name}", methods={"GET"}, requirements={"name"="\w+"}, name="get_by_name")
     * @return Response
     */
    public function getByName(string $name): Response {
        $response = new Response();
        $sensorData = self::constructSensorData();
        $validSensorConfig = empty($sensorData) ? false : true;
        if (!$validSensorConfig) {
            $response->setContent('No sensor data is configured in your environment file.');
            $response->setStatusCode(self::STATUS_VALIDATION_FAILED);
        } else {
            $name = strtolower($name);
            $valid = $this->validateRoom($name, $sensorData);
            if (!$valid) {
                $response->setContent('Invalid Room Name.');
                $response->setStatusCode(self::STATUS_VALIDATION_FAILED);
            } else {
                $room = $this->getDoctrine()->getRepository(RoomGateway::class)->findBy(['room' => $name]);
                $serializer = $this->get('serializer');
                if (!empty($room)) {
                    $data = $serializer->serialize($room, 'json');
                    $response->setContent($data);
                } else {
                    $response->setContent('No weather data found.');
                    $response->setStatusCode(self::STATUS_NOT_FOUND);
                }
            }
        }
        return $response;
    }

    /**
     * Delete weather records based on the set interval.
     * Default is 1 day.
     *
     * @Route("weatherstationapi/delete/{interval}", methods={"DELETE"}, name="task_delete")
     * @param string $entity The entity to delete from
     * @param string $dataTimeField Table dateTimeField to use
     * @param int $interval The interval to delete records
     * @return Response
     * @throws Exception
     */
    public function delete(string $entity, string $dataTimeField, int $interval = 1): Response {
        $response = new Response();
        $response->setStatusCode(self::STATUS_OK);
        $entityManager = $this->getDoctrine()->getManager();
        $qb = $entityManager->createQueryBuilder();

        // By default we want to delete records that are older than 1 day.
        // Weather data is only needed for 24 hrs.
        $date = new \DateTime();
        $period = new DateInterval('P'.$interval.'D');
        $date->sub($period);

        $date->setTimezone(new DateTimeZone('America/Toronto'));
        $date->format('Y-m-d H:i:s');
        $results  = $qb->select('p')
            ->from($entity, 'p')
            ->where('p.'.$dataTimeField. '<= :date_from')
            ->setParameter('date_from', $date)
            ->getQuery()
            ->execute();

        if (!empty($results)) {
            foreach($results as $result) {
                $entityManager->remove($result);
                $entityManager->flush();
            }
            $response->setStatusCode(self::STATUS_NO_CONTENT);
        } else {
            $response->setStatusCode(self::STATUS_NOT_FOUND);
        }

        $serializer = $this->get('serializer');
        $data = $serializer->serialize($results, 'json');
        $response->setContent($data);
        return $response;
    }

    /**
     * Post weatherData.
     *
     * @Route("/weatherstationapi/",  methods={"POST"}, name="post_by_name")
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $interval Interval for sending weather report emails.
     * @return Response
     * @throws Exception
     */
    public function post(\Symfony\Component\HttpFoundation\Request $request, int $interval = 1): Response {
        $response = new Response();
        $sensorData = self::constructSensorData();
        $validSensorConfig = empty($sensorData) ? false : true;
        if (!$validSensorConfig) {
            $response->setContent('No sensor data is configured in your environment file.');
            $response->setStatusCode(self::STATUS_VALIDATION_FAILED);
        } else {
            // turn request data into an array
            $parameters = json_decode($request->getContent(), true);
            $parameters = $this->normalizeData($parameters);

            $valid = false;
            if ($parameters && is_array($parameters)) {
                $valid = $this->validatePost($parameters);
            }
            if (!$valid) {
                $response->setStatusCode(self::STATUS_VALIDATION_FAILED);
                $response->setContent('Post validation failed.');
            } else {
                $validRoom = $this->validateRoom($parameters['room'], $sensorData);
                $validStation = $this->validateStationID($parameters['station_id'], $sensorData);
                if (!$validRoom || !$validStation) {
                    $response->setContent('Validation failed.');
                    $response->setStatusCode(self::STATUS_VALIDATION_FAILED);

                    return $response;
                }

                $entityManager = $this->getDoctrine()->getManager();

                $roomGateway = new RoomGateway();
                $roomGateway->setRoom($parameters['room']);
                $roomGateway->setHumidity($parameters['humidity']);
                $roomGateway->setTemperature($parameters['temperature']);
                $roomGateway->setStationId($parameters['station_id']);
                $dt = new \DateTime();
                $dt->format('Y-m-d H:i:s');
                $dt->setTimezone(new DateTimeZone('America/Toronto'));
                $roomGateway->setInsertDateTime($dt);

                $entityManager->persist($roomGateway);
                $entityManager->flush();

                // Everytime a record is inserted, we want to call the delete API to delete records that are older than 1 day.
                // keeping weather data for 24hrs.

                //Delete  sensor data. Table room_gateway
                $this->delete( RoomGateway::class,'insert_date_time', $_ENV["KEEP_RECORDS_FOR"] ?? $interval);
                // Delete sensor report data. Table weather_report
                $this->delete( WeatherReport::class,'lastSentDate', $_ENV["KEEP_RECORDS_FOR"] ?? $interval);
                $response->setStatusCode(self::STATUS_OK);
            }
        }
        return $response;
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
    private function validatePost(array $parameters): bool {
        $valid = true;
        $valid = (isset($parameters['temperature']) &&  isset($parameters['humidity']) && isset($parameters['station_id']) && isset($parameters['room'])) ? $valid : !$valid;
        return $valid;
    }

    /**
     * Validate station name.
     *
     * @param string $station
     * @param array $stationData
     * @return bool
     */
    private function validateRoom(string $station, array $stationData): bool {
        $valid = true;
        if (!isset($stationData[$station])) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * Validate station id.
     *
     * @param int $stationID
     * @param array $stationData
     * @return bool
     */
    private function validateStationID(int $stationID, array $stationData): bool {
        $valid = true;
        $sensorData = array_flip($stationData);
        if (!isset($sensorData[$stationID])) {
            $valid = false;
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
