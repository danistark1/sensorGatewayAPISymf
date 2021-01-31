<?php
/**
 * @author Dani Stark.
 */
namespace App\Listeners;

use App\Controller\SensorController;
use App\Entity\SensorEntity;
use App\Entity\WeatherReportEntity;
use App\Logger\MonologDBHandler;
use App\WeatherStationLogger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use Monolog\Logger;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Utils\StationDateTime;
use App\Kernel;

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

    private $logger;

    /**
     * PostListener constructor.
     *
     * @param \Twig\Environment $templating
     * @param MailerInterface $mailer
     * @param Kernel|null $kernel
     */
    public function __construct(\Twig\Environment $templating, MailerInterface $mailer, WeatherStationLogger $logger) {
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->logger = $logger;
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
        $postInstance = $args->getEntity();
        $reportEnabled = $_ENV["READING_REPORT_ENABLED"] ?? false;
        // only act on "Room" entity
        if (($postInstance instanceof SensorEntity) && $reportEnabled) {
            $this->prepareReportData($args);
        }
    }

    /**
     * Check dates/report table and decide if a report needs to be sent.
     *
     * @param $args
     * @throws \Exception|\Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function prepareReportData($args) {
        // Try to send the report if the criteria is met.
        // First, get current Date & time
        $currentDateTime = StationDateTime::dateNow();
        $currentDate = StationDateTime::dateNow('', true, 'Y-m-d');
        $currentTime = StationDateTime::dateNow('', true, 'H:i:s');

        // Get the last inserted report.
        $entityManager = $args->getObjectManager();
        $reportDataDb = $entityManager->getRepository(WeatherReportEntity::class)->findBy(
            array(),
            array('id'=>'DESC'),
            1,
            0);
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
            if ($currentTime >= ($_ENV["FIRST_REPORT_TIME"] ?? self::FIRST_REPORT_TIME) && !$reportToday) {
                // First report of the day, send
                $reportData['newReport'] = true;
                $reportData['reportNumber'] = 1;
                $this->sendReport($args, $reportData);
            }
            // if time > 08:00 PM && last sent date from today and counter is < 2 send
            if ($currentTime >= ($_ENV["SECOND_REPORT_TIME"] ?? self::SECOND_REPORT_TIME) &&  ($lastReportLastCounter < 2) && $reportToday) {
                // Second report of the day, send
                $reportData['reportNumber'] = 2;
                $reportData['newReport'] = false;
                $reportData['counter'] = $lastReportLastCounter + 1;
                $this->sendReport($args, $reportData);
            }
        } else {
            // First report
            $reportData['reportNumber'] = 0;
            $this->sendReport($args, $reportData);
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
     * @param array $reportData
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function sendReport($args, array $reportData) {
        // TODO check if the returned array is empty.
        $massagedData = $this->prepareSensorData($args);
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
        $weatherReport = new WeatherReportEntity();
        $weatherReport->setEmailBody('TODO');
        $weatherReport->setLastSentCounter($reportData['counter']);

        $reportData['currentDateTime']->format('Y-m-d');

        $weatherReport->setLastSentDate($reportData['currentDateTime']);
        $reportData['currentDateTime']->format('H:i:s');
        $weatherReport->setLastSentTime($reportData['currentDateTime']);

        $entityManager->persist($weatherReport);
        $entityManager->flush();

        $this->logger->log('Daily Report Log, Sent!',
            $reportData, Logger::DEBUG
        );
    }

    /**
     * Massage data before sending to twig
     *
     * @param LifecycleEventArgs $args
     * @return array[]
     */
    private function prepareSensorData(LifecycleEventArgs $args): array {
        $entityManager = $args->getObjectManager();
        // Construct station IDs array.
        $stationIDs = SensorController::constructSensorData();
        // Remove any invalid entries before calling temp & humidity methods on an empty array.
        $prepareData = $weatherData =  [];
        foreach ($stationIDs as $room => $stationID) {
            $roomData = $entityManager->getRepository(SensorEntity::class)->findBy(
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
        if (empty($prepareData)) {
            $this->logger->error('Daily Report No Data'
            );
        }
        $weatherData = ['weatherData' => $prepareData];
        // Data ready to be sent to twig.
        return $weatherData;
    }
}
