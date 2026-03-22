<?php

namespace App\Repository;

use App\Entity\Surveillance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Surveillance>
 */
class SurveillanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Surveillance::class);
    }

    //    /**
    //     * @return Surveillance[] Returns an array of Surveillance objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Surveillance
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * cette permet de recupere les surveillants par examen, par classe selon le niveau de l
     * la classe, selon la date croissante 
     * 
     */
    public function findSurveillanceTableau(?int $cycleId = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.examen', 'e')
            ->join('s.classe', 'c')
            ->join('c.niveau', 'n')
            ->join('n.cycle', 'cycle')
            ->leftJoin('s.enseignant', 'ens')
            ->leftJoin('s.stagiaire', 'stagiaire')
            ->addSelect('e', 'c', 'n', 'cycle', 'ens', 'stagiaire')
            ->orderBy('e.date', 'ASC')
            ->addOrderBy('e.heursDebut', 'ASC')
            ->addOrderBy('e.heureFin', 'ASC')
            ->addOrderBy('n.name', 'ASC')
            ->addOrderBy('c.name', 'ASC');

        if ($cycleId !== null) {
            $qb->andWhere('cycle.id = :cycleId')
                ->setParameter('cycleId', $cycleId);
        }

        return $qb->getQuery()->getResult();
    }
}
