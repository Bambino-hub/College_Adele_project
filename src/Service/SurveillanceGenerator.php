<?php

namespace App\Service;

use App\Entity\ClassName;
use App\Entity\Cycles;
use App\Entity\Examen;
use App\Entity\Matter;
use App\Entity\Niveau;
use App\Entity\Stagiaire;
use App\Entity\Surveillance;
use App\Entity\Teatchers;
use App\Repository\StagiaireRepository;
use App\Repository\TeatchersRepository;
use Doctrine\ORM\EntityManagerInterface;

class SurveillanceGenerator
{
    public function __construct(
        private TeatchersRepository $teacherRepository,
        private StagiaireRepository $stagiaireRepository,
        private EntityManagerInterface $em,
        private array $literaryDomainTokens = ['fr', 'francais', 'philo', 'histoire', 'geo', 'h-g', 'hg', 'ang', 'anglais', 'all', 'esp', 'ecm'],
        private array $scientificDomainTokens = ['math', 'svt', 'pc', 'pct', 'physique', 'chimie', 'science', 'biologie']
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
        $examens = array_values(array_filter($examens, static fn($examen): bool => $examen instanceof Examen));

        if ($examens === []) {
            return;
        }

        // ==========================================================
        //  Suppression des anciennes surveillances (régénération)
        // ==========================================================
        foreach ($examens as $examen) {
            foreach ($examen->getSurveillances() as $surveillance) {
                $this->em->remove($surveillance);
            }
        }
        $this->em->flush(); // Important pour nettoyer avant nouvelle génération

        $examens = $this->sortExamensChronologically($examens);
        $slotIndexes = $this->buildSlotIndexes($examens);
        $generatedAssignments = [];
        $generatedLoadCounts = [];
        $generatedTypeCounts = [
            'teacher' => 0,
            'trainee' => 0,
        ];

        // ==========================================================
        //  Récupération des données nécessaires
        // ==========================================================
        foreach ($examens as $examen) {
            $numberPerClass = $examen->getNombreSurveillantsParClasse(); // Nombre de surveillants par classe
            $classes = $examen->getClasse()->toArray(); // Classes concernées par l'examen
            $cycle = $this->resolveExamCycle($examen);

            if ($cycle === null) {
                throw new \RuntimeException("Toutes les classes de l'examen doivent être rattachées à un cycle.");
            }

            if ($classes === []) {
                throw new \RuntimeException("Impossible de générer une surveillance pour un examen sans classe.");
            }

            $classes = $this->sortClassesByDescendingLevel($classes);

            $slotIndex = $slotIndexes[$this->buildSlotKey($examen)];
            $pairedSupervisorsByClass = [];
            $classNamesInExam = array_map(
                static fn(ClassName $class): string => trim((string) $class->getName()),
                $classes
            );

            // Tableau pour éviter qu’un enseignant surveille
            // plusieurs classes dans le même examen
            $usedSupervisorKeys = [];

            // ==========================================================
            //  Rotation globale équitable
            //    On récupère séparément les enseignants et les stagiaires
            // ==========================================================
            $teachers = $this->teacherRepository->findTeachersEligibleForPdfCycleOrderedByGlobalSurveillanceCount(
                $this->resolveTeacherPdfCycleCode($cycle)
            );
            $trainees = $this->stagiaireRepository->findStagiairesEligibleForCycleOrderedByGlobalSurveillanceCount($cycle->getId());

            if ($teachers === [] && $trainees === []) {
                throw new \RuntimeException(sprintf(
                    'Aucun enseignant ou stagiaire rattaché au cycle %s n\'est disponible pour la surveillance.',
                    $cycle->getName()
                ));
            }

            $matterTeachers = [];
            $domainTeachers = [];
            $matterTrainees = [];
            $domainTrainees = [];
            $examMatter = $examen->getMatiere();
            $examDomain = $this->resolveDomainFromMatter($examMatter);

            if ($examMatter !== null) {
                $matterTeachers = array_values(array_filter(
                    $teachers,
                    static fn(Teatchers $teacher): bool => $teacher->teachesMatterInCycle($examMatter, $cycle)
                ));

                $matterTrainees = array_values(array_filter(
                    $trainees,
                    static fn(Stagiaire $trainee): bool => $trainee->getMatiereDeStage()?->getId() === $examMatter->getId()
                ));
            }

            if ($examDomain !== null) {
                $domainTeachers = array_values(array_filter(
                    $teachers,
                    fn(Teatchers $teacher): bool => $this->isTeacherInDomainForCycle($teacher, $examDomain, $cycle)
                ));

                $domainTrainees = array_values(array_filter(
                    $trainees,
                    fn(Stagiaire $trainee): bool => $this->isTraineeInDomain($trainee, $examDomain)
                ));
            }

            foreach ($classes as $classe) {

                $requiredSupervisorsForClass = $numberPerClass;
                if ($this->isCombinedClassHandledAsSingleSupervisor($classe, $classNamesInExam)) {
                    $requiredSupervisorsForClass = 1;
                }

                $assigned = 0; // Nombre de surveillants déjà affectés pour cette classe

                // ── Priorité 0 : candidat pairé (règle spéciale Tle C ↔ Tle D/D2) ──────
                $pairedCandidate = $this->resolvePairedCandidateForClass($classe, $pairedSupervisorsByClass);

                if ($pairedCandidate !== null) {
                    $this->assignCandidate(
                        $examen,
                        $classe,
                        $pairedCandidate,
                        $slotIndex,
                        $usedSupervisorKeys,
                        $generatedAssignments,
                        $generatedLoadCounts,
                        $generatedTypeCounts
                    );
                    $assigned++;
                }

                // ── Priorité 1 : enseignant de la matière au MEME NIVEAU ───────────────
                // L'enseignant qui enseigne réellement cette matière au niveau de la
                // classe examinée (ex: 3eme) est prioritaire.
                if ($assigned < $requiredSupervisorsForClass && $examMatter !== null) {
                    $tier1Teachers = array_values(array_filter(
                        $matterTeachers,
                        fn(Teatchers $t): bool => $t->teachesMatterInNiveau($examMatter, $classe->getNiveau())
                    ));

                    $tier1Teacher = $this->findAvailableTeacher(
                        $tier1Teachers,
                        $examen,
                        $usedSupervisorKeys,
                        $slotIndex,
                        $generatedAssignments,
                        $generatedLoadCounts
                    );

                    if ($tier1Teacher !== null) {
                        $this->assignTeacher(
                            $examen,
                            $classe,
                            $tier1Teacher,
                            $slotIndex,
                            $usedSupervisorKeys,
                            $generatedAssignments,
                            $generatedLoadCounts,
                            $generatedTypeCounts
                        );
                        $this->registerPairedCandidateForCounterpart(
                            $classe,
                            ['type' => 'teacher', 'person' => $tier1Teacher],
                            $pairedSupervisorsByClass
                        );
                        $assigned++;
                    }
                }

                // ── Priorité 2 : enseignant de la même matière (autre classe du cycle) ──
                // Parmi les enseignants de la matière évaluée, on cherche le moins chargé
                // disponible, même s'il n'enseigne pas précisément dans cette classe.
                if ($assigned < $requiredSupervisorsForClass && $examMatter !== null) {
                    $tier2Teacher = $this->findAvailableTeacher(
                        $matterTeachers,
                        $examen,
                        $usedSupervisorKeys,
                        $slotIndex,
                        $generatedAssignments,
                        $generatedLoadCounts
                    );

                    if ($tier2Teacher !== null) {
                        $this->assignTeacher(
                            $examen,
                            $classe,
                            $tier2Teacher,
                            $slotIndex,
                            $usedSupervisorKeys,
                            $generatedAssignments,
                            $generatedLoadCounts,
                            $generatedTypeCounts
                        );
                        $this->registerPairedCandidateForCounterpart(
                            $classe,
                            ['type' => 'teacher', 'person' => $tier2Teacher],
                            $pairedSupervisorsByClass
                        );
                        $assigned++;
                    }
                }

                // ── Priorité 3 : enseignant du même domaine (littéraire/scientifique)
                // d'abord au même niveau, puis dans le cycle.
                if ($assigned < $requiredSupervisorsForClass && $examDomain !== null) {
                    $tier3TeachersSameLevel = array_values(array_filter(
                        $domainTeachers,
                        fn(Teatchers $t): bool => $this->teachesDomainInNiveau($t, $examDomain, $classe->getNiveau(), $cycle)
                    ));

                    $tier3TeacherSameLevel = $this->findAvailableTeacher(
                        $tier3TeachersSameLevel,
                        $examen,
                        $usedSupervisorKeys,
                        $slotIndex,
                        $generatedAssignments,
                        $generatedLoadCounts
                    );

                    if ($tier3TeacherSameLevel !== null) {
                        $this->assignTeacher(
                            $examen,
                            $classe,
                            $tier3TeacherSameLevel,
                            $slotIndex,
                            $usedSupervisorKeys,
                            $generatedAssignments,
                            $generatedLoadCounts,
                            $generatedTypeCounts
                        );
                        $this->registerPairedCandidateForCounterpart(
                            $classe,
                            ['type' => 'teacher', 'person' => $tier3TeacherSameLevel],
                            $pairedSupervisorsByClass
                        );
                        $assigned++;
                    }
                }

                if ($assigned < $requiredSupervisorsForClass && $examDomain !== null) {
                    $tier4Teacher = $this->findAvailableTeacher(
                        $domainTeachers,
                        $examen,
                        $usedSupervisorKeys,
                        $slotIndex,
                        $generatedAssignments,
                        $generatedLoadCounts
                    );

                    if ($tier4Teacher !== null) {
                        $this->assignTeacher(
                            $examen,
                            $classe,
                            $tier4Teacher,
                            $slotIndex,
                            $usedSupervisorKeys,
                            $generatedAssignments,
                            $generatedLoadCounts,
                            $generatedTypeCounts
                        );
                        $this->registerPairedCandidateForCounterpart(
                            $classe,
                            ['type' => 'teacher', 'person' => $tier4Teacher],
                            $pairedSupervisorsByClass
                        );
                        $assigned++;
                    }
                }

                if ($assigned < $requiredSupervisorsForClass && $examMatter !== null) {
                    $matterTraineeCandidate = $this->findAvailableCandidateFromList(
                        'trainee',
                        $matterTrainees,
                        $examen,
                        $usedSupervisorKeys,
                        $slotIndex,
                        $generatedAssignments,
                        $generatedLoadCounts
                    );

                    if ($matterTraineeCandidate !== null) {
                        $this->assignCandidate(
                            $examen,
                            $classe,
                            $matterTraineeCandidate,
                            $slotIndex,
                            $usedSupervisorKeys,
                            $generatedAssignments,
                            $generatedLoadCounts,
                            $generatedTypeCounts
                        );
                        $this->registerPairedCandidateForCounterpart($classe, $matterTraineeCandidate, $pairedSupervisorsByClass);
                        $assigned++;
                    }
                }

                if ($assigned < $requiredSupervisorsForClass && $examDomain !== null) {
                    $domainTraineeCandidate = $this->findAvailableCandidateFromList(
                        'trainee',
                        $domainTrainees,
                        $examen,
                        $usedSupervisorKeys,
                        $slotIndex,
                        $generatedAssignments,
                        $generatedLoadCounts
                    );

                    if ($domainTraineeCandidate !== null) {
                        $this->assignCandidate(
                            $examen,
                            $classe,
                            $domainTraineeCandidate,
                            $slotIndex,
                            $usedSupervisorKeys,
                            $generatedAssignments,
                            $generatedLoadCounts,
                            $generatedTypeCounts
                        );
                        $this->registerPairedCandidateForCounterpart($classe, $domainTraineeCandidate, $pairedSupervisorsByClass);
                        $assigned++;
                    }
                }

                // ── Priorité finale : pool général (rotation équitable enseignants + stagiaires)
                while ($assigned < $requiredSupervisorsForClass) {
                    $candidate = $this->findAvailableCandidate(
                        $teachers,
                        $trainees,
                        $examen,
                        $usedSupervisorKeys,
                        $slotIndex,
                        $generatedAssignments,
                        $generatedLoadCounts,
                        $generatedTypeCounts
                    );

                    if ($candidate === null) {
                        throw new \RuntimeException(sprintf(
                            'Pas assez de surveillants disponibles pour la classe %s (%s).',
                            $classe->getName(),
                            $cycle->getName()
                        ));
                    }

                    $this->assignCandidate(
                        $examen,
                        $classe,
                        $candidate,
                        $slotIndex,
                        $usedSupervisorKeys,
                        $generatedAssignments,
                        $generatedLoadCounts,
                        $generatedTypeCounts
                    );
                    $this->registerPairedCandidateForCounterpart($classe, $candidate, $pairedSupervisorsByClass);
                    $assigned++;
                }
            }
        }

        // ==========================================================
        //  Enregistrement final en base de données
        // ==========================================================
        $this->em->flush();
    }

