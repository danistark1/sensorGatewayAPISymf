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

/**
 * Monolog handler.
 */
class MonologDBHandler extends AbstractProcessingHandler {
    /** @var EntityManagerInterface */
    protected $em;

    private const EMAIL_LOG = "Sensor Gateway Logger";

    private const CRITICAL = 'critical';

    private const DEBUG = 'debug';

    private const INFO = 'info';

    private const WARNING = 'warning';

    /**
     * @var SensorConfiguration
     */
    protected $config;

    /** @var MailerInterface  */
    private $mailer;

    /** @var SensorCacheHandler  */
    protected $configCache;

    /**
     * @param EntityManagerInterface $em
     * @param SensorCacheHandler $configCache
     * @param MailerInterface $mailer
     * @param int $level
     * @param bool $bubble
     */
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
        // Check global logging config
        $loggingLevelRecord = strtolower($record['level_name']) ?? self::CRITICAL;
        $loggingEnabled = $this->configCache->getConfigKey('logging-enabled');
        $loggingLevel = $this->configCache->getConfigKey('logging-level') ?? self::CRITICAL;

        $emailLoggingEnabled = $this->configCache->getConfigKey('email-logging-enabled');
        $emailLoggingLevel = $this->configCache->getConfigKey('email-logging-level');
        $shouldEmailLog = true;
        if ($emailLoggingEnabled) {
            if (!in_array($loggingLevelRecord, $emailLoggingLevel)) {
                $shouldEmailLog = false;
            }
        }
        if ($loggingEnabled === 1 && $loggingLevelRecord === $loggingLevel) {
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
                if ($emailLoggingEnabled && $shouldEmailLog) {
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
    }

}
