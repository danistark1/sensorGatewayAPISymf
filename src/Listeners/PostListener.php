<?php
/**
 * @author Dani Stark.
 */
namespace App\Listeners;

use App\Controller\SensorController;
use App\Entity\SensorEntity;
use App\Entity\WeatherReportEntity;
use App\Logger\MonologDBHandler;
use App\Utils\ArraysUtils;
use App\WeatherStationLogger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\Event\PreUpdateEventArgs;
use Monolog\Logger;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Utils\StationDateTime;
use App\Kernel;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class PostListener
 */
class PostListener {

    private const REPORT_DAILY = 'Daily Report';

    private const REPORT_NOTIFICATIONS = 'Notifications Report';

    // Time to send first weather report
    private const FIRST_REPORT_TIME = "07:00:00";

    // Time to send second weather report
    private const SECOND_REPORT_TIME = "06:00:00";

    // Time to send first notification report
    private const FIRST_NOTIFICATION_TIME = "12:00:00";

    // Time to send second notification report
    private const SECOND_NOTIFICATION_TIME = "18:00:00";

    // Time to send third notification report
    private const THIRD_NOTIFICATION_TIME = "22:00:00";

    /**
     * @var Environment
     */
    public $templating;
    
    /**
     * @var MailerInterface
     */
    public $mailer;