    /**
     * @param Examen[] $examens
     * @return Examen[]
     */
    private function sortExamensChronologically(array $examens): array
    {
        usort($examens, static function (Examen $left, Examen $right): int {
            return [$left->getDate()?->format('Y-m-d'), $left->getHeursDebut()?->format('H:i:s'), $left->getHeureFin()?->format('H:i:s'), $left->getId()]
                <=> [$right->getDate()?->format('Y-m-d'), $right->getHeursDebut()?->format('H:i:s'), $right->getHeureFin()?->format('H:i:s'), $right->getId()];
        });

        return $examens;
    }

    /**
     * @param Examen[] $examens
     * @return array<string, int>
     */
    private function buildSlotIndexes(array $examens): array
    {
        $slotIndexes = [];
        $seenSlots = [];
        $currentSlotByDate = [];

        foreach ($examens as $examen) {
            $dateKey = $examen->getDate()?->format('Y-m-d');
            $slotKey = $this->buildSlotKey($examen);

            if (isset($seenSlots[$dateKey][$slotKey])) {
                $slotIndexes[$slotKey] = $seenSlots[$dateKey][$slotKey];
                continue;
            }

            $currentSlotByDate[$dateKey] = ($currentSlotByDate[$dateKey] ?? -1) + 1;
            $seenSlots[$dateKey][$slotKey] = $currentSlotByDate[$dateKey];
            $slotIndexes[$slotKey] = $currentSlotByDate[$dateKey];
        }

        return $slotIndexes;
    }

