<?php
/**
 * @author Dani S <danistark.ca@gmail.com>
 */
namespace App\Controller;

use App\Entity\RoomGateway;
use App\Repository\RoomGatewayRepository;
use DateInterval;
use DateTime;
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
class SensorController extends AbstractController
{
    const ROOM_BEDROOM = 'bedroom';
    const ROOM_GARAGE = 'garage';
    const ROOM_LIVING_ROOM = 'living-room';
    const ROOM_BASEMENT = 'basement';
    const ROOM_OUTSIDE = 'outside';

    const STATION_ID_BEDROOM = 6126;
    const STATION_ID_BASEMENT = 3026;
    const STATION_ID_LIVING_ROOM = 15043;
    const STATION_ID_OUTSIDE = 12154;
    const STATION_ID_GARAGE = 8166;

    /**
     * Get weatherData by stationID
     *
     * Basement: 3026
     * Beddroom: 6126
     * Garage: 8166
     * Living-room: 15043
     * Outside: 12154
     *
     * @Route("/weatherstationapi/{id}", methods={"GET"}, requirements={"id"="\d+"}, name="get_by_id")
     */
    public function getByID($id) {
        $room = $this->getDoctrine()->getRepository(RoomGateway::class)->findBy(['station_id' => $id]);
        return $this->json([$room]);
    }

    /**
     * Get weatherData by room name.
     *
     * @Route("weatherstationapi/{name}", methods={"GET"}, requirements={"name"="\w+"}, name="get_by_name")
     */
    public function getByName($name) {
        $room = $this->getDoctrine()->getRepository(RoomGateway::class)->findBy(['room' => $name]);
        $response = new Response();
        if (!empty($room)) {
            $serializer = $this->get('serializer');
            $data = $serializer->serialize($room, 'json');

            $response->setContent($data);
            return $response;
        } else {
            $response->setContent('No weather data found for '.$name);
            $response->setStatusCode(404);
            return $response;
        }
    }


    /**
     * Delete weather records based on the set interval.
     * Default is 1 day.
     *
     * @Route("weatherstationapi/delete/{interval}", methods={"DELETE"}, name="task_delete")
     * @param int $interval The interval to delete records from
     * @return Response
     * @throws \Exception
     */
    public function delete(int $interval = 1) {
        $response = new Response();
        $response->setStatusCode(200);
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
            ->from(RoomGateway::class, 'p')
            ->where('p.insert_date_time <= :date_from')
            ->setParameter('date_from', $date)
            ->getQuery()
            ->execute();

        if (!empty($results)) {
            foreach($results as $result) {
                $entityManager->remove($result);
                $entityManager->flush();
            }
            $response->setStatusCode(204);
        } else {
            $response->setStatusCode(404);
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
     * @return Response
     * @throws \Exception
     */
    public function post(\Symfony\Component\HttpFoundation\Request $request) {
        $response = new Response();
        // turn request data into an array
        $parameters = json_decode($request->getContent(), true);

        $valid = true;
        if ($parameters && is_array($parameters)) {
            $valid = $this->validatePost($parameters);
        }

        $validRoom = $this->validateRoom($parameters['room']);
        $validStation = $this->validateStationID($parameters['station_id']);
        if (!$valid || !$validRoom || !$validStation) {
            $response->setContent('Validation failed');
            $response->setStatusCode(404);
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

        // Everytime a record is inserted, we want to call the delete API to delete records that are olders than 1 day.
        // This way we only keep data for 24hrs.
        $this->delete(1);
        $response->setStatusCode(200);
        return $response;
    }

    /**
     * @param array $parameters
     * @return bool
     */
    private function validatePost(array $parameters): bool {
        $valid = true;
        $validationResults = [
            'temperature' => 0,
            'humidity' => 0,
            'station_id' => 0,
            'room' => 0
        ];
        if (!isset($parameters['temperature'])){
            $validationResults['temperature'] = 1;
        }

        if (!isset($parameters['humidity'])) {
            $validationResults['humidity'] = 1;
        }
        if (!isset($parameters['station_id'])){
            $validationResults['station_id'] = 1;
        }
        if (!isset($parameters['room'])){
            $validationResults['room'] = 1;
        }
      if (in_array(1, $validationResults)){
          $valid = false;
      }
      return $valid;
    }

    /**
     * @param string $room
     * @return bool
     */
    private function validateRoom(string $room): bool {
        $valid = true;
        if(
            $room !== self::ROOM_BASEMENT &&
            $room !== self::ROOM_LIVING_ROOM &&
            $room !== self::ROOM_GARAGE &&
            $room !== self::ROOM_BEDROOM &&
            $room !== self::ROOM_OUTSIDE
        ) {
            $valid = false;
        }
        return $valid;
    }

    /**
     * @param int $stationID
     * @return bool
     */
    private function validateStationID(int $stationID): bool {
        $valid = true;
        if(
            $stationID !== self::STATION_ID_BASEMENT &&
            $stationID !== self::STATION_ID_BEDROOM &&
            $stationID !== self::STATION_ID_GARAGE &&
            $stationID !== self::STATION_ID_LIVING_ROOM &&
            $stationID !== self::STATION_ID_OUTSIDE
        ) {
            $valid = false;
        }
        return $valid;
    }
}
