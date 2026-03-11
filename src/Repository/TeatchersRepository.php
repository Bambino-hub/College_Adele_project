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
            ->groupBy('t.id')
            ->orderBy('COUNT(s.id)', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
