<?php
/**
 * @author Dani Stark(danistark1.ca@gmail.com).
 */
namespace App\Repository;

use App\Entity\SensorEntity;
use App\Utils\StationDateTime;
use App\WeatherStationLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Monolog\Logger;

/**
 * @method SensorEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method SensorEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method SensorEntity[]    findAll()
 * @method SensorEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SensorRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, SensorEntity::class);
    }


    /**
     * Find a record.
     *
     * @param array $params
     * @return array
     */
    public function findByQuery(array $params): array {
        $sensorData = parent::findBy($params);
        return $sensorData;
    }

    /**
     * Get a single record order by field name.
     *
     * @param string $room Room
     * @param string $field Field to order by.
     * @param string $order The order.
     * @return array
     */
    public function findOrdered(string $sensorName, $field = 'insert_date_time', $order = 'DESC'): array {
       $sensorData = parent::findBy(['room'=> $sensorName], [$field => $order],1);
       return $sensorData;
    }

    /**
     * Find a record.
     *
     * @param array $params
     * @return array
     */
    public function findByQueryOperation(array $params): array {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $field = $params['field'];
        $value = $params['value'];
        $operation = $params['operation'];

        $results  = $qb->select('p')
            ->from(SensorEntity::class, 'p')
            ->where('p.'.$field. $operation. ' :'.$field)
            ->setParameter($field, $value)
            ->getQuery()
            ->execute();

        return $results;
    }

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
        $roomGateway = new SensorEntity();
        $roomGateway->setRoom($params['room']);
        $roomGateway->setHumidity($params['humidity']);
        $roomGateway->setTemperature($params['temperature']);
        $roomGateway->setStationId($params['station_id']);
        $dt = StationDateTime::dateNow();
        $roomGateway->setInsertDateTime($dt);
        $result = true;
        try {
            $em->persist($roomGateway);
        } catch (ORMInvalidArgumentException | ORMException $e) {
            $result = false;
           //$this->logger->log('test', [], Logger::CRITICAL);
        }
        $em->flush();
        return $result;
    }

    /**
     * Delete a record.
     *
     * @param array $params
     * @throws ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(array $params) {

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        // By default we want to delete records that are older than 1 day.
        // Weather data is only needed for 24 hrs.
        $date = StationDateTime::dateNow('P'.$params['interval'].'D');
        $results  = $qb->select('p')
            ->from($params['tableName'], 'p')
            ->where('p.'.$params['dateTimeField']. '<= :date_from')
            ->setParameter('date_from', $date)
            ->getQuery()
            ->execute();

        if (!empty($results)) {
            foreach ($results as $result) {
                $em->remove($result);
                $em->flush();
            }
        }
    }

    // /**
    //  * @return RoomGateway[] Returns an array of RoomGateway objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    */

    /*
    public function findOneBySomeField($value): ?RoomGateway
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
