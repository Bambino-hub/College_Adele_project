<?php

namespace App\Controller;

use App\Entity\Cycles;
use App\Entity\Examen;
use App\Form\ExamenType;
use App\Repository\CyclesRepository;
use App\Repository\ExamenRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/examen')]
final class ExamenController extends AbstractController
{
    /**
     * Affiche la vue globale des examens et les points d'entrée par cycle.
     */
    #[Route(name: 'app_examen_index', methods: ['GET'])]
    public function index(ExamenRepository $examenRepository, CyclesRepository $cyclesRepository): Response
    {
        return $this->render('examen/index.html.twig', [
            'examens' => $examenRepository->findAll(),
            'cycles' => $cyclesRepository->findAll(),
        ]);
    }

    #[Route('/college', name: 'app_examen_college', methods: ['GET'])]
    public function college(CyclesRepository $cyclesRepository): Response
    {
        $cycle = $this->resolveCycleByName($cyclesRepository, 'Cycle 1');

        return $this->redirectToRoute('app_examen_cycle_index', ['id' => $cycle->getId()]);
    }

    #[Route('/lycee', name: 'app_examen_lycee', methods: ['GET'])]
    public function lycee(CyclesRepository $cyclesRepository): Response
    {
        $cycle = $this->resolveCycleByName($cyclesRepository, 'Cycle 2');

        return $this->redirectToRoute('app_examen_cycle_index', ['id' => $cycle->getId()]);
    }

    /**
     * Regroupe les examens d'un cycle par date et créneau pour l'affichage tableau.
     */
    #[Route('/cycle/{id}/tableau', name: 'app_examen_cycle_index', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function cycleIndex(Request $request, Cycles $cycle, ExamenRepository $examenRepository): Response
    {
        $examens = $examenRepository->findByCycle($cycle->getId());
        $orderedLevels = $this->resolveOrderedLevels($cycle);
        $groupedExamens = $this->buildGroupedExamens($examens, $orderedLevels);
        $programRows = $this->buildProgramRows($groupedExamens, $orderedLevels);

        $headerConfig = $this->resolveHeaderConfig($request, $cycle);

        return $this->render('examen/cycle_tableau.html.twig', [
            'cycle' => $cycle,
            'groupedExamens' => $groupedExamens,
            'orderedLevels' => $orderedLevels,
            'programRows' => $programRows,
            'headerConfig' => $headerConfig,
        ]);
    }

