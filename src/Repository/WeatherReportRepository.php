<?php

namespace App\Repository;

use App\Entity\WeatherReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WeatherReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method WeatherReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method WeatherReport[]    findAll()
 * @method WeatherReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WeatherReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeatherReport::class);
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
