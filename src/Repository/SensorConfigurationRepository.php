<?php

namespace App\Repository;

use App\Entity\SensorConfigurationEntity;
use App\Utils\SensorDateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @method SensorConfigurationEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method SensorConfigurationEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method SensorConfigurationEntity[]    findAll()
 * @method SensorConfigurationEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SensorConfigurationRepository extends ServiceEntityRepository {
    /** @var FilesystemAdapter  */
    private $cache;

    /**
     *
     */
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, SensorConfigurationEntity::class);
    }

    /**
     * Save Config record to the database.
     *
     * @param array $params Post data.
     * @return bool True is operation is successful, false otherwise.
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(array $params) {
        $em = $this->getEntityManager();
        $weatherConfigEnt = new SensorConfigurationEntity();
        $weatherConfigEnt->setConfigKey($params['config_key']);
        $weatherConfigEnt->setConfigValue($params['config_value']);
        $weatherConfigEnt->setConfigType($params['config_type']);

        $dt = SensorDateTime::dateNow();
        $weatherConfigEnt->setConfigDate($dt);

        $result = true;
        try {
            $em->persist($weatherConfigEnt);
        } catch (ORMInvalidArgumentException | ORMException $e) {
            $result = false;
            //$this->logger->log('test', [], Logger::CRITICAL);
        }
        $em->flush();
        return $result;
    }

    /**
     * @return $response
     */
    public function update($key, $value) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $dt = SensorDateTime::dateNow('', true);
        try {
        $q = $qb->update(SensorConfigurationEntity::class, 'c')
        ->set('c.configValue', $qb->expr()->literal($value))
        ->set('c.configDate', $qb->expr()->literal($dt))
        ->where('c.configKey = ?1')
        ->setParameter(1, $key)
        ->getQuery();
        $q->execute();
            $response = true;
        } catch (\Exception $e) {
            return 'An Error occured during save: ' .$e->getMessage();
        }
        return $response;
    }

    /**
     * Find a record.
     *
     * @param string $key
     * @return mixed
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getConfigValue(string $key) {
//        //$this->cache->delete('cache_'.$key);
//        $value = $this->cache->get('cache_'.$key, function (ItemInterface $item) use ($key) {
            // cache expires in 41 days.
            $dbValue = parent::findBy(['configKey' => $key],[], 1);
//            if (!empty($dbValue)) {
//                $item->expiresAfter(3600000);
//            }
//            return $dbValue;
//        });
        $dbValue = parent::findBy(['configKey' => $key],[], 1);
        return $dbValue;
        //return isset($value[0]) ? $value[0]->getConfigValue() : [];
    }

    // /**
    //  * @return WeatherConfiguration[] Returns an array of WeatherConfiguration objects
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

    /*
    public function findOneBySomeField($value): ?WeatherConfiguration
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
