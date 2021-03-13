<?php
/**
 * @author Dani Stark.
 */
namespace App\Listeners;

use App\Entity\SensorEntity;
use App\Entity\WeatherReportEntity;
use App\Repository\WeatherReportRepository;
use App\Utils\ArraysUtils;
use App\WeatherConfiguration;
use App\WeatherStationLogger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monolog\Logger;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Utils\StationDateTime;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class PostListener
 */
class PostListener {

    // Daily report
    private const REPORT_DAILY = 'Daily Report';

    // Notifications title
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

    /** @var WeatherReportRepository  */
    private $weatherReportRepository;

    /**
     * @var WeatherConfiguration
     */
    private $config;

    /**
     * PostListener constructor.
     *
     * @param Environment $templating
     * @param MailerInterface $mailer
     * @param WeatherStationLogger $logger
     * @param WeatherReportRepository $weatherReportRepository
     * @param WeatherConfiguration $config
     */
    public function __construct(
        Environment $templating,
        MailerInterface $mailer,
        WeatherStationLogger $logger,
        WeatherReportRepository $weatherReportRepository,
        WeatherConfiguration $config) {
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->weatherReportRepository = $weatherReportRepository;
        $this->config = $config;
    }

    /**
     * Post listener: Listens for SensorController posts.
     *
     * @param LifecycleEventArgs $args
     * @throws \Exception
     */
    public function postPersist(LifecycleEventArgs $args) {
        $this->entityManager = $args->getObjectManager();
        $postInstance = $args->getEntity();
        $reportEnabled = $this->config->getConfigKey('weatherReport.readingReportEnabled') ?? false;

        $notificationsReportEnabled = $this->config->getConfigKey('weatherReport.notificationsReportEnabled') ?? false;
        // only act on "Sensor" entity
        if (($postInstance instanceof SensorEntity) && ($reportEnabled || $notificationsReportEnabled)) {
            /// Get latest Sensor Readings.
            $latestSensorData = $this->getLatestSensorData();
            // Prepare notifications report
            $latestNotificationsData = $this->prepareNotifications($latestSensorData);

            // Last Sent Notifications Report
            $lastSentNotificationReport = $this->getLastSentReport(self::REPORT_NOTIFICATIONS);
            // Check if notification report needs to be sent.
            $notificationsCounter = $this->shouldSendReport($lastSentNotificationReport, self::REPORT_TYPE_NOTIFICATION);
            if ($notificationsCounter && $notificationsCounter !== 0 && $notificationsReportEnabled && !empty($latestNotificationsData)) {
                try {
                    $success = $this->sendReport($latestNotificationsData, '/sensor/weatherStationReportNotifications.html.twig', self::REPORT_NOTIFICATIONS);
                    if ($success === true) {
                        $this->updateWeatherReport(self::REPORT_NOTIFICATIONS, $notificationsCounter);
                    }

                } catch (TransportExceptionInterface | LoaderError | RuntimeError | SyntaxError $e) {
                    $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
                }
            }

             // Last Sent Daily Report
            $lastSentDailyReport = $this->getLastSentReport(self::REPORT_DAILY);

            // Check if daily report needs to be sent.
            $reportCounter = $this->shouldSendReport($lastSentDailyReport, self::REPORT_TYPE_REPORT);
            if ($reportCounter && $reportCounter !==0 && $reportEnabled && !empty($latestSensorData)) {
                try {
                    $success = $this->sendReport($latestSensorData, '/sensor/weatherStationDailyReport.html.twig', self::REPORT_DAILY);
                    if ($success) {
                        $this->updateWeatherReport(self::REPORT_DAILY, $reportCounter);
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
    private function shouldSendReport(array $lastSentDailyReport, string $reportType = '') {
        $counterUpdate = 0;
        $firstNotificationTime = $this->config->getConfigKey('weatherReport.firstNotificationTime');
        $firstReportTime =  $this->config->getConfigKey('weatherReport.firstReportTime');
        $secondNotificationTime =  $this->config->getConfigKey('weatherReport.secondNotificationTime');
        $secondReportTime =  $this->config->getConfigKey('weatherReport.secondReportTime');
        $firstReport = $reportType === 'notification' ? ($firstNotificationTime ?? self::FIRST_NOTIFICATION_TIME) : ($firstReportTime ?? self::FIRST_REPORT_TIME);
        $secondReport = $reportType === 'notification' ? ($secondNotificationTime ?? self::SECOND_NOTIFICATION_TIME) : ($secondReportTime ?? self::SECOND_REPORT_TIME);

        $currentTime = StationDateTime::dateNow('', true, 'H:i:s');
        /** @var WeatherReportEntity $lastSentDailyReport */
        $lastReportLastCounter = isset($lastSentDailyReport[0]) ? $lastSentDailyReport[0]->getLastSentCounter() : 0;

        if (!empty($lastSentDailyReport)) {
            // First & Second report already sent, get out.
            if ($lastReportLastCounter === 2) {
                return false;
                // First report already sent, & counter = 1, send second report.
            } elseif ($lastReportLastCounter === 1 && $currentTime > $secondReport) {
                return 2;
            }
        } else {
            // empty should send
            // first Report of the day
            if ($currentTime > $firstReport && $currentTime < $secondReport && $lastReportLastCounter === 0) {
                return 1;
            }
            // When table is empty and first report time has passed, skip first report and set counter to 2.
            if ($currentTime > $secondReport && $lastReportLastCounter === 0) {
               return 2;
            }
        }
        return $counterUpdate;
    }

    /**
     * Get latest sensor readings.
     *
     * @return array[]
     */
    private function getLatestSensorData(): array {
        // Construct station IDs array.
        $stationSensorConfigs = $this->config->getConfigs()['sensor']['config'];
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
        $tempSorted = ArraysUtils::arraySortByColumn($prepareData, 'temperature');
        $humiditySorted = ArraysUtils::arraySortByColumn($prepareData, 'humidity');
        $sortedArray = [];
        foreach($tempSorted as $key => $value) {
            $sortedArray[$key] = [
                'temperature' => $value,
                'humidity' => $humiditySorted[$key]
            ];
        }
        $weatherData = ['weatherData' => $sortedArray];
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
     * Prepare notifications data.
     *
     * @param array $latestSensorData
     * @return array
     */
    private function prepareNotifications(array $latestSensorData): array {
        $massagedData = $latestSensorData['weatherData'] ?? [];
        if (empty($massagedData)) {
            $this->logger->log("weatherData is not set",["function"=> __FUNCTION__], Logger::CRITICAL);
            return $massagedData;
        }
        $thresholdTempUpper = $thresholdTempLower = $thresholdHumidUpper = $thresholdHumidLower = false;
        // Loop over configured Thresholds, send email.
        $notificationsEmailData = [];
        foreach($massagedData as $key => $value) {
            if ($massagedData[$key]['temperature'] >= $this->config->getConfigs()['sensor'][$key]['upper']['temperature']) {
                $thresholdTempUpper = true;
                $notificationsEmailData[$key]['temperature']['upper'] = 'Upper Temp Threshold Reached ';
                $notificationsEmailData[$key]['temperature']['value'] = $massagedData[$key]['temperature'];
            } elseif($massagedData[$key]['temperature'] <= $this->config->getConfigs()['sensor'][$key]['lower']['temperature']) {
                $thresholdTempLower = true;
                $notificationsEmailData[$key]['temperature']['lower'] = 'Lower Temp Threshold Reached ';
                $notificationsEmailData[$key]['temperature']['value'] = $massagedData[$key]['temperature'];
            }
            if ($massagedData[$key]['humidity'] >= $this->config->getConfigs()['sensor'][$key]['upper']['humidity']) {
                $thresholdHumidUpper = true;
                $notificationsEmailData[$key]['humidity']['upper'] = 'Upper Humidity Threshold Reached ';
                $notificationsEmailData[$key]['humidity']['value'] =  $massagedData[$key]['humidity'];
            }elseif($massagedData[$key]['humidity'] <= $this->config->getConfigs()['sensor'][$key]['lower']['humidity']) {
                $thresholdHumidLower = true;
                $notificationsEmailData[$key]['humidity']['lower'] = 'Lower Humidity Threshold Reached ';
                $notificationsEmailData[$key]['humidity']['value'] = $massagedData[$key]['humidity'];
            }
        }
        if ($thresholdTempUpper || $thresholdTempLower || $thresholdHumidUpper || $thresholdHumidLower) {
            $notificationsEmailData = ['notificationsData' => $notificationsEmailData];
        }else {
            $notificationsEmailData = [];
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
     * @return bool
     */
    private function sendReport(array $sensorData, string $twigEmail, string $emailTitle = 'Report'): bool {
        $success = true;
        $fromEmail = $this->config->getConfigKey('weatherReport.fromEmail');
        $toEmail =  $this->config->getConfigKey('weatherReport.toEmail');
        $emailsArray = [
            'from' =>  $fromEmail,
            'to' => $toEmail
        ];
        $valid = ArraysUtils::validateEmails(($emailsArray));
        if (!$valid) {
            $this->logger->log('Invalid Emails.', ['sender' => __FUNCTION__, 'emails' => $emailsArray], Logger::CRITICAL);
            return !$success;
        }
        //return true;
        if (!empty($sensorData) && !$this->config->getConfigKey('disableEmails')) {
            $message = (new Email())
                ->from($fromEmail)
                ->to($toEmail)
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
                    [$sensorData, 'exceptionMsg' =>$exception->getMessage()], Logger::DEBUG
                );

            }
        } else {
            $success = false;
        }
        return $success;
    }

    /**
     * Update weatherReport Table.
     *
     * @param string $reportType
     * @param $notificationsCounter
     */
    private function updateWeatherReport(string $reportType, $notificationsCounter) {
        try {
            $this->weatherReportRepository->save(
                ['counter' => $notificationsCounter,
                    'emailBody' =>$reportType]
            );
        } catch (OptimisticLockException | ORMException $e) {
            $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
        }
    }
}
