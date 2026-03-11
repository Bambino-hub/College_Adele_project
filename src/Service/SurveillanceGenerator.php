<?php

namespace App\Service;

use App\Entity\Surveillance;
use App\Repository\TeatchersRepository;
use Doctrine\ORM\EntityManagerInterface;

class SurveillanceGenerator
{
    public function __construct(
        private TeatchersRepository $teacherRepository,
        private EntityManagerInterface $em
    ) {}

    /**
     * Génère automatiquement le tableau de surveillance pour un examen donné.
     *
     * Logique :
     * - Rotation globale équitable (basée sur l'historique total des surveillances)
     * - Aucun enseignant ne peut surveiller deux salles du même examen
     * - Aucun enseignant ne peut surveiller deux examens qui se chevauchent
     * - Suppression automatique de l'ancien tableau si régénération
     */
    public function generate(array $examens): void
    {
        // ==========================================================
        //  Suppression des anciennes surveillances (régénération)
        // ==========================================================
        foreach ($examens as $examen) {
            foreach ($examen->getSurveillances() as $surveillance) {
                $this->em->remove($surveillance);
            }
        }
        $this->em->flush(); // Important pour nettoyer avant nouvelle génération


        // Tableau pour éviter qu’un enseignant surveille
        // plusieurs classes dans le même examen
        $usedTeacherIds = [];

        // ==========================================================
        //  Récupération des données nécessaires
        // ==========================================================
        foreach ($examens as $examen) {
            $numberPerClass = $examen->getNombreSurveillantsParClasse(); // Nombre de surveillants par classe

            // ==========================================================
            //  Rotation globale équitable
            //    On récupère les enseignants triés par nombre total
            //    de surveillances (ASC → les moins chargés en premier)
            // ==========================================================
            $teachers = $this->teacherRepository->findTeachersOrderedByGlobalSurveillanceCount();

            // On mélange pour garder un aspect aléatoire
            shuffle($teachers);

            $classes = $examen->getClasse()->toArray(); // Classes concernées par l'examen
            foreach ($classes as $classe) {

                $assigned = 0; // Nombre de surveillants déjà affectés pour cette classe

                foreach ($teachers as $key => $teacher) {

                    // Si on a déjà assez de surveillants pour cette classe → stop
                    if ($assigned >= $numberPerClass) {
                        break;
                    }

                    // ------------------------------------------------------
                    // Vérifications importantes :
                    // 1. L'enseignant n'est pas déjà utilisé dans cet examen
                    // 2. L'enseignant n'a pas un autre examen au même moment
                    // ------------------------------------------------------
                    if (
                        !\in_array($teacher->getId(), $usedTeacherIds)
                        &&
                        !$this->teacherRepository->isTeacherBusyDuringExam(
                            $examen->getDate(),
                            $examen->getHeursDebut(),
                            $examen->getHeureFin(),
                            $teacher->getId()
                        )
                    ) {

                        // Création de la surveillance
                        $surveillance = new Surveillance();
                        $surveillance->setExamen($examen);
                        $surveillance->setClasse($classe);
                        $surveillance->setEnseignant($teacher);

                        // Sauvegarde en mémoire
                        $this->em->persist($surveillance);

                        // On marque cet enseignant comme utilisé
                        $usedTeacherIds[] = $teacher->getId();

                        // On le retire du tableau pour éviter réutilisation immédiate
                        unset($teachers[$key]);

                        $assigned++;
                    }
                }

                // Si on n’a pas pu affecter assez de surveillants
                if ($assigned < $numberPerClass) {
                    throw new \Exception("Pas assez d'enseignants disponibles pour la classe.");
                }
            }

            // ==========================================================
            //  Enregistrement final en base de données
            // ==========================================================
            $this->em->flush();
        }
    }
}
