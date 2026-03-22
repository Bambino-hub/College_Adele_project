<?php

namespace App\Repository;

use App\Entity\Examen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Examen>
 */
class ExamenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Examen::class);
    }

    //    /**
    //     * @return Examen[] Returns an array of Examen objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Examen
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByCycle(int $cycleId): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.classe', 'c')
            ->join('c.niveau', 'n')
            ->join('n.cycle', 'cycle')
            ->addSelect('c', 'n', 'cycle')
            ->andWhere('cycle.id = :cycleId')
            ->setParameter('cycleId', $cycleId)
            ->orderBy('e.date', 'ASC')
            ->addOrderBy('e.heursDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
