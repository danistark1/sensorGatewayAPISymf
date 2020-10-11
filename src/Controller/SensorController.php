<?php

namespace App\Controller;

use App\Entity\RoomGateway;
use App\Repository\RoomGatewayRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
class SensorController extends AbstractController
{
    /**
     * Get weatherData by stationID
     *
     * Basement: 3026
     * Beddroom: 6126
     * Garage: 8166
     * Living-room: 15043
     * Outside: 12154
     *
     * @Route("/weatherstationapi/{id}",requirements={"id"="\d+"}, name="get_by_id")
     */
    public function getByID($id) {
        $room = $this->getDoctrine()->getRepository(RoomGateway::class)->findBy(['station_id' => $id]);
        return $this->json([$room]);
    }

    /**
     * Get weatherData by room name.
     *
     * @Route("weatherstationapi/{name}", requirements={"name"="\w+"}, name="get_by_name")
     */
    public function getByName($name) {
        $room = $this->getDoctrine()->getRepository(RoomGateway::class)->findBy(['room' => $name]);
        if ($room) {
            $serializer = $this->get('serializer');
            $data = $serializer->serialize($room, 'json');
            $response = new Response();
            $response->setContent($data);
            $response->headers->set('Stark', 'Test');
            return $response;
        }
    }

//    /**
//     * Post weatherData.
//     *
//     * @Route("/weatherstationapi/{name}", requirements={"name"="\w+"}, name="get_by_name")
//     */
//    public function post($data) {
//        $room = $this->getDoctrine()->getRepository(RoomGateway::class)->findBy(['room' => $name]);
//        if ($room) {
//            $serializer = $this->get('serializer');
//            $response = $serializer->serialize($room, 'json');
//
//            return new Response($response);
//        }
//    }

}