    private function buildSlotKey(Examen $examen): string
    {
        return sprintf(
            '%s|%s|%s',
            $examen->getDate()?->format('Y-m-d'),
            $examen->getHeursDebut()?->format('H:i:s'),
            $examen->getHeureFin()?->format('H:i:s')
        );
    }

    private function resolveExamCycle(Examen $examen): ?Cycles
    {
        $resolvedCycle = null;

        foreach ($examen->getClasse() as $classe) {
            $currentCycle = $classe->getNiveau()?->getCycle();

            if ($currentCycle === null) {
                return null;
            }

            if ($resolvedCycle === null) {
                $resolvedCycle = $currentCycle;
                continue;
            }

            if ($resolvedCycle->getId() !== $currentCycle->getId()) {
                throw new \RuntimeException("Un examen ne peut pas mélanger des classes de plusieurs cycles.");
            }
        }

        return $resolvedCycle;
    }

    private function resolveTeacherPdfCycleCode(Cycles $cycle): string
    {
        $name = strtolower((string) $cycle->getName());

        if (str_contains($name, '2') || str_contains($name, 'lycee')) {
            return Teatchers::PDF_CYCLE_2;
        }

        return Teatchers::PDF_CYCLE_1;
    }

    /**
     * @param ClassName[] $classes
     * @return ClassName[]
     */
    private function sortClassesByDescendingLevel(array $classes): array
    {
        usort($classes, function (ClassName $left, ClassName $right): int {
            $leftPriority = $this->resolveLevelPriority($left->getNiveau());
            $rightPriority = $this->resolveLevelPriority($right->getNiveau());

            if ($leftPriority !== $rightPriority) {
                return $rightPriority <=> $leftPriority;
            }

            return mb_strtolower((string) $left->getName()) <=> mb_strtolower((string) $right->getName());
        });

        return $classes;
    }

