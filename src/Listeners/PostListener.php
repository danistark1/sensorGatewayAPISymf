<?php
/**
 * @author Dani Stark.
 */
namespace App\Listeners;

use App\Entity\SensorEntity;
use App\Entity\SensorMoistureEntity;
use App\Entity\SensorReportEntity;
use App\Repository\SensorReportRepository;
use App\Utils\ArraysUtils;
use App\SensorCacheHandler;
use App\SensorGatewayLogger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monolog\Logger;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Utils\SensorDateTime;
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
     * Notification type - notification moisture
     */
    private const REPORT_TYPE_NOTIFICATION_MOISTURE = 'notification_moisture';

    /**
     * Notification type - report moisture
     */
    private const REPORT_TYPE_REPORT_MOISTURE = 'report_moisture';

    /**
     * Notification type - notification
     */
    private const REPORT_TYPE_NOTIFICATION = 'notification_weather';

    /**
     * Notification type - report
     */
    private const REPORT_TYPE_REPORT = 'report_weather';

    /**
     * @var Environment
     */
    public $templating;

    /**
     * @var MailerInterface
     */
    public $mailer;

    /**
     * @var SensorGatewayLogger
     */
    private $logger;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /** @var SensorReportRepository  */
    private $weatherReportRepository;

    /** @var SensorCacheHandler  */
    private $configCache;

    /**
     * PostListener constructor.
     *
     * @param Environment $templating
     * @param MailerInterface $mailer
     * @param SensorGatewayLogger $logger
     * @param SensorReportRepository $weatherReportRepository
     * @param SensorCacheHandler $configCacheHandler
     */
    public function __construct(
        Environment $templating,
        MailerInterface $mailer,
        SensorGatewayLogger $logger,
        SensorReportRepository $weatherReportRepository,
        SensorCacheHandler $configCacheHandler) {
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->weatherReportRepository = $weatherReportRepository;
        $this->configCache = $configCacheHandler;

    }

    /**
     * Post listener: Listens for SensorController posts.
     *
     * @param LifecycleEventArgs $args
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function postPersist(LifecycleEventArgs $args) {
        $this->entityManager = $args->getObjectManager();
        $postInstance = $args->getEntity();
        if ((!$postInstance instanceof SensorEntity) && (!$postInstance instanceof SensorMoistureEntity)) {
            return;
        }
        $reportEnabled = $this->configCache->isConfigSetKey('weatherReport-readingReportEnabled');
        $notificationsReportEnabled = $this->configCache->isConfigSetKey('weatherReport-notificationsReportEnabled');

        // TODO Moisture Report
        $moistureReportEnabled = $this->configCache->isConfigSetKey('sensorReport-moistureReportEnabled');
        $moistureNotificationsEnabled = $this->configCache->isConfigSetKey('sensorReport-moistureNotificationsEnabled');
        if ($moistureNotificationsEnabled && $postInstance instanceof SensorMoistureEntity) {
            // Last Sent Notifications moisture Report
            $lastSentNotificationMoisture = $this->getLastSentReport(self::REPORT_TYPE_NOTIFICATION_MOISTURE);
            // Check if notification report needs to be sent.
            $notificationsMoistureCounter = $this->shouldSendReportTime($lastSentNotificationMoisture, self::REPORT_TYPE_NOTIFICATION_MOISTURE);
            $moistureEntity = $this->shouldSendReportValues($postInstance);
            /** SensorMoistureEntity  $postInstance */
            if ($moistureEntity !== '' && $notificationsMoistureCounter && $notificationsMoistureCounter !== 0 && !empty($postInstance->getSensorReading())) {
                try {
                    $result = $this->sendReport(
                        $moistureEntity,
                        '/sensor/moistureReportNotifications.html.twig',
                        $moistureEntity['emailTitle']);
                    if (!empty($result)) {
                        $this->updateWeatherReport(self::REPORT_TYPE_NOTIFICATION_MOISTURE, $notificationsMoistureCounter, $result);
                    }

                } catch (TransportExceptionInterface | LoaderError | RuntimeError | SyntaxError $e) {
                    $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
                }
            }
        }
        // only act on "Sensor" entity
        if ($reportEnabled || $notificationsReportEnabled) {
            /// Get latest Sensor Readings.
            $latestSensorData = $this->getLatestWeatherSensorData();
            // Prepare notifications report
            $latestNotificationsData = $this->prepareNotifications($latestSensorData);

            // Last Sent Notifications Report
            $lastSentNotificationReport = $this->getLastSentReport(self::REPORT_NOTIFICATIONS);
            // Check if notification report needs to be sent.
            $notificationsCounter = $this->shouldSendReportTime($lastSentNotificationReport, self::REPORT_TYPE_NOTIFICATION);
            if ($notificationsCounter && $notificationsCounter !== 0 && $notificationsReportEnabled && !empty($latestNotificationsData)) {
                try {
                    $result = $this->sendReport(
                        $latestNotificationsData,
                        '/sensor/weatherStationReportNotifications.html.twig',
                        $this->configCache->getConfigKey('weatherReport-emailTitleNotifications') ?? self::REPORT_NOTIFICATIONS);
                    if (!empty($result)) {
                        $this->updateWeatherReport(self::REPORT_NOTIFICATIONS, $notificationsCounter, $result);
                    }

                } catch (TransportExceptionInterface | LoaderError | RuntimeError | SyntaxError $e) {
                    $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
                }
            }

             // Last Sent Daily Report
            $lastSentDailyReport = $this->getLastSentReport(self::REPORT_DAILY);

            // Check if daily report needs to be sent.
            $reportCounter = $this->shouldSendReportTime($lastSentDailyReport, self::REPORT_TYPE_REPORT);
            if ($reportCounter && $reportCounter !== 0 && $reportEnabled && !empty($latestSensorData)) {
                try {
                    $result = $this->sendReport(
                        $latestSensorData,
                        '/sensor/weatherStationDailyReport.html.twig',
                        $this->configCache->getConfigKey('weatherReport-emailTitleDailyReport') ?? self::REPORT_DAILY);
                    if (!empty($result)) {
                        $this->updateWeatherReport(self::REPORT_DAILY, $reportCounter, $result);
                    }
                } catch (TransportExceptionInterface | LoaderError | RuntimeError | SyntaxError $e) {
                    $this->logger->log($e->getMessage(), ['sender' => __FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
                }
            }
        }
    }

    /**
     * Check if a moisture report will need to be sent.
     *
     * @param $postInstance
     * @return array|string
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function shouldSendReportValues($postInstance) {
        $title = '';
        /** SensorMoistureEntity $reportType */
        $sensorID = $postInstance->getSensorID();
        if ($sensorID) {
            $sensorUpperThreshold = $this->configCache->getConfigKey("sensor-$sensorID-upper-moisture");
            $sensorLowerThreshold = $this->configCache->getConfigKey("sensor-$sensorID-lower-moisture");
            $currentReading = $postInstance->getSensorReading();
            $name = $postInstance->getName();
            if ($currentReading >= $sensorUpperThreshold) {
                $title = "Your $name is soaked!";
                $event = "Upper moisture threshold reached.";
            } elseif ($currentReading <= $sensorLowerThreshold) {
                $title = "You should water your $name, its feeling thirsty!";
                $event = "Lower moisture threshold reached.";
            }
        }
        if (!empty($title)) {
            // Construct notification event.
            $msg = [
                'sensorID' => $sensorID,
                'name' => $postInstance->getName(),
                'event' => $event,
                'reading' => $postInstance->getSensorReading(),
                'location' => $postInstance->getSensorLocation(),
                'emailTitle' => $title
            ];
        }
        return $msg;
        // Get all configured sensor thresholds by type and decide if a report should be send.
    }

    /**
     * Check if  a report needs to be sent. (Notifications or Daily)
     *
     * @param $lastSentDailyReport
     * @param string $reportType Empty for Daily report.
     * @return bool A report needs to be sent.
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function shouldSendReportTime(array $lastSentDailyReport, string $reportType = '') {
        $counterUpdate = 0;
        $firstNotificationTime = $this->configCache->getConfigKey("$reportType-firstNotificationTime");
        $firstReportTime = $this->configCache->getConfigKey("$reportType-firstReportTime");
        $secondNotificationTime = $this->configCache->getConfigKey("$reportType-secondNotificationTime");
        $secondReportTime = $this->configCache->getConfigKey("$reportType-secondReportTime");

        if (str_contains('notification', $reportType)) {
            $firstReport = $firstNotificationTime ?? self::FIRST_NOTIFICATION_TIME;
            $secondReport = $secondNotificationTime ??  self::SECOND_NOTIFICATION_TIME;
        } else {
            $firstReport = $firstReportTime ?? self::FIRST_REPORT_TIME;
            $secondReport = $secondReportTime ?? self::SECOND_REPORT_TIME;
        }
        $currentTime = SensorDateTime::dateNow('', true, 'H:i:s');
        /** @var SensorReportEntity $lastSentDailyReport */
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

    private function getLatestMoistureSensorData() {

    }
    /**
     * Get latest sensor readings.
     *
     * @return array[]
     */
    private function getLatestWeatherSensorData(): array {
        // Construct station IDs array.
        $stationSensorConfigs = $this->configCache->getSensorConfigs();
       // return $stationSensorConfigs;
        // ["bedroom" => "6126"
        //  "basement" => "3026"
        //   "garage" => "8166"
        //   "living_room" => "15043"
        //   "outside" => "12154"]
        // Remove any invalid entries before calling temp & humidity methods on an empty array.

        $prepareData =  [];
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
        $currentDate = SensorDateTime::dateNow('', false, 'Y-m-d');
        // Get the last inserted report for the current day;
        $reportDataDb = $this->entityManager->getRepository(SensorReportEntity::class)->findBy(
            array('reportType' => $reportType, 'lastSentDate' => $currentDate),
            array('id'=> 'DESC'),
            1,
            0);
        $this->entityManager->clear();
        return $reportDataDb;
    }


    private function prepareMoistureNotifications(array $latestSensorData) {

    }

    /**
     * Prepare notifications data.
     *
     * @param array $latestSensorData
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function prepareNotifications(array $latestSensorData): array {
        $massagedData = $latestSensorData['weatherData'] ?? [];
        if (empty($massagedData)) {
            $this->logger->log("weatherData is not set",["function"=> __CLASS__.__FUNCTION__], Logger::CRITICAL);
            return $massagedData;
        }
        $thresholdTempUpper = $thresholdTempLower = $thresholdHumidUpper = $thresholdHumidLower = false;
        // Loop over configured Thresholds, send email.
        $notificationsEmailData = [];
        foreach($massagedData as $key => $value) {
            if ($massagedData[$key]['temperature'] >= $this->configCache->getConfigKey('sensor-'.$key.'-'.'upper-temperature')) {
                $thresholdTempUpper = true;
                $notificationsEmailData[$key]['temperature']['upper'] = 'Upper Temp Threshold Reached ';
                $notificationsEmailData[$key]['temperature']['value'] = $massagedData[$key]['temperature'];
            } elseif($massagedData[$key]['temperature'] <=  $this->configCache->getConfigKey('sensor-'.$key.'-'.'lower-temperature')) {
                $thresholdTempLower = true;
                $notificationsEmailData[$key]['temperature']['lower'] = 'Lower Temp Threshold Reached ';
                $notificationsEmailData[$key]['temperature']['value'] = $massagedData[$key]['temperature'];
            }
            if ($massagedData[$key]['humidity'] >= $this->configCache->getConfigKey('sensor-'.$key.'-'.'upper-humidity')) {
                $thresholdHumidUpper = true;
                $notificationsEmailData[$key]['humidity']['upper'] = 'Upper Humidity Threshold Reached ';
                $notificationsEmailData[$key]['humidity']['value'] =  $massagedData[$key]['humidity'];
            } elseif($massagedData[$key]['humidity'] <=  $this->configCache->getConfigKey('sensor-'.$key.'-'.'lower-humidity')) {
                $thresholdHumidLower = true;
                $notificationsEmailData[$key]['humidity']['lower'] = 'Lower Humidity Threshold Reached ';
                $notificationsEmailData[$key]['humidity']['value'] = $massagedData[$key]['humidity'];
            }
        }
        if ($thresholdTempUpper || $thresholdTempLower || $thresholdHumidUpper || $thresholdHumidLower) {
            $notificationsEmailData = ['notificationsData' => $notificationsEmailData];
        } else {
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
     * @return bool
     * @throws TransportExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError*@throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function sendReport(array $sensorData, string $twigEmail, string $emailTitle = 'Report'): array {
        $emailData = [];
        $fromEmail = $this->configCache->getConfigKey('weatherReport-fromEmail');
        $toEmail = $this->configCache->getConfigKey('weatherReport-toEmail');
        $emailsArray = [
            'from' =>  $fromEmail,
            'to' => $toEmail
        ];
        $valid = ArraysUtils::validateEmails(($emailsArray));
        if (!$valid) {
            $this->logger->log('Invalid Emails.', ['sender' => __CLASS__.__FUNCTION__, 'emails' => $emailsArray], Logger::CRITICAL);
            return $emailData;
        }
        if (!empty($sensorData) && !$this->configCache->getConfigKey('weatherReport-disableEmails')) {
            $emailData = [
                'from' => $fromEmail,
                'to' => $toEmail,
                'subject' => $emailTitle,
                'sensorData' => $sensorData
            ];
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

                $this->logger->log($emailTitle . ' Not Sent!',
                    [
                        'sender' => __CLASS__.__FUNCTION__,
                        'sensorData' => $sensorData,
                        'exceptionMsg' => $exception->getMessage()
                    ],
                    Logger::DEBUG
                );

            }
        }
        return $emailData;
    }

    /**
     * Update weatherReport Table.
     *
     * @param string $reportType
     * @param $notificationsCounter
     */
    private function updateWeatherReport(string $reportType, $notificationsCounter, $emailBody) {
        try {
            //$lastSent = $this->getLastSentReport($reportType);
            //$notificationsCounter = $this->shouldSendReport($lastSent, $reportType);
            //$this->logger->log('updateWeatherReport',['updateWeatherReportCounter' => $notificationsCounter], Logger::INFO);

            $this->weatherReportRepository->save(
                [
                    'counter' => $notificationsCounter,
                    'emailBody' => $emailBody,
                    'reportType' => $reportType
                ]
            );
        } catch (OptimisticLockException | ORMException $e) {
            $this->logger->log($e->getMessage(), ['sender' => __CLASS__.__FUNCTION__, 'errorCode' => $e->getCode()], Logger::CRITICAL);
        }
    }
}
