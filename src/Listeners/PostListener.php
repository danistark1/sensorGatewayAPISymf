<?php
namespace App\Listeners;

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

        // only act on some "Product" entity
        if (!$entity instanceof RoomGateway) {
            return;
        }

        $entityManager = $args->getObjectManager();

        // Get current weather data,
        $weatherData = $entityManager->getRepository(RoomGateway::class)->find();

        // result should be something like this
        $result = [
            $idOutside => [
                'Temperature' => $temperature,
                'Humidity' => $humidity,
                'Name' => $roomName
            ],
            $idLivingRoom => [
                'Temperature' => $temperature,
                'Humidity' => $humidity,
                'Name' => $roomName
            ]
        ];
        //
        //
        //
        //
        $message = (new Email())
            ->from('vantesla1@gmail.com')
            ->to('dany.majdalani@gmail.com')
            ->html(
                $this->templating->render(
                    'sensor/weatherStationReport.twig.html',
                    ['WeatherData' => $weatherData]
                ),
                'text/html'
            );
        $this->mailer->send($message);
    }
}