    private function resolveLevelPriority(?Niveau $niveau): int
    {
        $name = mb_strtolower((string) $niveau?->getName());

        return match (true) {
            str_contains($name, 'tle') || str_contains($name, 'terminale') => 60,
            str_contains($name, '1ere') || str_contains($name, 'premiere') => 50,
            str_contains($name, '2nde') || str_contains($name, 'seconde') => 40,
            str_contains($name, '3eme') => 30,
            str_contains($name, '4eme') => 20,
            str_contains($name, '5eme') => 10,
            str_contains($name, '6eme') => 0,
            default => -10,
        };
    }

    private function resolveDomainFromMatter(?Matter $matter): ?string
    {
        return $this->resolveDomainFromLabel($matter?->getNom());
    }

    private function resolveDomainFromLabel(?string $label): ?string
    {
        $normalized = mb_strtolower(trim((string) $label));

        if ($normalized === '') {
            return null;
        }

        foreach ($this->literaryDomainTokens as $token) {
            $normalizedToken = mb_strtolower(trim((string) $token));
            if ($normalizedToken !== '' && str_contains($normalized, $normalizedToken)) {
                return 'litteraire';
            }
        }

        foreach ($this->scientificDomainTokens as $token) {
            $normalizedToken = mb_strtolower(trim((string) $token));
            if ($normalizedToken !== '' && str_contains($normalized, $normalizedToken)) {
                return 'scientifique';
            }
        }

        return null;
    }

