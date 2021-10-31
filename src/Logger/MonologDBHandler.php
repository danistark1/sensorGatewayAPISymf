<?php

namespace App\Logger;
use App\Entity\SensorLoggerEntity;
use App\GatewayCache\SensorCacheHandler;
use App\SensorConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use App\Utils\SensorDateTime;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MonologDBHandler extends AbstractProcessingHandler {
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    private const EMAIL_LOG = "Sensor Gateway Logger";
    /**
     * @var SensorConfiguration
     */
    protected $config;

    /** @var MailerInterface  */
    private $mailer;

    /** @var SensorCacheHandler  */
    protected $configCache;

    public function __construct(EntityManagerInterface $em, SensorCacheHandler $configCache, MailerInterface $mailer, $level = Logger::API, $bubble = true) {
        $this->em = $em;
        $this->configCache = $configCache;
        $this->mailer = $mailer;
        parent::__construct($level, $bubble);
    }

    /**
     * Called when writing to our database
     *
     * @param array $record
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function write(array $record): void {
        // Check if debugging is enabled.
        $debug = $this->configCache->getConfigKey('application-debug');
        // Check if email logging is enabled, get configured logging level.
        $emailLoggingEnabled = $this->configCache->getConfigKey('email-logging-enabled');
        $emailLoggingLevel = $this->configCache->getConfigKey('email-logging-level');

        if ($emailLoggingEnabled) {
            if (!in_array(strtolower($record['level_name']), $emailLoggingLevel)) {
                return;
            }
        }
        if (!empty($debug) && $debug == 1) {
            //if( 'doctrine' == $record['channel'] ) {
            // TODO Log level should be configurable
//            if ((int)$record['level'] === Logger::INFO || (int)$record['level'] === Logger::DEBUG) {
//                return;
//            }

            try {
                $logEntry = new SensorLoggerEntity();
                $logEntry->setMessage($record['message']);
                $logEntry->setLevel($record['level']);
                $logEntry->setLevelName($record['level_name']);
                $logDateTime = SensorDateTime::dateNow();
                $logEntry->setInsertDateTime($logDateTime);

                if (is_array($record['extra'])) {
                    $logEntry->setExtra($record['extra']);
                } else {
                    $logEntry->setExtra([]);
                }

                if (is_array($record['context'])) {
                    $logEntry->setContext($record['context']);
                } else {
                    $logEntry->setContext([]);
                }
                $this->em->clear();
                $this->em->persist($logEntry);
                $this->em->flush();
                if ($emailLoggingEnabled) {
                    $message = new Email();
                    $message->addTo($this->configCache->getConfigKey('admin-email'));
                    $message->from($this->configCache->getConfigKey('weatherReport-fromEmail'));
                    $message->subject(self::EMAIL_LOG);
                    $message->text($record['message']);
                    $this->mailer->send($message);
                }
            } catch (\Exception $e) {
                error_log($record['message']);
                error_log($e->getMessage());
            }
        }


//        // Ensure the doctrine channel is ignored (unless its greater than a warning error), otherwise you will create an infinite loop, as doctrine like to log.. a lot..
//        if( 'doctrine' == $record['channel'] ) {
//
//            if( (int)$record['level'] >= Logger::WARNING ) {
//                error_log($record['message']);
//            }
//
//            return;
//        }
//        // Only log errors greater than a warning
//        // TODO - you could ideally add this into configuration variable
//        if( (int)$record['level'] >= Logger::NOTICE ) {
//
//            try
//            {
//                // Logs are inserted as separate SQL statements, separate to the current transactions that may exist within the entity manager.
//                $em = $this->_container->get('doctrine')->getManager();
//                $conn = $em->getConnection();
//
//                $created = date('Y-m-d H:i:s');
//
//                $serverData = ""; //$record['extra']['server_data'];
//                $referer = "";
//                if (isset($_SERVER['HTTP_REFERER'])){
//                    $referer= $_SERVER['HTTP_REFERER'];
//                }
//
//                $stmt = $em->getConnection()->prepare('INSERT INTO system_log(log, level, server_data, modified, created)
//                                    VALUES(' . $conn->quote($record['message']) . ', \'' . $record['level'] . '\', ' . $conn->quote($referer) . ', \'' . $created . '\', \'' . $created . '\');');
//                $stmt->execute();
//
//            } catch( \Exception $e ) {
//
//                // Fallback to just writing to php error logs if something really bad happens
//                error_log($record['message']);
//                error_log($e->getMessage());
//            }











































    }

}
