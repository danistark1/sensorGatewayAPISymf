<?php

namespace App\Repository;

use App\Entity\SensorEntity;
use App\Entity\SensorReportEntity;
use App\Utils\SensorDateTime;
use App\Logger\SensorGatewayLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use Monolog\Logger;

/**
 * @method SensorReportEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method SensorReportEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method SensorReportEntity[]    findAll()
 * @method SensorReportEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SensorReportRepository extends ServiceEntityRepository {

    /** @var SensorGatewayLogger  */
    private $logger;

    /**
     * WeatherReportRepository constructor.
     *
     * @param \Doctrine\Persistence\ManagerRegistry $registry
     * @param SensorGatewayLogger $logger
     */
    public function __construct(ManagerRegistry $registry, SensorGatewayLogger $logger) {
        parent::__construct($registry, SensorReportEntity::class);
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
        $weatherEntity = new SensorReportEntity();
        $weatherEntity->setLastSentCounter($params['counter']);
        $weatherEntity->setEmailBody($params['emailBody']);
        $weatherEntity->setReportType($params['reportType']);
        $weatherEntity->setLastSentDate(SensorDateTime::dateNow('',false,'Y-m-d' ));
        $weatherEntity->setLastSentTime(SensorDateTime::dateNow('',false,'H:i:s' ));
        $result = true;
        $em->getConnection()->beginTransaction();

        try {
            $em->persist($weatherEntity);
            $em->flush();
            // Try and commit the transaction
            $em->getConnection()->commit();
        } catch (ORMInvalidArgumentException | ORMException | ConnectionException | MappingException $e) {
            $result = false;
            $this->logger->log('test', [], Logger::CRITICAL);
        }

        return $result;
    }

    public function delete($type) {
        $em = $this->getEntityManager();


        $entity = $em->getRepository(SensorReportEntity::class)->findOneBy(['reportType' => $type]);
        $em->remove($entity);
        $em->flush();
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
