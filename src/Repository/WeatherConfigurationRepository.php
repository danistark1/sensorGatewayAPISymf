<?php

namespace App\Repository;

use App\Entity\WeatherConfigurationEntity;
use App\Utils\StationDateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @method WeatherConfigurationEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method WeatherConfigurationEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method WeatherConfigurationEntity[]    findAll()
 * @method WeatherConfigurationEntity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WeatherConfigurationRepository extends ServiceEntityRepository {
    /** @var FilesystemAdapter  */
    private $cache;

    /**
     *
     */
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, WeatherConfigurationEntity::class);
        //$this->cache = new FilesystemAdapter();
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
        $weatherConfigEnt = new WeatherConfigurationEntity();
        $weatherConfigEnt->setConfigKey($params['config_key']);
        $weatherConfigEnt->setConfigValue($params['config_value']);
        $weatherConfigEnt->setConfigType($params['config_type']);

        $dt = StationDateTime::dateNow();
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
        try {
        $q = $qb->update(WeatherConfigurationEntity::class, 'c')
        ->set('c.configValue', $qb->expr()->literal($value))
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
