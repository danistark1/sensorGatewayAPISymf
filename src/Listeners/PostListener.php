<?php
/**
 * @author Dani Stark.
 */
namespace App\Listeners;

use App\Controller\SensorController;
use App\Entity\RoomGateway;
use App\Entity\WeatherReport;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Class PostListener
 */
class PostListener {

    // Time to send first weather report
    private const FIRST_REPORT_TIME = "07:00:00";

    // Time to send second weather report
    private const SECOND_REPORT_TIME = "20:00:00";
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
     * @throws \Exception
     */
    public function postPersist(LifecycleEventArgs $args) {
        $entity = $args->getObject();
        $sensorController = new SensorController();
        // only act on "Room" entity
        if (!$entity instanceof RoomGateway) {
            return;
        }

        $this->prepareReportData($args, $sensorController);
    }

    /**
     * Check dates/report table and decide if a report needs to be sent.
     *
     * @param $args
     * @param $sensorController
     * @throws \Exception|\Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function prepareReportData($args, SensorController $sensorController) {
        // Try to send the report if the criteria is met.
        // First, get current Date & time
        $currentDateTime = new \DateTime('now', new \DateTimeZone($_ENV["TIMEZONE"] ?? "America/Toronto"));
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentTime = $currentDateTime->format('H:i:s');

        // Get the last inserted report.
        $entityManager = $args->getObjectManager();
        $reportDataDb = $entityManager->getRepository(WeatherReport::class)->findBy(array(),array('id'=>'DESC'),1,0);
        $reportData = [
            'newReport' => false,
            'counter' => 1,
            'currentDateTime' => $currentDateTime
        ];
        if (!empty($reportDataDb)) {
            $lastReportDate = $reportDataDb[0]->getLastSentDate()->format('Y-m-d');
            $lastReportLastCounter = $reportDataDb[0]->getLastSentCounter();
            $reportToday = ($lastReportDate === $currentDate);

            // If time > 07:00 AM && last sent report not from today and counter is 0 send
            if ($currentTime >= ($_ENV["FIRST_REPORT_TIME"] ?? self::SECOND_REPORT_TIME) && !$reportToday) {
                // First report of the day, send
                $reportData['newReport'] = true;
                $this->sendReport($args,$sensorController, $reportData);

            }
            // if time > 08:00 PM && last sent date from today and counter is < 2 send
            if ($currentTime >= ($_ENV["SECOND_REPORT_TIME"] ?? self::SECOND_REPORT_TIME) &&  $lastReportLastCounter < 2 && $reportToday) {
                // Second report of the day, send
                $reportData['newReport'] = false;
                $reportData['counter'] = $lastReportLastCounter + 1;
                $this->sendReport($args,$sensorController, $reportData);
            }
        } else {
            // First report
            $this->sendReport($args, $sensorController, $reportData);
        }
    }

    /**
     * Sends a weather report.
     *
     * Required .env config
     *
     * FROM_EMAIL=
     * TO_EMAIL=
     * EMAIL_TITLE= (Defaults to Weather Station Report)
     * TIMEZONE= (Defaults to "America/Toronto")
     * FIRST_REPORT_TIME= (Defaults to sending report everyday at "07:00:00")
     * SECOND_REPORT_TIME= (Defaults to sending report everyday at "20:00:00")
     *
     * @param $args
     * @param $sensorController
     * @param array $reportData
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function sendReport($args, $sensorController, array $reportData) {
        // TODO check if the returned array is empty.
        $massagedData = $this->prepareSensorData($args, $sensorController);
        $message = (new Email())
            ->from($_ENV["FROM_EMAIL"])
            ->to($_ENV["TO_EMAIL"])
            ->subject($_ENV["EMAIL_TITLE"] ?? "Weather Station Report")
            ->html(
                $this->templating->render(
                    '/sensor/weatherStationReport.html.twig',
                    $massagedData
                ),
                'text/html'
            );
        $this->mailer->send($message);
        $entityManager = $args->getObjectManager();

        // Update Email Report table after email is sent.
        $weatherReport = new WeatherReport();
        $weatherReport->setEmailBody('TODO');
        $weatherReport->setLastSentCounter($reportData['counter']);

        $reportData['currentDateTime']->format('Y-m-d');

        $weatherReport->setLastSentDate($reportData['currentDateTime']);
        $reportData['currentDateTime']->format('H:i:s');
        $weatherReport->setLastSentTime($reportData['currentDateTime']);

        $entityManager->persist($weatherReport);
        $entityManager->flush();
        //TODO delete report record that are older than 2 days.
    }

    /**
     * Massage data before sending to twig
     *
     * @param LifecycleEventArgs $args
     * @param SensorController $sensorController
     * @return array[]
     */
    private function prepareSensorData(LifecycleEventArgs $args, SensorController $sensorController): array {
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
            $roomData = $entityManager->getRepository(RoomGateway::class)->findBy(
                [
                    'station_id' => $stationID
                ],
                [
                    'id' => 'DESC'
                ]);
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
