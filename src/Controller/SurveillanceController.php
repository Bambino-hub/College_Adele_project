<?php

namespace App\Controller;

use App\Entity\Examen;
use App\Repository\ExamenRepository;
use App\Repository\SurveillanceRepository;
use App\Service\SurveillanceGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;
// use Box\Spout\Writer\XLSX\Writer as XLSXWriter;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class SurveillanceController extends AbstractController
{

    public function __construct(
        private readonly SurveillanceRepository $surveillanceRepository,
        private  readonly ExamenRepository $examenRepository

    ) {}

    #[Route('/exam/generate-surveillance', name: 'exam_generate_surveillance', methods: ['GET'])]
    public function generate(
        // Examen $exam,
        SurveillanceGenerator $generator
    ): JsonResponse {

        try {
            // Appel du service de génération
            $generator->generate($this->examenRepository->findAll()); // ID de l'examen à générer (à adapter)

            return new JsonResponse([
                'success' => true,
                'message' => 'Tableau de surveillance généré avec succès.'
            ]);
        } catch (\Exception $e) {

            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Affiche le tableau de surveillance pour un examen donné
     */
    #[Route('/exam/{id}/surveillance', name: 'exam_surveillance')]
    public function show(Examen $exam)
    {
        $surveillances = $this->surveillanceRepository->findAll();

        return $this->render('surveillance/show.html.twig', [
            'exam' => $exam
        ]);
    }

    #[Route('/surveillance', name: 'surveillance_index')]
    public function index(): Response
    {
        $surveillances = $this->surveillanceRepository->findAll();

        return $this->render('surveillance/index.html.twig', [
            'surveillances' => $surveillances
        ]);
    }

    #[Route('/surveillance/tableau', name: 'surveillance_tableau')]
    public function showSurveillanceTableau(): Response
    {
        $surveillances = $this->surveillanceRepository->findSurveillanceTableau();

        $tableau = [];

        // ordre souhaité des niveaux pour affichage
        $ordreNiveaux = ['6eme', '5eme', '4eme', '3eme'];

        foreach ($surveillances as $s) {
            $date = $s->getExamen()->getDate()->format('Y-m-d');

            foreach ($s->getExamen()->getClasse() as $classe) {
                // on récupère le nom du niveau (6eme, 5eme...)
                $niveau = $classe->getNiveau()->getName();

                if (!isset($tableau[$date][$niveau])) {
                    $tableau[$date][$niveau] = [
                        'niveau' => $niveau,
                        'matiere' => $s->getExamen()->getMatiere()->getNom(),
                        // utilise un tableau associatif pour forcer l'unicité
                        'surveillants' => [],
                    ];
                }

                // enregistrer par clé pour éviter toute répétition
                $nom = $s->getEnseignant()->getLastname();
                $tableau[$date][$niveau]['surveillants'][$nom] = $nom;
            }
        }

        // convertir les tableaux associatifs en listes simples
        foreach ($tableau as $d => &$niveaux) {
            foreach ($niveaux as $niv => &$data) {
                $data['surveillants'] = array_values($data['surveillants']);
            }
            unset($data);
        }
        unset($niveaux);

        // trier les dates par ordre croissant
        ksort($tableau);

        // assurer l'ordre des niveaux à l'intérieur de chaque date
        foreach ($tableau as &$niveaux) {
            uksort($niveaux, function ($a, $b) use ($ordreNiveaux) {
                $posA = array_search($a, $ordreNiveaux);
                $posB = array_search($b, $ordreNiveaux);
                return $posA <=> $posB;
            });
        }
        unset($niveaux);


        return $this->render('surveillance/tableau.html.twig', [
            'tableau' => $tableau
        ]);
    }

    #[Route('/surveillance/tableau/export/{format}', name: 'surveillance_tableau_export')]
    public function exportTableau(string $format): Response
    {
        // Récupérer les données comme dans showSurveillanceTableau
        $surveillances = $this->surveillanceRepository->findSurveillanceTableau();

        $tableau = [];

        $ordreNiveaux = ['6eme', '5eme', '4eme', '3eme'];

        foreach ($surveillances as $s) {
            $date = $s->getExamen()->getDate()->format('Y-m-d');

            foreach ($s->getExamen()->getClasse() as $classe) {
                $niveau = $classe->getNiveau()->getName();

                if (!isset($tableau[$date][$niveau])) {
                    $tableau[$date][$niveau] = [
                        'niveau' => $niveau,
                        'matiere' => $s->getExamen()->getMatiere()->getNom(),
                        'surveillants' => [],
                    ];
                }

                $nom = $s->getEnseignant()->getLastname();
                $tableau[$date][$niveau]['surveillants'][$nom] = $nom;
            }
        }

        foreach ($tableau as $d => &$niveaux) {
            foreach ($niveaux as $niv => &$data) {
                $data['surveillants'] = array_values($data['surveillants']);
            }
            unset($data);
        }
        unset($niveaux);

        ksort($tableau);

        foreach ($tableau as &$niveaux) {
            uksort($niveaux, function ($a, $b) use ($ordreNiveaux) {
                $posA = array_search($a, $ordreNiveaux);
                $posB = array_search($b, $ordreNiveaux);
                return $posA <=> $posB;
            });
        }
        unset($niveaux);

        switch ($format) {
            case 'pdf':
                return $this->exportPdf($tableau);
                // case 'excel':
                //     return $this->exportExcel($tableau);
            case 'word':
                return $this->exportWord($tableau);
            default:
                throw new \InvalidArgumentException('Format non supporté : ' . $format . '. Formats disponibles : pdf, word.');
        }
    }

    private function exportPdf(array $tableau): Response
    {
        $html = $this->renderView('surveillance/tableau_export.html.twig', [
            'tableau' => $tableau
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="tableau_surveillance.pdf"'
        ]);
    }

    // private function exportExcel(array $tableau): Response
    // {
    //     try {
    //         $writer = XLSXWriter::create();
    //         $tempFile = tempnam(sys_get_temp_dir(), 'surveillance');
    //         $writer->openToFile($tempFile);

    //         // En-têtes
    //         $header = ['Date', '6eme', '5eme', '4eme', '3eme'];
    //         $writer->addRow($header);

    //         foreach ($tableau as $date => $niveaux) {
    //             $row = [(string) $date];
    //             foreach (['6eme', '5eme', '4eme', '3eme'] as $niveau) {
    //                 if (isset($niveaux[$niveau])) {
    //                     $matiere = (string) $niveaux[$niveau]['matiere'];
    //                     $surveillants = implode("\n", array_map('strval', $niveaux[$niveau]['surveillants']));
    //                     $row[] = $matiere . "\n" . $surveillants;
    //                 } else {
    //                     $row[] = '';
    //                 }
    //             }
    //             $writer->addRow($row);
    //         }

    //         $writer->close();

    //         $response = $this->file($tempFile, 'tableau_surveillance.xlsx', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    //         unlink($tempFile);
    //         return $response;
    //     } catch (\Exception $e) {
    //         throw new \RuntimeException('Erreur lors de l\'export Excel : ' . $e->getMessage());
    //     }
    // }

    private function exportWord(array $tableau): Response
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle('Tableau de Surveillance', 1);

        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2000)->addText('Date');
        $table->addCell(2000)->addText('6eme');
        $table->addCell(2000)->addText('5eme');
        $table->addCell(2000)->addText('4eme');
        $table->addCell(2000)->addText('3eme');

        foreach ($tableau as $date => $niveaux) {
            $table->addRow();
            $table->addCell()->addText($date);
            foreach (['6eme', '5eme', '4eme', '3eme'] as $niveau) {
                $cell = $table->addCell();
                if (isset($niveaux[$niveau])) {
                    $cell->addText($niveaux[$niveau]['matiere'], ['bold' => true]);
                    $cell->addTextBreak();
                    foreach ($niveaux[$niveau]['surveillants'] as $surveillant) {
                        $cell->addText($surveillant);
                        $cell->addTextBreak();
                    }
                }
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'surveillance_word');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        $response = $this->file($tempFile, 'tableau_surveillance.docx', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        unlink($tempFile);
        return $response;
    }
}