    /**
     * @var WeatherStationLogger
     */
    private $logger;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * PostListener constructor.
     *
     * @param Environment $templating
     * @param MailerInterface $mailer
     * @param WeatherStationLogger $logger
     */
    public function __construct(Environment $templating, MailerInterface $mailer, WeatherStationLogger $logger) {
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * Post listener: Listens for SensorController posts.
     *
     * @param LifecycleEventArgs $args
     * @throws TransportExceptionInterface
     * @throws \Exception
     */
    public function postPersist(LifecycleEventArgs $args) {
        $this->entityManager = $args->getObjectManager();
        $postInstance = $args->getEntity();
        $reportEnabled = $_ENV["READING_REPORT_ENABLED"] ?? false;
        // only act on "Sensor" entity
        if (($postInstance instanceof SensorEntity) && $reportEnabled) {
            // Prepare notifications report
            $latestNotificationsData = $this->prepareNotifications();

            // Last Sent Notifications Report
            $lastSentNotificationReport = $this->getLastSentReport(self::REPORT_NOTIFICATIONS);
            // Check if notification report needs to be sent.
            $shouldSendNotificationReport = $this->shouldSendReport($lastSentNotificationReport,'notification');
            if ($shouldSendNotificationReport) {
                try {
                    $this->sendReport($latestNotificationsData, '/sensor/weatherStationReportNotifications.html.twig', self::REPORT_NOTIFICATIONS);
                    $this->updateWeatherReport(self::REPORT_NOTIFICATIONS);
                } catch (TransportExceptionInterface | LoaderError | RuntimeError | SyntaxError $e) {
                    $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
                }
            }

             // Last Sent Daily Report
            $lastSentDailyReport = $this->getLastSentReport(self::REPORT_DAILY);
            // Get latest Sensor Readings.
            $latestSensorData = $this->getLatestSensorData();
            // Check if daily report needs to be sent.
            $shouldSendReport = $this->shouldSendReport($lastSentDailyReport);
            if ($shouldSendReport) {
                try {
                    $this->sendReport($latestSensorData, '/sensor/weatherStationDailyReport.html.twig', self::REPORT_DAILY);
                    $this->updateWeatherReport(self::REPORT_DAILY);
                } catch (TransportExceptionInterface | LoaderError | RuntimeError | SyntaxError $e) {
                    $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
                }
            }

        }
    }

    /**
     * Check if  a report needs to be sent. (Notifications or Daily)
     *
     * @param $lastSentDailyReport
     * @param string $reportType Empty for Daily report.
     * @return bool A report needs to be sent.
     * @throws \Exception
     */
    private function shouldSendReport($lastSentDailyReport, string $reportType = ''): bool {
        $shouldSendReport = false;

        $firstReport = $reportType === 'notification' ? ($_ENV["FIRST_NOTIFICATION_TIME"] ?? self::FIRST_NOTIFICATION_TIME) : ($_ENV["FIRST_REPORT_TIME"] ?? self::FIRST_REPORT_TIME);
        $secondReport = $reportType === 'notification' ? ($_ENV["SECOND_NOTIFICATION_TIME"] ?? self::SECOND_NOTIFICATION_TIME) : ($_ENV["SECOND_REPORT_TIME"] ?? self::SECOND_REPORT_TIME);


        $currentTime = StationDateTime::dateNow('', true, 'H:i:s');
        $lastReportLastCounter = isset($lastSentDailyReport[0]) ? $lastSentDailyReport[0]->getLastSentCounter() : null;
        if (empty($lastSentDailyReport)) {
            if ($currentTime >= $firstReport) {
                $shouldSendReport = true;
                //first report of the day send!
            }
        } else {
            if($currentTime >= $secondReport &&  ($lastReportLastCounter < 2)) {
                $shouldSendReport = true;
                //second report send
            }
        }
        return $shouldSendReport;
    }

    /**
     * Get latest sensor readings.
     *
     * @return array[]
     */
    private function getLatestSensorData(): array {
        // Construct station IDs array.
        $stationIDs = SensorController::constructSensorData();
        // Remove any invalid entries before calling temp & humidity methods on an empty array.
        $prepareData = $weatherData =  [];
        foreach ($stationIDs as $room => $stationID) {
            $roomData = $this->entityManager->getRepository(SensorEntity::class)->findBy(
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
        return $weatherData;
    }

    /**
     * Get last sent report/notification.
     *
     * @param string $reportType Report type Daily Report / Notifications Report
     * @return array
     * @throws \Exception
     */
    private function getLastSentReport(string $reportType): array {
        $currentDate = StationDateTime::dateNow('', false, 'Y-m-d');
        // Get the last inserted report for the current day;
        $reportDataDb = $this->entityManager->getRepository(WeatherReportEntity::class)->findBy(
            array('emailBody' => $reportType, 'lastSentDate' => $currentDate),
            array('id'=>'DESC'),
            1,
            0);

        return $reportDataDb;
    }

    /**
     *
     * @throws TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function prepareNotifications(): array {
        $massagedData = $this->getLatestSensorData()['weatherData'];
        $notificationsSetThresholds = SensorController::constructNotificationsData();
        // Loop over configured Thresholds, send email.
        $notificationsEmailData = [];
        foreach($massagedData as $key => $value) {
            if ($massagedData[$key]['temperature'] >= $notificationsSetThresholds['sensor_'.$key.'_upper_temperature']) {
                $notificationsEmailData[$key]['temperature']['upper'] = 'Upper Temp Threshold Reached ';
                $notificationsEmailData[$key]['temperature']['value'] = $massagedData[$key]['temperature'];
            } elseif($massagedData[$key]['temperature'] <= $notificationsSetThresholds['sensor_'.$key.'_lower_temperature']) {
                $notificationsEmailData[$key]['temperature']['lower'] = 'Lower Temp Threshold Reached ';
                $notificationsEmailData[$key]['temperature']['value'] = $massagedData[$key]['temperature'];
            }
            if ($massagedData[$key]['humidity'] >= $notificationsSetThresholds['sensor_'.$key.'_upper_humidity']) {
                $notificationsEmailData[$key]['humidity']['upper'] = 'Upper Humidity Threshold Reached ';
                $notificationsEmailData[$key]['humidity']['value'] =  $massagedData[$key]['humidity'];
            }elseif($massagedData[$key]['humidity'] <= $notificationsSetThresholds['sensor_'.$key.'_lower_humidity']) {
                $notificationsEmailData[$key]['humidity']['lower'] = 'Lower Humidity Threshold Reached ';
                $notificationsEmailData[$key]['humidity']['value'] = $massagedData[$key]['humidity'];
            }
            $notificationsEmailData = ['notificationsData' => $notificationsEmailData];
        }
        return $notificationsEmailData;
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
     * @param array $reportData
     * @param array $sensorData
     * @param string $twigEmail
     * @param string $emailTitle
     * @throws TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function sendReport(array $sensorData, string $twigEmail, $emailTitle = 'Report') {
        $emailsArray = [
            'from' => $_ENV["FROM_EMAIL"],
            'to' => $_ENV["TO_EMAIL"]
        ];
        $valid = ArraysUtils::validateEmails(($emailsArray));
        if (!$valid) {
            $this->logger->log('Invalid Emails.', ['sender' => __FUNCTION__, 'emails' => $emailsArray], Logger::CRITICAL);
        }
        if ($valid && !empty($sensorData))  {
            $message = (new Email())
                ->from($_ENV["FROM_EMAIL"])
                ->to($_ENV["TO_EMAIL"])
                ->subject($emailTitle)
                ->html(
                    $this->templating->render(
                        $twigEmail,
                        $sensorData
                    ),
                    'text/html'
                );
            $this->mailer->send($message);

            $this->logger->log($emailTitle.' Sent!',
                $sensorData, Logger::DEBUG
            );
        }
    }

    /**
     * Update weatherReport Table.
     *
     * @param $reportType
     * @throws \Exception
     */
    private function updateWeatherReport($reportType) {
        $lastSentReport = $this->getLastSentReport($reportType);
        // Update Email Report table after email is sent.
        $weatherReport = new WeatherReportEntity();
        $weatherReport->setEmailBody($reportType);
        $weatherReport->setLastSentCounter(isset($lastSentReport[0]) ? ($lastSentReport[0]->getLastSentCounter() + 1) : 1);


        $weatherReport->setLastSentDate(StationDateTime::dateNow('',false,'Y-m-d' ));
        $weatherReport->setLastSentTime(StationDateTime::dateNow('',false,'H:i:s' ));

        try {
            $this->entityManager->persist($weatherReport);
        } catch (ORMException $e) {
            $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
        }
        try {
            $this->entityManager->flush();
        } catch (OptimisticLockException | ORMException $e) {
            $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
        }

    }
}