    private function isTeacherInDomainForCycle(Teatchers $teacher, string $domain, Cycles $cycle): bool
    {
        $disciplineDomain = $this->resolveDomainFromLabel($teacher->getDisciplines());
        if ($disciplineDomain === $domain) {
            return true;
        }

        foreach ($teacher->getEnseignement() as $enseignement) {
            if ($enseignement->getClassName()?->getNiveau()?->getCycle()?->getId() !== $cycle->getId()) {
                continue;
            }

            if ($this->resolveDomainFromLabel($enseignement->getMatter()?->getNom()) === $domain) {
                return true;
            }
        }

        return false;
    }

    private function teachesDomainInNiveau(Teatchers $teacher, string $domain, ?Niveau $niveau, Cycles $cycle): bool
    {
        if ($niveau === null || $niveau->getId() === null) {
            return false;
        }

        foreach ($teacher->getEnseignement() as $enseignement) {
            $teachingClass = $enseignement->getClassName();

            if ($teachingClass?->getNiveau()?->getCycle()?->getId() !== $cycle->getId()) {
                continue;
            }

            if ($teachingClass->getNiveau()?->getId() !== $niveau->getId()) {
                continue;
            }

            if ($this->resolveDomainFromLabel($enseignement->getMatter()?->getNom()) === $domain) {
                return true;
            }
        }

        return false;
    }

    private function isTraineeInDomain(Stagiaire $trainee, string $domain): bool
    {
        return $this->resolveDomainFromLabel($trainee->getMatiereDeStage()?->getNom()) === $domain;
    }

    /**
     * Tle C et Tle D2 (ou Tle D) partagent un seul surveillant si les deux classes
     * sont presentes dans le meme examen.
     *
     * @param string[] $classNamesInExam
     */
    private function isCombinedClassHandledAsSingleSupervisor(ClassName $classe, array $classNamesInExam): bool
    {
        $normalizedClass = $this->normalizeClassName((string) $classe->getName());

        if (!in_array($normalizedClass, ['tle c', 'tle d2', 'tle d'], true)) {
            return false;
        }

        foreach ($classNamesInExam as $className) {
            $candidate = $this->normalizeClassName($className);

            if (
                ($normalizedClass === 'tle c' && in_array($candidate, ['tle d2', 'tle d'], true))
                || (in_array($normalizedClass, ['tle d2', 'tle d'], true) && $candidate === 'tle c')
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeClassName(string $className): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($className)) ?? '');
    }

    /**
     * @param array<string, array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire}> $pairedSupervisorsByClass
     * @return array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire}|null
     */
    private function resolvePairedCandidateForClass(ClassName $classe, array $pairedSupervisorsByClass): ?array
    {
        $className = trim((string) $classe->getName());

        if ($className === '') {
            return null;
        }

        return $pairedSupervisorsByClass[$className] ?? null;
    }

    /**
     * @param array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire} $candidate
     * @param array<string, array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire}> $pairedSupervisorsByClass
     */
    private function registerPairedCandidateForCounterpart(
        ClassName $classe,
        array $candidate,
        array &$pairedSupervisorsByClass
    ): void {
        $className = trim((string) $classe->getName());
        $counterparts = $this->resolvePairedClassNames($className);

        if ($counterparts === []) {
            return;
        }

        foreach ($counterparts as $counterpart) {
            if (!isset($pairedSupervisorsByClass[$counterpart])) {
                $pairedSupervisorsByClass[$counterpart] = $candidate;
            }
        }
    }

