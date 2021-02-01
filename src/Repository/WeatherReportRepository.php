<?php

namespace App\Repository;

use App\Entity\SensorEntity;
use App\Entity\WeatherReportEntity;
use App\Utils\StationDateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WeatherReportEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method WeatherReportEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method WeatherReportEntity[]    findAll()
 * @method WeatherReportEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WeatherReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeatherReportEntity::class);
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
        try {
            $em->persist($weatherEntity);
        } catch (ORMInvalidArgumentException | ORMException $e) {
            $result = false;
            //$this->logger->log('test', [], Logger::CRITICAL);
        }
        $em->flush();
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
