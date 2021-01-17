<?php

namespace App\Logger;
use App\Entity\WeatherLogger;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;

class MonologDBHandler extends AbstractProcessingHandler {
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
        parent::__construct();
    }

    /**
     * Called when writing to our database
     *
     * @param array $record
     */
    protected function write(array $record): void {
        try {

            $logEntry = new WeatherLogger();
            $logEntry->setMessage($record['message']);
            $logEntry->setLevel($record['level']);
            $logEntry->setLevelName($record['level_name']);

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


            $this->em->persist($logEntry);
            $this->em->flush();
        } catch (\Exception $e) {

        }
    }

}
