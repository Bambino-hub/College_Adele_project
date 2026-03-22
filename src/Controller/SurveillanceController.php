<?php

namespace App\Controller;

use App\Entity\Cycles;
use App\Repository\ExamenRepository;
use App\Repository\SurveillanceRepository;
use App\Service\SurveillanceGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

class SurveillanceController extends AbstractController
{
    /**
     * Initialise les dépôts nécessaires au tableau de surveillance.
     */
    public function __construct(
        private readonly SurveillanceRepository $surveillanceRepository,
        private  readonly ExamenRepository $examenRepository

    ) {}

    /**
     * Génère les surveillances d'un cycle puis renvoie vers la page appelante.
     */
    #[Route('/exam/generate-surveillance/cycle/{id}', name: 'exam_generate_surveillance_for_cycle', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function generateForCycle(Request $request, Cycles $cycle, SurveillanceGenerator $generator): RedirectResponse
    {
        try {
            $generator->generate($this->examenRepository->findByCycle($cycle->getId()));
            $this->addFlash('success', sprintf('Les surveillances du %s ont été générées.', $cycle->getName()));
        } catch (\Throwable $exception) {
            $this->addFlash('danger', $exception->getMessage());
        }

        $referer = $request->headers->get('referer');

        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_examen_cycle_index', ['id' => $cycle->getId()]);
    }

    /**
     * Construit le tableau des surveillances pour un cycle donné.
     */
    #[Route('/surveillance/tableau/cycle/{id}', name: 'surveillance_tableau_cycle', requirements: ['id' => '\d+'])]
    public function showSurveillanceTableau(Request $request, Cycles $cycle): Response
    {
        $surveillances = $this->surveillanceRepository->findSurveillanceTableau($cycle->getId());
        $headerConfig = $this->resolveHeaderConfig($request, $cycle);
        $orderedLevels = $this->resolveOrderedLevels($cycle);

        $groupedSurveillances = $this->buildGroupedSurveillances($surveillances, $orderedLevels);
        $programRows = $this->buildProgramRows($groupedSurveillances, $orderedLevels);


        return $this->render('surveillance/tableau.html.twig', [
            'cycle' => $cycle,
            'orderedLevels' => $orderedLevels,
            'programRows' => $programRows,
            'headerConfig' => $headerConfig,
        ]);
    }

