<?php

namespace App\Repository;

use App\Entity\RoomGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RoomGateway|null find($id, $lockMode = null, $lockVersion = null)
 * @method RoomGateway|null findOneBy(array $criteria, array $orderBy = null)
 * @method RoomGateway[]    findAll()
 * @method RoomGateway[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomGatewayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomGateway::class);
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
