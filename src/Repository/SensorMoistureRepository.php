<?php

namespace App\Repository;

use App\Entity\SensorMoistureEntity;
use App\Utils\SensorDateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Monolog\Logger;

/**
 * @method SensorMoistureEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method SensorMoistureEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method SensorMoistureEntity[]    findAll()
 * @method SensorMoistureEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SensorMoistureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SensorMoistureEntity::class);
    }

    /**
     * Moisture Sensor pruning.
     *
     * @param array $params
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(array $params) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        // By default we want to delete records that are older than 1 day.
        $date = SensorDateTime::dateNow('P'.$params['interval'].'D');
        $results  = $qb->select('p')
            ->from(SensorMoistureEntity::class, 'p')
            ->where('p.'.$params['dateTimeField']. '<= :insertDateTime')
            ->setParameter('insertDateTime', $date)
            ->getQuery()
            ->execute();

        if (!empty($results)) {
            foreach ($results as $result) {
                $em->remove($result);
                $em->flush();
            }
        }
    }

    /**
     * Save Sensor record to the database.
     *
     * @param array $params Post data.
     * @return bool True is operation is successful, false otherwise.
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function save(array $params) {
        $sensorGateway = new SensorMoistureEntity();
        $sensorGateway->setName($params['name']);
        $sensorGateway->setSensorID($params['sensorid']);
        $sensorGateway->setSensorLocation($params['sensorlocation']);
        $batteryStatus = $params['batterystatus'] ?? null;
        $sensorGateway->setBatteryStatus($batteryStatus);
        $sensorGateway->setSensorReading($params['sensorreading']);

        $dt = SensorDateTime::dateNow();
        $sensorGateway->setInsertDateTime($dt);
        $result = true;
        // Get the entity manager, begin a transaction.
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->persist($sensorGateway);
            $em->flush();
            // Try and commit the transaction
            $em->getConnection()->commit();
        } catch (ORMInvalidArgumentException | ORMException | ConnectionException $e) {
            $result = false;
            //$this->logger->log('Record save failed.', ['function' => __CLASS__.__FUNCTION__], Logger::CRITICAL);
        }
        return $result;
    }

    // /**
    //  * @return SensorMoisture[] Returns an array of SensorMoisture objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SensorMoisture
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
