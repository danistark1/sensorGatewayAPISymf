<?php
/**
 * @author Dani Stark.
 */
namespace App\Listeners;

use App\Controller\SensorController;
use App\Entity\RoomGateway;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Class PostListener
 */
class PostListener {

    /**
     * @var \Twig\Environment
     */
    public $templating;
    /**
     * @var MailerInterface
     */
    public $mailer;

    /**
     * PostListener constructor.
     *
     * @param \Twig\Environment $templating
     * @param MailerInterface $mailer
     */
    public function __construct(\Twig\Environment $templating, MailerInterface $mailer) {
        $this->templating = $templating;
        $this->mailer = $mailer;
    }

    /**
     * Post listener: Listens for any post made in the SensorController.
     *
     * @param LifecycleEventArgs $args
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function postPersist(LifecycleEventArgs $args) {
        $entity = $args->getObject();
        $sensorController = new SensorController();
        // only act on "Room" entity
        if (!$entity instanceof RoomGateway) {
            return;
        }

        $massagedData = $this->prepareData($args, $sensorController);
        $message = (new Email())
            ->from($_ENV["FROM_EMAIL"])
            ->to($_ENV["TO_EMAIL"])
            ->subject($_ENV["EMAIL_TITLE"])
            ->html(
                $this->templating->render(
                    '/sensor/weatherStationReport.html.twig',
                    $massagedData
                ),
                'text/html'
            );
        $this->mailer->send($message);
    }

    /**
     * Massage data before sending to twig
     *
     * @param LifecycleEventArgs $args
     * @param SensorController $sensorController
     * @return array[]
     */
    private function prepareData(LifecycleEventArgs $args, SensorController $sensorController): array {

        $entityManager = $args->getObjectManager();
        // Construct station IDs array.
        $stationIDs = [
            'outside' => $sensorController::STATION_ID_OUTSIDE,
            'living-room' => $sensorController::STATION_ID_LIVING_ROOM,
            'garage' => $sensorController::STATION_ID_GARAGE,
            'bedroom' => $sensorController::STATION_ID_BEDROOM,
            'basement' => $sensorController::STATION_ID_BASEMENT
        ];
        // Remove any invalid entries before calling temp & humidity methods on an empty array.
        $prepareData = $weatherData =  [];
        foreach ($stationIDs as $room => $stationID) {
            $roomData = $entityManager->getRepository(RoomGateway::class)->findBy(['station_id' => $stationID]);
            if(!empty($roomData)) {
                $prepareData[$room] = [
                    'temperature' => $roomData[0]->getTemperature(),
                    'humidity' => $roomData[0]->getHumidity()
                    ];
            }
        }
        $weatherData = ['weatherData' => $prepareData];
        // Data ready to be sent to twig.
        return $weatherData;
    }
}