    /**
     * Exporte en PDF le tableau des examens d'un cycle.
     */
    #[Route('/cycle/{id}/export/pdf', name: 'app_examen_cycle_export_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function exportCyclePdf(Request $request, Cycles $cycle, ExamenRepository $examenRepository): Response
    {
        $examens = $examenRepository->findByCycle($cycle->getId());
        $orderedLevels = $this->resolveOrderedLevels($cycle);
        $groupedExamens = $this->buildGroupedExamens($examens, $orderedLevels);
        $programRows = $this->buildProgramRows($groupedExamens, $orderedLevels);
        $headerConfig = $this->resolveHeaderConfig($request, $cycle);

        $html = $this->renderView('examen/cycle_export_pdf.html.twig', [
            'cycle' => $cycle,
            'groupedExamens' => $groupedExamens,
            'orderedLevels' => $orderedLevels,
            'programRows' => $programRows,
            'headerConfig' => $headerConfig,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Times-Roman');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A3', 'landscape');
        $dompdf->render();

        $filename = sprintf('examens_%s.pdf', strtolower(str_replace(' ', '_', $cycle->getName() ?? 'cycle')));

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    #[Route('/new', name: 'app_examen_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $examan = new Examen();
        $form = $this->createForm(ExamenType::class, $examan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($examan);
            $entityManager->flush();

            $this->addFlash('success', 'Examen enregistré avec succès.');

            return $this->redirectToRoute('app_examen_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('examen/new.html.twig', [
            'examan' => $examan,
            'form' => $form,
        ]);
    }

    /**
     * Crée un examen en conservant le contexte du cycle courant.
     */
    #[Route('/cycle/{id}/new', name: 'app_examen_new_for_cycle', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function newForCycle(Request $request, Cycles $cycle, EntityManagerInterface $entityManager): Response
    {
        $examan = new Examen();
        $form = $this->createForm(ExamenType::class, $examan, [
            'cycle' => $cycle,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($examan);
            $entityManager->flush();

            $this->addFlash('success', 'Examen enregistré avec succès.');

            return $this->redirectToRoute('app_examen_new_for_cycle', ['id' => $cycle->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('examen/new.html.twig', [
            'examan' => $examan,
            'form' => $form,
            'cycle' => $cycle,
        ]);
    }

    #[Route('/{id}', name: 'app_examen_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Examen $examan): Response
    {
        return $this->render('examen/show.html.twig', [
            'examan' => $examan,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_examen_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        Examen $examan,
        EntityManagerInterface $entityManager,
        CyclesRepository $cyclesRepository
    ): Response {
        $cycleId = (int) $request->query->get('cycle_id', 0);
        if ($cycleId <= 0) {
            $cycleId = $examan->getCycle()?->getId() ?? 0;
        }

        $cycle = $examan->getCycle();
        if ($cycle === null && $cycleId > 0) {
            $cycle = $cyclesRepository->find($cycleId);
        }

        $form = $this->createForm(ExamenType::class, $examan, [
            'cycle' => $cycle,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            if ($cycleId > 0) {
                return $this->redirectToRoute('app_examen_cycle_index', ['id' => $cycleId], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_examen_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('examen/edit.html.twig', [
            'examan' => $examan,
            'form' => $form,
            'cycleId' => $cycleId,
            'cycle' => $cycle,
        ]);
    }

    #[Route('/{id}', name: 'app_examen_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Examen $examan, EntityManagerInterface $entityManager): Response
    {
        $cycleId = $request->getPayload()->getInt('cycle_id');
        if ($cycleId <= 0) {
            $cycleId = (int) $request->query->get('cycle_id', 0);
        }
        if ($cycleId <= 0) {
            $cycleId = $examan->getCycle()?->getId() ?? 0;
        }

        if ($this->isCsrfTokenValid('delete' . $examan->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($examan);
            $entityManager->flush();
        }

        if ($cycleId > 0) {
            return $this->redirectToRoute('app_examen_cycle_index', ['id' => $cycleId], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_examen_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Résout un cycle métier à partir de son libellé attendu.
     */
    private function resolveCycleByName(CyclesRepository $cyclesRepository, string $cycleName): Cycles
    {
        $cycle = $cyclesRepository->findOneBy(['name' => $cycleName]);

        if (!$cycle instanceof Cycles) {
            throw $this->createNotFoundException(sprintf('Le cycle %s est introuvable.', $cycleName));
        }

        return $cycle;
    }

    /**
     * Retourne l'ordre d'affichage des niveaux selon le cycle.
     *
     * @return string[]
     */
    private function resolveOrderedLevels(Cycles $cycle): array
    {
        if ($cycle->getName() === 'Cycle 2') {
            return ['2nde CD', '2nde A', '1ere D', '1ere A', 'Tle C', 'Tle D', 'Tle A'];
        }

        return ['6eme', '5eme', '4eme', '3eme'];
    }

    /**
     * Structure les examens par date, créneau et niveau pour la vue tableau.
     *
     * @param Examen[] $examens
     * @param string[] $orderedLevels
     * @return array<string, array<int, array{label: string, levels: array<string, array<int, array{id: int|null, matiere: string, classes: string[]}>>}>>
     */
    private function buildGroupedExamens(array $examens, array $orderedLevels): array
    {
        $grouped = [];

        foreach ($examens as $examen) {
            $dateKey = $examen->getDate()?->format('Y-m-d') ?? 'Date inconnue';
            $start = $examen->getHeursDebut()?->format('H:i') ?? '??:??';
            $end = $examen->getHeureFin()?->format('H:i') ?? '??:??';
            $slotKey = \sprintf('%s-%s', $start, $end);

            if (!isset($grouped[$dateKey][$slotKey])) {
                $grouped[$dateKey][$slotKey] = [
                    'label' => \sprintf('%s - %s', $start, $end),
                    'levels' => [],
                ];
            }

            $classesByLevel = [];

            foreach ($examen->getClasse() as $classe) {
                $levelName = $classe->getNiveau()?->getName() ?? 'Sans niveau';
                $classesByLevel[$levelName][] = $classe->getName();
            }

            foreach ($classesByLevel as $levelName => $classes) {
                $grouped[$dateKey][$slotKey]['levels'][$levelName][] = [
                    'id' => $examen->getId(),
                    'matiere' => $examen->getMatiere()?->getNom() ?? 'Matière non définie',
                    'classes' => array_values(array_unique($classes)),
                ];
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
            }
            unset($slotData);
        }
        unset($slots);

        return $grouped;
    }

    /**
     * Construit des lignes de tableau (date + classes) inspirees du programme de devoirs.
     *
     * @param array<string, array<int, array{label: string, levels: array<string, array<int, array{id: int|null, matiere: string, classes: string[]}>>}>> $groupedExamens
     * @param string[] $orderedLevels
     * @return array<int, array{date: string, slot: string, cells: array<string, array<int, array{id: int|null, label: string}>>}>
     */
    private function buildProgramRows(array $groupedExamens, array $orderedLevels): array
    {
        $rows = [];

        foreach ($groupedExamens as $date => $slots) {
            foreach ($slots as $slot) {
                $cells = [];

                foreach ($orderedLevels as $level) {
                    $cells[$level] = [];

                    if (!isset($slot['levels'][$level])) {
                        continue;
                    }

                    foreach ($slot['levels'][$level] as $exam) {
                        $cells[$level][] = [
                            'id' => $exam['id'],
                            'label' => $exam['matiere'],
                        ];
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
     * Construit l'entete personnalise a partir des parametres de filtre.
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

        $defaultTitle = 'PROGRAMME DES DEVOIRS';
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
