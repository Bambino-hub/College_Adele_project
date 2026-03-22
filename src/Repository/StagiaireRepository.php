<?php

namespace App\Repository;

use App\Entity\Stagiaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stagiaire>
 */
class StagiaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stagiaire::class);
    }

    public function findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount(int $cycleId): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.surveillances', 'surveillance')
            ->groupBy('s.id')
            ->orderBy('COUNT(DISTINCT surveillance.id)', 'ASC')
            ->addOrderBy('s.lastname', 'ASC')
            ->addOrderBy('s.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function isTraineeBusyDuringExam(
        \DateTimeInterface $date,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        int $stagiaireId
    ): bool {
        return (bool) $this->createQueryBuilder('s')
            ->select('COUNT(surveillance.id)')
            ->join('s.surveillances', 'surveillance')
            ->join('surveillance.examen', 'e')
            ->where('s.id = :stagiaire')
            ->andWhere('e.date = :date')
            ->andWhere('e.heursDebut < :heureFin')
            ->andWhere('e.heureFin > :heursDebut')
            ->setParameter('stagiaire', $stagiaireId)
            ->setParameter('date', $date)
            ->setParameter('heursDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
