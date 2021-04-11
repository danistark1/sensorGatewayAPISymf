<?php

namespace App\Repository;

use App\Entity\SensorEntity;
use App\Entity\WeatherReportEntity;
use App\Utils\StationDateTime;
use App\WeatherStationLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WeatherReportEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method WeatherReportEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method WeatherReportEntity[]    findAll()
 * @method WeatherReportEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WeatherReportRepository extends ServiceEntityRepository {

    /** @var \App\WeatherStationLogger  */
    private $logger;

    /**
     * WeatherReportRepository constructor.
     *
     * @param \Doctrine\Persistence\ManagerRegistry $registry
     * @param \App\WeatherStationLogger $logger
     */
    public function __construct(ManagerRegistry $registry, WeatherStationLogger $logger) {
        parent::__construct($registry, WeatherReportEntity::class);
        $this->logger = $logger;

    }

    // /**
    //  * @return WeatherReport[] Returns an array of WeatherReport objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /**
     * Save Sensor record to the database.
     *
     * @param array $params Post data.
     * @return bool True is operation is successful, false otherwise.
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(array $params) {
        $em = $this->getEntityManager();
        $weatherEntity = new WeatherReportEntity();
        $weatherEntity->setLastSentCounter($params['counter']);
        $weatherEntity->setEmailBody($params['emailBody']);
        $weatherEntity->setLastSentDate(StationDateTime::dateNow('',false,'Y-m-d' ));
        $weatherEntity->setLastSentTime(StationDateTime::dateNow('',false,'H:i:s' ));
        $result = true;
        $em->getConnection()->beginTransaction();
        try {
            $em->persist($weatherEntity);
            $em->flush();
            // Try and commit the transaction
            $em->getConnection()->commit();
        } catch (ORMInvalidArgumentException | ORMException | ConnectionException $e) {
            $result = false;
            $this->logger->log('test', [], Logger::CRITICAL);
        }
        return $result;
    }
    /*
    public function findOneBySomeField($value): ?WeatherReport
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
