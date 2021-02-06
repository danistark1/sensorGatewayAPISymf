<?php
/**
 * @author Dani Stark.
 */
namespace App\Listeners;

use App\Controller\SensorController;
use App\Entity\SensorEntity;
use App\Entity\WeatherReportEntity;
use App\Logger\MonologDBHandler;
use App\Repository\WeatherReportRepository;
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
     * Notification type - notification
     */
    private const REPORT_TYPE_NOTIFICATION = 'notification';

    /**
     * Notification type - report
     */
    private const REPORT_TYPE_REPORT = 'report';

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

    private $weatherReportRepository;

    /**
     * PostListener constructor.
     *
     * @param Environment $templating
     * @param MailerInterface $mailer
     * @param WeatherStationLogger $logger
     * @param WeatherReportRepository $weatherReportRepository
     */
    public function __construct(
        Environment $templating,
        MailerInterface $mailer,
        WeatherStationLogger $logger,
        WeatherReportRepository $weatherReportRepository) {
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->weatherReportRepository = $weatherReportRepository;
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
        $notificationsReportEnabled = $_ENV["NOTIFICATIONS_REPORT_ENABLED"] ?? false;
        // only act on "Sensor" entity
        if (($postInstance instanceof SensorEntity) && ($reportEnabled || $notificationsReportEnabled)) {
            /// Get latest Sensor Readings.
            $latestSensorData = $this->getLatestSensorData();
            // Prepare notifications report
            $latestNotificationsData = $this->prepareNotifications($latestSensorData);

            // Last Sent Notifications Report
            $lastSentNotificationReport = $this->getLastSentReport(self::REPORT_NOTIFICATIONS);
            // Check if notification report needs to be sent.
            $shouldSendNotificationReport = $this->shouldSendReport($lastSentNotificationReport, self::REPORT_TYPE_NOTIFICATION);

            if ($shouldSendNotificationReport && $notificationsReportEnabled && !empty($latestNotificationsData)) {
                try {
                    $success = $this->sendReport($latestNotificationsData, '/sensor/weatherStationReportNotifications.html.twig', self::REPORT_NOTIFICATIONS);
                    if ($success === true) {
                        $this->updateWeatherReport(self::REPORT_NOTIFICATIONS);
                    }

                } catch (TransportExceptionInterface | LoaderError | RuntimeError | SyntaxError $e) {
                    $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
                }
            }

             // Last Sent Daily Report
            $lastSentDailyReport = $this->getLastSentReport(self::REPORT_DAILY);

            // Check if daily report needs to be sent.
            $shouldSendReport = $this->shouldSendReport($lastSentDailyReport, self::REPORT_TYPE_REPORT);
            if ($shouldSendReport && $reportEnabled && !empty($latestSensorData)) {
                try {
                    $success = $this->sendReport($latestSensorData, '/sensor/weatherStationDailyReport.html.twig', self::REPORT_DAILY);
                    if ($success) {
                        $this->updateWeatherReport(self::REPORT_DAILY);
                    }
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
        $timerOk = $currentTime >= $firstReport || $currentTime >= $secondReport;
        $lastReportLastCounter = isset($lastSentDailyReport[0]) ? $lastSentDailyReport[0]->getLastSentCounter() : null;

        if (!empty($lastSentDailyReport)) {
            // check if first or second report
            if ($lastReportLastCounter === 2) {
                return $shouldSendReport = false;
            } elseif ($lastReportLastCounter === 1 && $timerOk) {
                $shouldSendReport = true;
            }
        } else {
            // empty shoud send
            if ($timerOk) {
                $shouldSendReport = true;
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
        $stationSensorConfigs = SensorController::constructSensorData();
        // ["bedroom" => "6126"
        //  "basement" => "3026"
        //   "garage" => "8166"
        //   "living_room" => "15043"
        //   "outside" => "12154"]
        // Remove any invalid entries before calling temp & humidity methods on an empty array.
        $prepareData = $weatherData =  [];
        foreach ($stationSensorConfigs as $sensorName => $stationID) {
            $sensorData = $this->entityManager->getRepository(SensorEntity::class)->findOrdered($sensorName);
            if(!empty($sensorData)) {
                $prepareData[$sensorName] = [
                    'temperature' => $sensorData[0]->getTemperature(),
                    'humidity' => $sensorData[0]->getHumidity()
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
        $this->entityManager->clear();
        return $reportDataDb;
    }

    /**
     *
     * @throws TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function prepareNotifications($latestSensorData): array {
        $massagedData = $latestSensorData['weatherData'];
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
        }
        $notificationsEmailData = ['notificationsData' => $notificationsEmailData];
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
     * @return bool
     */
    private function sendReport(array $sensorData, string $twigEmail, string $emailTitle = 'Report'): bool {
        $success = true;
        $valid = false;
        $emailsArray = [
            'from' => $_ENV["FROM_EMAIL"],
            'to' => $_ENV["TO_EMAIL"]
        ];
        $valid = ArraysUtils::validateEmails(($emailsArray));
        if (!$valid) {
            $this->logger->log('Invalid Emails.', ['sender' => __FUNCTION__, 'emails' => $emailsArray], Logger::CRITICAL);

            return !$success;
        }
        if ($valid && !empty($sensorData)) {
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
                )
            ;
            try {
                $this->mailer->send($message);
            } catch (TransportExceptionInterface $exception) {
                $success = false;
                $this->logger->log($emailTitle . ' Not Sent!',
                    $sensorData, Logger::DEBUG
                );

            }
        }
        return $success;
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
        $counter = isset($lastSentReport[0]) ? ($lastSentReport[0]->getLastSentCounter() + 1) : 1;
        try {
            $this->weatherReportRepository->save(
                ['counter' => $counter,
                    'emailBody' =>$reportType]
            );
        } catch (OptimisticLockException | ORMException $e) {
            $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
        }
    }
}