    /**
     * @return string[]
     */
    private function resolvePairedClassNames(string $className): array
    {
        $normalized = $this->normalizeClassName($className);

        return match ($normalized) {
            'tle c' => ['Tle D2', 'Tle D'],
            'tle d2', 'tle d' => ['Tle C'],
            default => [],
        };
    }

    /**
     * @param Teatchers[] $teachers
     * @param string[] $usedSupervisorKeys
     * @param array<string, array<int, array{date: string, start: string, end: string, slot: int}>> $generatedAssignments
     */
    private function findAvailableTeacher(
        array $teachers,
        Examen $examen,
        array $usedSupervisorKeys,
        int $slotIndex,
        array $generatedAssignments,
        array $generatedLoadCounts
    ): ?Teatchers {
        $orderedTeachers = $this->sortPersonsByCurrentLoad('teacher', $teachers, $generatedLoadCounts);

        foreach ($orderedTeachers as $teacher) {
            $candidateKey = $this->buildCandidateKey('teacher', $teacher->getId());

            if ($candidateKey === null || \in_array($candidateKey, $usedSupervisorKeys, true)) {
                continue;
            }

            if ($this->teacherRepository->isTeacherBusyDuringExam(
                $examen->getDate(),
                $examen->getHeursDebut(),
                $examen->getHeureFin(),
                $teacher->getId()
            )) {
                continue;
            }

            if ($this->isSupervisorBusyInCurrentGeneration($candidateKey, $examen, $generatedAssignments)) {
                continue;
            }

            if ($this->hasSupervisorConsecutiveSlot($candidateKey, $examen, $slotIndex, $generatedAssignments)) {
                continue;
            }

            return $teacher;
        }

        return null;
    }

    /**
     * @param Teatchers[] $teachers
     * @param Stagiaire[] $trainees
     * @param string[] $usedSupervisorKeys
     * @param array<string, array<int, array{date: string, start: string, end: string, slot: int}>> $generatedAssignments
     * @return array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire}|null
     */
    private function findAvailableCandidate(
        array $teachers,
        array $trainees,
        Examen $examen,
        array $usedSupervisorKeys,
        int $slotIndex,
        array $generatedAssignments,
        array $generatedLoadCounts,
        array $generatedTypeCounts
    ): ?array {
        $availableTrainee = $this->findAvailableCandidateFromList(
            'trainee',
            $trainees,
            $examen,
            $usedSupervisorKeys,
            $slotIndex,
            $generatedAssignments,
            $generatedLoadCounts
        );

        $availableTeacher = $this->findAvailableCandidateFromList(
            'teacher',
            $teachers,
            $examen,
            $usedSupervisorKeys,
            $slotIndex,
            $generatedAssignments,
            $generatedLoadCounts
        );

        if ($availableTrainee === null) {
            return $availableTeacher;
        }

        if ($availableTeacher === null) {
            return $availableTrainee;
        }

        if ($this->shouldPrioritizeTrainee(
            $availableTrainee,
            $availableTeacher,
            $generatedLoadCounts,
            $generatedTypeCounts
        )) {
            return $availableTrainee;
        }

        return $availableTeacher;
    }

