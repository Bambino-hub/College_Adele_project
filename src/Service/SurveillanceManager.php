<?php

namespace App\Service;

use App\Entity\Surveillance;
use App\Entity\Teatchers;
use App\Repository\TeatchersRepository;
use Doctrine\ORM\EntityManagerInterface;

class SurveillanceManager
{
    public function __construct(
        private TeatchersRepository $teacherRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Modifie le surveillant d'une surveillance donnée
     */
    public function updateSurveillance(
        Surveillance $surveillance,
        Teatchers $newTeacher
    ): void {

        $exam = $surveillance->getExamen();

        // ======================================================
        //  Vérifier conflit horaire (autre examen même heure)
        // ======================================================
        if ($this->teacherRepository->isTeacherBusyDuringExam(
            $exam->getDate(),
            $exam->getHeursDebut(),
            $exam->getHeureFin(),
            $newTeacher->getId()
        )) {
            throw new \Exception("Cet enseignant est déjà occupé à cette heure.");
        }

        // ======================================================
        //  Vérifier qu'il ne surveille pas déjà
        // une autre classe du même examen
        // ======================================================
        foreach ($exam->getSurveillances() as $existing) {
            if (
                $existing->getEnseignant()->getId() === $newTeacher->getId()
                &&
                $existing->getId() !== $surveillance->getId()
            ) {
                throw new \Exception("Cet enseignant surveille déjà une autre classe pour cet examen.");
            }
        }

        // ======================================================
        //  Appliquer modification
        // ======================================================
        $surveillance->setEnseignant($newTeacher);

        $this->em->flush();
    }
}
