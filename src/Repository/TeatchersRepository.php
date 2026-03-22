<?php

namespace App\Repository;

use App\Entity\Teatchers;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Teatchers>
 */
class TeatchersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Teatchers::class);
    }

    //    /**
    //     * @return Teatchers[] Returns an array of Teatchers objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Teatchers
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    // Vérifie si un enseignant est déjà occupé à surveiller un examen à une date et heure données
    public function isTeacherBusyDuringExam(
        \DateTimeInterface $date,
        \DateTimeInterface $heureDebut,
        \DateTimeInterface $heureFin,
        int $teacherId
    ): bool {
        return (bool) $this->createQueryBuilder('t')
            ->select('COUNT(s.id)')
            ->join('t.surveillances', 's')
            ->join('s.examen', 'e')
            ->where('t.id = :teacher')
            ->andWhere('e.date = :date')
            ->andWhere('e.heursDebut < :heureFin')
            ->andWhere('e.heureFin > :heursDebut')
            ->setParameter('teacher', $teacherId)
            ->setParameter('date', $date)
            ->setParameter('heursDebut', $heureDebut)
            ->setParameter('heureFin', $heureFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Récupère les enseignants triés par nombre total de surveillances (ascendant)
    public function findTeachersOrderedByGlobalSurveillanceCount(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.surveillances', 's')
            ->where('t.canSupervise = true')
            ->groupBy('t.id')
            ->orderBy('COUNT(s.id)', 'ASC')
            ->addOrderBy('t.lastname', 'ASC')
            ->addOrderBy('t.firstname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTeachersEligibleForCycleOrderedByGlobalSurveillanceCount(int $cycleId): array
    {
        $cycleCode = $cycleId === 1 ? Teatchers::PDF_CYCLE_1 : Teatchers::PDF_CYCLE_2;

        return $this->findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount($cycleCode);
    }

    public function findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount(string $cycleCode): array
    {
        if (!in_array($cycleCode, [Teatchers::PDF_CYCLE_1, Teatchers::PDF_CYCLE_2], true)) {
            $cycleCode = Teatchers::PDF_CYCLE_1;
        }

        return $this->createQueryBuilder('t')
            ->leftJoin('t.surveillances', 's')
            ->where('t.canSupervise = true')
            ->andWhere('t.pdfCycle = :cycleCode OR t.pdfCycle = :bothCycles')
            ->groupBy('t.id')
            ->orderBy('COUNT(DISTINCT s.id)', 'ASC')
            ->addOrderBy('t.lastname', 'ASC')
            ->addOrderBy('t.firstname', 'ASC')
            ->setParameter('cycleCode', $cycleCode)
            ->setParameter('bothCycles', Teatchers::PDF_CYCLE_BOTH)
            ->getQuery()
            ->getResult();
    }
}