    /**
     * @param Teatchers[]|Stagiaire[] $persons
     * @param string[] $usedSupervisorKeys
     * @param array<string, array<int, array{date: string, start: string, end: string, slot: int}>> $generatedAssignments
     * @return array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire}|null
     */
    private function findAvailableCandidateFromList(
        string $type,
        array $persons,
        Examen $examen,
        array $usedSupervisorKeys,
        int $slotIndex,
        array $generatedAssignments,
        array $generatedLoadCounts
    ): ?array {
        $orderedPersons = $this->sortPersonsByCurrentLoad($type, $persons, $generatedLoadCounts);

        foreach ($orderedPersons as $person) {
            $candidate = [
                'type' => $type,
                'person' => $person,
            ];
            $candidateKey = $this->buildCandidateKey($candidate['type'], $candidate['person']->getId());

            if ($candidateKey === null || \in_array($candidateKey, $usedSupervisorKeys, true)) {
                continue;
            }

            if ($this->isCandidateBusyDuringExam($candidate, $examen)) {
                continue;
            }

            if ($this->isSupervisorBusyInCurrentGeneration($candidateKey, $examen, $generatedAssignments)) {
                continue;
            }

            if ($this->hasSupervisorConsecutiveSlot($candidateKey, $examen, $slotIndex, $generatedAssignments)) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * @param Teatchers[]|Stagiaire[] $persons
     * @return Teatchers[]|Stagiaire[]
     */
    private function sortPersonsByCurrentLoad(string $type, array $persons, array $generatedLoadCounts): array
    {
        usort($persons, function (Teatchers|Stagiaire $left, Teatchers|Stagiaire $right) use ($type, $generatedLoadCounts): int {
            $leftLoad = $this->getCurrentLoad($type, $left, $generatedLoadCounts);
            $rightLoad = $this->getCurrentLoad($type, $right, $generatedLoadCounts);

            if ($leftLoad !== $rightLoad) {
                return $leftLoad <=> $rightLoad;
            }

            $leftName = mb_strtolower(trim(sprintf('%s %s', $left->getLastname(), $left->getFirstname())));
            $rightName = mb_strtolower(trim(sprintf('%s %s', $right->getLastname(), $right->getFirstname())));

            return $leftName <=> $rightName;
        });

        return $persons;
    }

    /**
     * @param array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire} $traineeCandidate
     * @param array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire} $teacherCandidate
     */
    private function shouldPrioritizeTrainee(
        array $traineeCandidate,
        array $teacherCandidate,
        array $generatedLoadCounts,
        array $generatedTypeCounts
    ): bool {
        $traineeAssigned = $generatedTypeCounts['trainee'] ?? 0;
        $teacherAssigned = $generatedTypeCounts['teacher'] ?? 0;

        // Objectif métier: autant que possible, les stagiaires doivent surveiller
        // plus que les enseignants à l'échelle globale.
        if ($traineeAssigned <= $teacherAssigned) {
            return true;
        }

        $traineeLoad = $this->getCurrentLoad($traineeCandidate['type'], $traineeCandidate['person'], $generatedLoadCounts);
        $teacherLoad = $this->getCurrentLoad($teacherCandidate['type'], $teacherCandidate['person'], $generatedLoadCounts);
        $requiredLead = $this->resolveTraineeLeadTarget($teacherAssigned + $traineeAssigned);

        return $traineeLoad < $teacherLoad + $requiredLead;
    }

    private function resolveTraineeLeadTarget(int $generatedTotalAssignments): int
    {
        if ($generatedTotalAssignments < 60) {
            return 1;
        }

        if ($generatedTotalAssignments < 160) {
            return 2;
        }

        return 3;
    }

    private function getCurrentLoad(string $type, Teatchers|Stagiaire $person, array $generatedLoadCounts): int
    {
        $key = $this->buildCandidateKey($type, $person->getId());

        if ($key === null) {
            return PHP_INT_MAX;
        }

        return $person->getSurveillances()->count() + ($generatedLoadCounts[$key] ?? 0);
    }

    /**
     * @param array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire} $candidate
     */
    private function isCandidateBusyDuringExam(array $candidate, Examen $examen): bool
    {
        $personId = $candidate['person']->getId();

        if ($personId === null) {
            return true;
        }

        if ($candidate['type'] === 'teacher') {
            return $this->teacherRepository->isTeacherBusyDuringExam(
                $examen->getDate(),
                $examen->getHeursDebut(),
                $examen->getHeureFin(),
                $personId
            );
        }

        return $this->stagiaireRepository->isTraineeBusyDuringExam(
            $examen->getDate(),
            $examen->getHeursDebut(),
            $examen->getHeureFin(),
            $personId
        );
    }

    private function buildCandidateKey(string $type, ?int $personId): ?string
    {
        if ($personId === null) {
            return null;
        }

        return sprintf('%s:%d', $type, $personId);
    }

    /**
     * @param string[] $usedSupervisorKeys
     * @param array<string, array<int, array{date: string, start: string, end: string, slot: int}>> $generatedAssignments
     */
    private function assignTeacher(
        Examen $examen,
        ClassName $classe,
        Teatchers $teacher,
        int $slotIndex,
        array &$usedSupervisorKeys,
        array &$generatedAssignments,
        array &$generatedLoadCounts,
        array &$generatedTypeCounts
    ): void {
        $surveillance = new Surveillance();
        $surveillance->setExamen($examen);
        $surveillance->setClasse($classe);
        $surveillance->setEnseignant($teacher);

        $this->em->persist($surveillance);

        $teacherKey = $this->buildCandidateKey('teacher', $teacher->getId());
        $usedSupervisorKeys[] = $teacherKey;
        $generatedAssignments[$teacherKey][] = [
            'date' => $examen->getDate()?->format('Y-m-d'),
            'start' => $examen->getHeursDebut()?->format('H:i:s'),
            'end' => $examen->getHeureFin()?->format('H:i:s'),
            'slot' => $slotIndex,
        ];
        $generatedLoadCounts[$teacherKey] = ($generatedLoadCounts[$teacherKey] ?? 0) + 1;
        $generatedTypeCounts['teacher'] = ($generatedTypeCounts['teacher'] ?? 0) + 1;
    }

    /**
     * @param array{type: 'teacher'|'trainee', person: Teatchers|Stagiaire} $candidate
     * @param string[] $usedSupervisorKeys
     * @param array<string, array<int, array{date: string, start: string, end: string, slot: int}>> $generatedAssignments
     */
    private function assignCandidate(
        Examen $examen,
        ClassName $classe,
        array $candidate,
        int $slotIndex,
        array &$usedSupervisorKeys,
        array &$generatedAssignments,
        array &$generatedLoadCounts,
        array &$generatedTypeCounts
    ): void {
        if ($candidate['type'] === 'teacher') {
            $this->assignTeacher(
                $examen,
                $classe,
                $candidate['person'],
                $slotIndex,
                $usedSupervisorKeys,
                $generatedAssignments,
                $generatedLoadCounts,
                $generatedTypeCounts
            );

            return;
        }

        $surveillance = new Surveillance();
        $surveillance->setExamen($examen);
        $surveillance->setClasse($classe);
        $surveillance->setStagiaire($candidate['person']);

        $this->em->persist($surveillance);

        $traineeKey = $this->buildCandidateKey('trainee', $candidate['person']->getId());
        $usedSupervisorKeys[] = $traineeKey;
        $generatedAssignments[$traineeKey][] = [
            'date' => $examen->getDate()?->format('Y-m-d'),
            'start' => $examen->getHeursDebut()?->format('H:i:s'),
            'end' => $examen->getHeureFin()?->format('H:i:s'),
            'slot' => $slotIndex,
        ];
        $generatedLoadCounts[$traineeKey] = ($generatedLoadCounts[$traineeKey] ?? 0) + 1;
        $generatedTypeCounts['trainee'] = ($generatedTypeCounts['trainee'] ?? 0) + 1;
    }

    /**
     * @param array<string, array<int, array{date: string, start: string, end: string, slot: int}>> $generatedAssignments
     */
    private function isSupervisorBusyInCurrentGeneration(string $candidateKey, Examen $examen, array $generatedAssignments): bool
    {
        $dateKey = $examen->getDate()?->format('Y-m-d');
        $start = $examen->getHeursDebut()?->format('H:i:s');
        $end = $examen->getHeureFin()?->format('H:i:s');

        foreach ($generatedAssignments[$candidateKey] ?? [] as $assignment) {
            if ($assignment['date'] !== $dateKey) {
                continue;
            }

            if ($assignment['start'] < $end && $assignment['end'] > $start) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, array<int, array{date: string, start: string, end: string, slot: int}>> $generatedAssignments
     */
    private function hasSupervisorConsecutiveSlot(string $candidateKey, Examen $examen, int $slotIndex, array $generatedAssignments): bool
    {
        $dateKey = $examen->getDate()?->format('Y-m-d');

        foreach ($generatedAssignments[$candidateKey] ?? [] as $assignment) {
            if ($assignment['date'] === $dateKey && $assignment['slot'] === $slotIndex - 1) {
                return true;
            }
        }

        return false;
    }
}