    /**
     * @param array<int, \App\Entity\Surveillance> $surveillances
     * @param string[] $orderedLevels
     * @return array<string, array<string, array{label: string, levels: array<string, array<string, array{id: int|null, matiere: string, assignments: array<string, string[]>}>>}>>
     */
    private function buildGroupedSurveillances(array $surveillances, array $orderedLevels): array
    {
        $grouped = [];

        foreach ($surveillances as $surveillance) {
            $examen = $surveillance->getExamen();
            $classe = $surveillance->getClasse();

            if ($examen === null || $classe === null) {
                continue;
            }

            $dateKey = $examen->getDate()?->format('Y-m-d') ?? 'Date inconnue';
            $start = $examen->getHeursDebut()?->format('H:i') ?? '??:??';
            $end = $examen->getHeureFin()?->format('H:i') ?? '??:??';
            $slotKey = sprintf('%s-%s', $start, $end);

            if (!isset($grouped[$dateKey][$slotKey])) {
                $grouped[$dateKey][$slotKey] = [
                    'label' => sprintf('%s - %s', $start, $end),
                    'levels' => [],
                ];
            }

            $levelName = $classe->getNiveau()?->getName() ?? 'Sans niveau';
            $displayLevel = $this->resolveDisplayLevelName($levelName, $classe->getName());

            if (!isset($grouped[$dateKey][$slotKey]['levels'][$displayLevel])) {
                $grouped[$dateKey][$slotKey]['levels'][$displayLevel] = [];
            }

            $examId = $examen->getId() ?? 0;
            $entryKey = (string) $examId;

            if (!isset($grouped[$dateKey][$slotKey]['levels'][$displayLevel][$entryKey])) {
                $grouped[$dateKey][$slotKey]['levels'][$displayLevel][$entryKey] = [
                    'id' => $examen->getId(),
                    'matiere' => $examen->getMatiere()?->getNom() ?? 'Matiere non definie',
                    'assignments' => [],
                ];
            }

            $classLabel = $this->resolveDisplayClassName($classe->getName() ?? 'Classe inconnue');

            if (!isset($grouped[$dateKey][$slotKey]['levels'][$displayLevel][$entryKey]['assignments'][$classLabel])) {
                $grouped[$dateKey][$slotKey]['levels'][$displayLevel][$entryKey]['assignments'][$classLabel] = [];
            }

            $fullName = trim($surveillance->getSurveillantFullName());
            if ($fullName !== '') {
                $grouped[$dateKey][$slotKey]['levels'][$displayLevel][$entryKey]['assignments'][$classLabel][$fullName] = $fullName;
            }
        }

        ksort($grouped);

        foreach ($grouped as &$slots) {
            uksort($slots, static fn(string $left, string $right): int => strcmp($left, $right));

            foreach ($slots as &$slotData) {
                uksort($slotData['levels'], function (string $left, string $right) use ($orderedLevels): int {
                    $leftPosition = array_search($left, $orderedLevels, true);
                    $rightPosition = array_search($right, $orderedLevels, true);

                    $leftPosition = $leftPosition === false ? PHP_INT_MAX : $leftPosition;
                    $rightPosition = $rightPosition === false ? PHP_INT_MAX : $rightPosition;

                    return $leftPosition <=> $rightPosition;
                });

                foreach ($slotData['levels'] as &$entriesByKey) {
                    foreach ($entriesByKey as &$entry) {
                        ksort($entry['assignments'], SORT_NATURAL | SORT_FLAG_CASE);

                        foreach ($entry['assignments'] as &$surveillants) {
                            $surveillants = array_values($surveillants);
                            sort($surveillants, SORT_NATURAL | SORT_FLAG_CASE);
                        }
                        unset($surveillants);
                    }
                    unset($entry);
                }
                unset($entriesByKey);
            }
            unset($slotData);
        }
        unset($slots);

        return $grouped;
    }

    private function resolveDisplayLevelName(string $levelName, ?string $className): string
    {
        $normalizedLevel = mb_strtolower(trim($levelName));
        $normalizedClass = mb_strtolower(trim((string) $className));

        if (
            in_array($normalizedLevel, ['tle c', 'tle d', 'tle d2'], true)
            || in_array($normalizedClass, ['tle c', 'tle d', 'tle d2'], true)
        ) {
            return 'Tle D et C';
        }

        return $levelName;
    }

    private function resolveDisplayClassName(string $className): string
    {
        $normalized = mb_strtolower(trim($className));

        return match ($normalized) {
            'tle d2', 'tle d' => 'Tle D2',
            default => $className,
        };
    }

    /**
     * @param array<string, array<string, array{label: string, levels: array<string, array<string, array{id: int|null, matiere: string, assignments: array<string, string[]>}>>}>> $groupedSurveillances
     * @param string[] $orderedLevels
     * @return array<int, array{date: string, slot: string, cells: array<string, array<int, array{id: int|null, matiere: string, assignments: array<string, string[]>}>>}>
     */
    private function buildProgramRows(array $groupedSurveillances, array $orderedLevels): array
    {
        $rows = [];

        foreach ($groupedSurveillances as $date => $slots) {
            foreach ($slots as $slot) {
                $cells = [];

                foreach ($orderedLevels as $level) {
                    $cells[$level] = [];

                    if (!isset($slot['levels'][$level])) {
                        continue;
                    }

                    foreach ($slot['levels'][$level] as $entry) {
                        $cells[$level][] = $entry;
                    }
                }

                $rows[] = [
                    'date' => $date,
                    'slot' => $slot['label'],
                    'cells' => $cells,
                ];
            }
        }

        return $rows;
    }

