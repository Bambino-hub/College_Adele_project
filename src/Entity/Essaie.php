<?php

// namespace App\Service;

// use App\Entity\Examen;
// use App\Entity\Surveillance;
// use App\Entity\Teatchers;
// use App\Repository\TeatchersRepository;
// use Doctrine\ORM\EntityManagerInterface;

// class SurveillanceGenerator
// class Essaie
// {
    // public function __construct(
        // private TeatchersRepository $teacherRepository,
        // private EntityManagerInterface $em
    // ) {}

/**
 * Génère automatiquement le tableau de surveillance pour un examen donné.
 *
 * Logique :
 * - Rotation globale équitable (basée sur l'historique total des surveillances)
 * - Aucun enseignant ne peut surveiller deux salles du même examen
 * - Aucun enseignant ne peut surveiller deux examens qui se chevauchent
 * - Suppression automatique de l'ancien tableau si régénération
 */
    // public function generate(Examen $examen): void
    // {
        // ==========================================================
        //  Suppression des anciennes surveillances (régénération)
        // ==========================================================
        // foreach ($examen->getSurveillances() as $surveillance) {
            // $this->em->remove($surveillance);
        // }
        // $this->em->flush(); // Important pour nettoyer avant nouvelle génération


        // ==========================================================
        //  Récupération des données nécessaires
        // ==========================================================
        // $classes = $examen->getClasse(); // Classes concernées par l'examen
        // $numberPerClass = $examen->getNombreSurveillantsParClasse(); // Nombre de surveillants par classe


        // ==========================================================
        //  Rotation globale équitable
        //    On récupère les enseignants triés par nombre total
        //    de surveillances (ASC → les moins chargés en premier)
        // ==========================================================
        // $teachers = $this->teacherRepository->findTeachersOrderedByGlobalSurveillanceCount();

        // On mélange pour garder un aspect aléatoire
        // shuffle($teachers);


        // Tableau pour éviter qu’un enseignant surveille
        // plusieurs classes dans le même examen
        // $usedTeacherIds = [];


        // ==========================================================
        //  Attribution des surveillants pour chaque classe
        // ==========================================================
        // foreach ($classes as $class) {
// 
            // $assigned = 0; // Nombre de surveillants déjà affectés pour cette classe

            // foreach ($teachers as $key => $teacher) {

                // Si on a déjà assez de surveillants pour cette classe → stop
                // if ($assigned >= $numberPerClass) {
                    // break;
                // }
// 
                // ------------------------------------------------------
                // Vérifications importantes :
                // 1. L'enseignant n'est pas déjà utilisé dans cet examen
                // 2. L'enseignant n'a pas un autre examen au même moment
                // ------------------------------------------------------
                // if (
                    // !in_array($teacher->getId(), $usedTeacherIds)
                    // &&
                    // !$this->teacherRepository->isTeacherBusyDuringExam(
                        // $examen->getDate(),
                        // $examen->getHeursDebut(),
                        // $examen->getHeureFin(),
                        // $teacher->getId()
                    // )
                // ) {
// 
                    // Création de la surveillance
                    // $surveillance = new Surveillance();
                    // $surveillance->setExamen($examen);
                    // $surveillance->setClasse($class);
                    // $surveillance->setEnseignant($teacher);

                    // Sauvegarde en mémoire
                    // $this->em->persist($surveillance);

                    // On marque cet enseignant comme utilisé
                    // $usedTeacherIds[] = $teacher->getId();

                    // On le retire du tableau pour éviter réutilisation immédiate
                    // unset($teachers[$key]);

                    // $assigned++;
                // }
            // }

            // Si on n’a pas pu affecter assez de surveillants
            // if ($assigned < $numberPerClass) {
                // throw new \Exception("Pas assez d'enseignants disponibles pour la classe.");
            // }
        // }

        // ==========================================================
        //  Enregistrement final en base de données
        // ==========================================================
        // $this->em->flush();
    // }

    // public function updateSurveillance(
        // Surveillance $surveillance,
        // Teatchers $newTeacher
    // ): void {

        // $exam = $surveillance->getExamen();

        //  Vérifier conflit horaire
        // if ($this->teacherRepository->isTeacherBusyDuringExam(
            // $exam->getDate(),
            // $exam->getHeursDebut(),
            // $exam->getHeureFin(),
            // $newTeacher->getId()
        // )) {
            // throw new \Exception("Cet enseignant est déjà occupé à cette heure.");
        // }

        //  Vérifier qu’il ne surveille pas déjà une autre classe
        // foreach ($exam->getSurveillances() as $existing) {
            // if (
                // $existing->getEnseignant()->getId() === $newTeacher->getId()
                // &&
                // $existing->getId() !== $surveillance->getId()
            // ) {
                // throw new \Exception("Cet enseignant surveille déjà une autre classe pour cet examen.");
            // }
        // }

        //  Appliquer modification
        // $surveillance->setEnseignant($newTeacher);

        // $this->em->flush();
    // }


// exemple d'utilisation dans un controller
// Génération automatique
// $generator->genererSurveillances($examen);

// Modification manuelle d’un surveillant
// $generator->updateSurveillance($surveillance, $nouvelEnseignant);


// public function findSurveillanceTableau()
// {
    // return $this->createQueryBuilder('s')
        // ->join('s.examen', 'e')
        // ->join('e.classe', 'c')
        // ->join('c.niveau', 'n')
        // ->join('s.enseignant', 'ens')
        // ->addSelect('e', 'c', 'n', 'ens')
        // ->orderBy('e.date', 'ASC')
        // ->getQuery()
        // ->getResult();
// }


// $surveillances = $surveillanceRepository->findSurveillanceTableau();

// $tableau = [];

// foreach ($surveillances as $s) {

    // $date = $s->getExamen()->getDate()->format('Y-m-d');
    // $niveau = $s->getExamen()->getClasse()->getNiveau()->getNom();

    // $tableau[$date][$niveau]['matiere'] =
        // $s->getExamen()->getMatiere()->getNom();

    // $tableau[$date][$niveau]['surveillants'][] =
        // $s->getEnseignant()->getNom();
// }


// <table class="table table-bordered">
// <thead>
// <tr>
//     <th>Date</th>
//     <th>6eme</th>
//     <th>5eme</th>
//     <th>4eme</th>
// </tr>
// </thead>

// <tbody>

// {% for date, niveaux in tableau %}
// <tr>

// <td>{{ date }}</td>

// <td>
// {% if niveaux['6eme'] is defined %}
// <strong>{{ niveaux['6eme'].matiere }}</strong><br>

// {% for s in niveaux['6eme'].surveillants %}
// {{ s }}<br>
// {% endfor %}
// {% endif %}
// </td>

// </tr>
// {% endfor %}

// </tbody>
// </table>
// }
