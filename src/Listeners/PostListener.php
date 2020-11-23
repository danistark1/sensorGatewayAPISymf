<?php
namespace App\Listeners;

use App\Controller\SensorController;
use App\Entity\RoomGateway;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PostListener {

    public $templating;
    public $mailer;

    public function __construct(\Twig\Environment $templating, MailerInterface $mailer) {

        $this->templating = $templating;
        $this->mailer = $mailer;
    }

    public function postPersist(LifecycleEventArgs $args) {
        $entity = $args->getObject();
        $sensorController = new SensorController();
        // only act on some "Product" entity
        if (!$entity instanceof RoomGateway) {
            return;
        }

        $entityManager = $args->getObjectManager();

        // Get current weather data,
        $weatherDataOutside = $entityManager->getRepository(RoomGateway::class)->findBy(['station_id' => $sensorController::STATION_ID_OUTSIDE]);
        $weatherDataLivingRoom = $entityManager->getRepository(RoomGateway::class)->findBy(['station_id' => $sensorController::STATION_ID_LIVING_ROOM]);
        $weatherDataGarage = $entityManager->getRepository(RoomGateway::class)->findBy(['station_id' => $sensorController::STATION_ID_GARAGE]);
        $weatherDataBedroom = $entityManager->getRepository(RoomGateway::class)->findBy(['station_id' => $sensorController::STATION_ID_BEDROOM]);
        $weatherDataBasement = $entityManager->getRepository(RoomGateway::class)->findBy(['station_id' => $sensorController::STATION_ID_BASEMENT]);

        // Get Date
        $date = $weatherDataOutside[0]->getInsertDateTime()->format('Y-m-d H:i:s');

        // Get Temp Data
        $tempOutside = $weatherDataOutside[0]->getTemperature();
        $tempLivingRoom = $weatherDataLivingRoom[0]->getTemperature();
        //dump($weatherDataLivingRoom);
        $tempGarage = $weatherDataGarage[0]->getTemperature();
        $tempBedroom = $weatherDataBedroom[0]->getTemperature();
        $tempBasement = $weatherDataBasement[0]->getTemperature();

        // Get Humidity data
        $humidOutside = $weatherDataOutside[0]->getHumidity();
        $humidLivingRoom = $weatherDataLivingRoom[0]->getHumidity();
        $humidGarage = $weatherDataGarage[0]->getHumidity();
        $humidBedroom = $weatherDataBedroom[0]->getHumidity();
        $humidBasement = $weatherDataBasement[0]->getHumidity();



        $message = (new Email())
            ->from('vantesla1@gmail.com')
            ->to('danistark.ca@gmail.com')
            ->subject('Weather Station Report ')
            ->html(
                $this->templating->render(
                    'base.html.twig',
                    [
                        'Date'=> $date,
                        'Header' => 'Room'.' Temperature '.' Humidity ',
                        'weatherDataOutside' => 'Outside'.' '. $tempOutside.' '.$humidOutside,
                        'weatherDataInside' => 'Inside'.' '. $tempLivingRoom.' '.$humidLivingRoom,
                        'weatherDataBasement' => 'Basement'.' '. $tempBasement.' '.$humidBasement,
                    ]
                ),
                'text/html'
            );
        $this->mailer->send($message);
    }
}