    /**
     * Retourne l'ordre de présentation des niveaux selon le cycle.
     *
     * @return string[]
     */
    private function resolveOrderedLevels(?Cycles $cycle): array
    {
        if ($cycle?->getName() === 'Cycle 2') {
            return ['2nde CD', '2nde A', '1ere D', '1ere A', 'Tle A', 'Tle D et C'];
        }

        return ['6eme', '5eme', '4eme', '3eme'];
    }

    /**
     * Exporte le tableau de surveillance du cycle demandé.
     */
    #[Route('/surveillance/tableau/export/{format}/cycle/{id}', name: 'surveillance_tableau_export_cycle', requirements: ['id' => '\d+'])]
    public function exportTableau(Request $request, string $format, Cycles $cycle): Response
    {
        $surveillances = $this->surveillanceRepository->findSurveillanceTableau($cycle->getId());
        $headerConfig = $this->resolveHeaderConfig($request, $cycle);

        $orderedLevels = $this->resolveOrderedLevels($cycle);
        $groupedSurveillances = $this->buildGroupedSurveillances($surveillances, $orderedLevels);
        $programRows = $this->buildProgramRows($groupedSurveillances, $orderedLevels);

        switch ($format) {
            case 'pdf':
                return $this->exportPdf($programRows, $orderedLevels, $cycle, $headerConfig);
            default:
                throw new \InvalidArgumentException('Format non supporté : ' . $format . '. Formats disponibles : pdf.');
        }
    }

    /**
     * Génère le rendu PDF à partir du tableau préparé.
     */
    private function exportPdf(array $programRows, array $orderedLevels, Cycles $cycle, array $headerConfig): Response
    {
        $html = $this->renderView('surveillance/tableau_export.html.twig', [
            'programRows' => $programRows,
            'cycle' => $cycle,
            'orderedLevels' => $orderedLevels,
            'headerConfig' => $headerConfig,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Times-Roman');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = sprintf('tableau_surveillance_%s.pdf', strtolower(str_replace(' ', '_', $cycle->getName() ?? 'cycle')));

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * Construit l'entete personnalisee a partir des parametres de filtre.
     *
     * @return array{title: string, cycleLine: string, trimestre: string, periode: string, anneeScolaire: string, customHeader: string}
     */
    private function resolveHeaderConfig(Request $request, Cycles $cycle): array
    {
        $trimestre = (string) $request->query->get('trimestre', '2');
        if (!in_array($trimestre, ['1', '2'], true)) {
            $trimestre = '2';
        }

        $periode = (string) $request->query->get('periode', 'premiere');
        if (!in_array($periode, ['premiere', 'deuxieme'], true)) {
            $periode = 'premiere';
        }

        $anneeScolaire = trim((string) $request->query->get('annee_scolaire', $this->defaultSchoolYear()));
        if ($anneeScolaire === '') {
            $anneeScolaire = $this->defaultSchoolYear();
        }

        $customHeader = trim((string) $request->query->get('entete', ''));

        $periodeLabel = $periode === 'deuxieme' ? 'DEUXIEME PERIODE DE COURS' : 'PREMIERE PERIODE DE COURS';
        $trimestreLabel = $trimestre === '1' ? 'PREMIER' : 'DEUXIEME';

        $defaultTitle = sprintf(
            'PROGRAMME DES SURVEILLANCES DE LA %s DU %s TRIMESTRE %s',
            $periodeLabel,
            $trimestreLabel,
            $anneeScolaire
        );

        $title = $customHeader !== '' ? strtoupper($customHeader) : $defaultTitle;

        return [
            'title' => $title,
            'cycleLine' => sprintf('CYCLE : %s', strtoupper((string) $cycle->getName())),
            'trimestre' => $trimestre,
            'periode' => $periode,
            'anneeScolaire' => $anneeScolaire,
            'customHeader' => $customHeader,
        ];
    }

    private function defaultSchoolYear(): string
    {
        $currentYear = (int) date('Y');
        $nextYear = $currentYear + 1;

        return sprintf('%d-%d', $currentYear, $nextYear);
    }
}
