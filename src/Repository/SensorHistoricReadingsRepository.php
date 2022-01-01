<?php

namespace App\Repository;

use App\Entity\SensorHistoricReadings;
use App\Utils\SensorDateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SensorHistoricReadings|null find($id, $lockMode = null, $lockVersion = null)
 * @method SensorHistoricReadings|null findOneBy(array $criteria, array $orderBy = null)
 * @method SensorHistoricReadings[]    findAll()
 * @method SensorHistoricReadings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SensorHistoricReadingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SensorHistoricReadings::class);
    }


    /**
     * Find a record.
     *
     * @param array $params
     * @return array
     */
    public function findByQuery(array $params): array {
        $sensorData = parent::findBy($params,[], 20);
        return $sensorData;
    }

    /**
     * Update a recipe.
     *
     * @param $historicReadingsEntity
     * @return mixed
     */
    public function updateHistoricReadings($historicReadingsEntity) {
        $em = $this->getEntityManager();
        try {
            $em->persist($historicReadingsEntity);
            $em->flush();
        } catch (ORMInvalidArgumentException | ORMException $e) {
        }

        return $historicReadingsEntity;
    }

    /**
     * Save historic readings.
     *
     * @param array $params
     * @return int|null
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function save(array $params) {
        $em = $this->getEntityManager();
        $sensorHistoricReadings = new SensorHistoricReadings();
        $sensorHistoricReadings->setLowestReading($params['lowest_reading']);
        $sensorHistoricReadings->setHighestReading($params['highest_reading']);
        $sensorHistoricReadings->setName($params['name']);
        $sensorHistoricReadings->setType($params['type']);

        $dt = SensorDateTime::dateNow('', false);
        $sensorHistoricReadings->setInsertDateLowest($dt);
        $sensorHistoricReadings->setInsertDateHighest($dt);
        $em->getConnection()->beginTransaction();
        try {
            $em->persist($sensorHistoricReadings);
            $em->flush();
            $em->getConnection()->commit();

        } catch (ORMInvalidArgumentException | ORMException $e) {
        }
        $id = $sensorHistoricReadings->getId();
        return $id;
    }

    // /**
    //  * @return SensorHistoricReadings[] Returns an array of SensorHistoricReadings objects
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
    public function findOneBySomeField($value): ?SensorHistoricReadings
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
