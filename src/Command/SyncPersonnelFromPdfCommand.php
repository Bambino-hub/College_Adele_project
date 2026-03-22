<?php

namespace App\Command;

use App\Entity\Matter;
use App\Entity\Teatchers;
use App\Repository\MatterRepository;
use App\Repository\TeatchersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(name: 'app:sync-personnel-from-pdf', description: 'Synchronise la liste du personnel et les matieres a partir du fichier YAML extrait du PDF.')]
class SyncPersonnelFromPdfCommand extends Command
{
    private const SOURCE_FILE = 'config/data/personnel_college_adele_2025_2026.yaml';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TeatchersRepository $teatchersRepository,
        private readonly MatterRepository $matterRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sourcePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . self::SOURCE_FILE;

        if (!is_file($sourcePath)) {
            $output->writeln('<error>Fichier introuvable: ' . self::SOURCE_FILE . '</error>');

            return Command::FAILURE;
        }

        $data = Yaml::parseFile($sourcePath);

        if (!is_array($data)) {
            $output->writeln('<error>Le fichier YAML est invalide.</error>');

            return Command::FAILURE;
        }

        $matterNames = $this->extractMatterNames($data);
        $personnels = is_array($data['personnels'] ?? null) ? $data['personnels'] : [];

        $deletedTeachers = $this->purgeTeachers();

        $createdMatters = $this->syncMatters($matterNames);
        $createdTeachers = $this->syncTeachers($personnels);

        $this->entityManager->flush();

        $output->writeln('<info>Synchronisation terminee.</info>');
        $output->writeln(sprintf('Personnels supprimes: %d', $deletedTeachers));
        $output->writeln(sprintf('Matieres creees: %d', $createdMatters));
        $output->writeln(sprintf('Personnels crees: %d', $createdTeachers));

        return Command::SUCCESS;
    }

    private function purgeTeachers(): int
    {
        $teachers = $this->teatchersRepository->findAll();

        if ($teachers === []) {
            return 0;
        }

        // Les stagiaires peuvent référencer un encadrant enseignant.
        $this->entityManager->getConnection()->executeStatement('UPDATE stagiaire SET encadrant_id = NULL');

        foreach ($teachers as $teacher) {
            $this->entityManager->remove($teacher);
        }

        $this->entityManager->flush();

        return count($teachers);
    }

    /**
     * @return string[]
     */
    private function extractMatterNames(array $data): array
    {
        $matieres = [];

        foreach (($data['matieres'] ?? []) as $matiere) {
            if (is_string($matiere) && trim($matiere) !== '') {
                $matieres[] = $this->canonicalizeMatterName($matiere);
            }
        }

        foreach (($data['personnels'] ?? []) as $person) {
            if (!is_array($person)) {
                continue;
            }

            foreach (($person['disciplines'] ?? []) as $discipline) {
                if (is_string($discipline) && trim($discipline) !== '') {
                    $matieres[] = $this->canonicalizeMatterName($discipline);
                }
            }
        }

        $matieres = array_values(array_unique($matieres));
        sort($matieres);

        return $matieres;
    }

    /**
     * @param string[] $matterNames
     */
    private function syncMatters(array $matterNames): int
    {
        $created = 0;

        foreach ($matterNames as $matterName) {
            $existing = $this->matterRepository->findOneBy(['nom' => $matterName]);

            if ($existing !== null) {
                continue;
            }

            $matter = new Matter();
            $matter->setNom($matterName);
            $this->entityManager->persist($matter);
            $created++;
        }

        $this->mergeMatterAlias('PHILLO', 'PHILO');

        return $created;
    }

    /**
     * @param array<int, mixed> $personnels
     */
    private function syncTeachers(array $personnels): int
    {
        $created = 0;

        foreach ($personnels as $person) {
            if (!is_array($person)) {
                continue;
            }

            $fullName = trim((string) ($person['full_name'] ?? ''));
            if ($fullName === '') {
                continue;
            }

            if (!(bool) ($person['can_supervise'] ?? true)) {
                continue;
            }

            [$lastname, $firstname] = $this->splitName($fullName);

            $teacher = new Teatchers();
            $this->entityManager->persist($teacher);
            $created++;

            $teacher->setLastname($lastname);
            $teacher->setFirstname($firstname);
            $teacher->setSexe($this->nullableTrim($person['sexe'] ?? null));
            $teacher->setMatricule($this->nullableTrim($person['matricule'] ?? null));
            $teacher->setStatut($this->nullableTrim($person['statut'] ?? null));
            $teacher->setDisciplines($this->buildDisciplinesLabel($person));
            $teacher->setCanSupervise(true);

            $cycle = $this->nullableTrim($person['cycle'] ?? null);
            if (!in_array($cycle, [Teatchers::PDF_CYCLE_1, Teatchers::PDF_CYCLE_2, Teatchers::PDF_CYCLE_BOTH], true)) {
                $cycle = Teatchers::PDF_CYCLE_BOTH;
            }
            $teacher->setPdfCycle($cycle);

            $phone = preg_replace('/\D+/', '', (string) ($person['phone'] ?? ''));
            $teacher->setPhoneNumber($phone !== '' ? $phone : '00000000');
        }

        return $created;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if ($parts === []) {
            return ['INCONNU', '-'];
        }

        $lastname = array_shift($parts);
        $firstname = implode(' ', $parts);

        if ($firstname === '') {
            $firstname = '-';
        }

        return [$lastname, $firstname];
    }

    private function normalizeName(string $name): string
    {
        $name = trim(mb_strtolower($name));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name) ?? $name;

        return trim($name);
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function buildDisciplinesLabel(array $person): ?string
    {
        $disciplines = $person['disciplines'] ?? null;

        if (!is_array($disciplines) || $disciplines === []) {
            return null;
        }

        $items = [];

        foreach ($disciplines as $discipline) {
            if (!is_string($discipline) || trim($discipline) === '') {
                continue;
            }

            $items[] = $this->canonicalizeMatterName($discipline);
        }

        if ($items === []) {
            return null;
        }

        return implode(' / ', array_values(array_unique($items)));
    }

    private function canonicalizeMatterName(string $matterName): string
    {
        $matterName = strtoupper(trim($matterName));

        return match ($matterName) {
            'PHILLO' => 'PHILO',
            default => $matterName,
        };
    }

    private function mergeMatterAlias(string $alias, string $canonical): void
    {
        $aliasMatter = $this->matterRepository->findOneBy(['nom' => $alias]);

        if ($aliasMatter === null) {
            return;
        }

        $canonicalMatter = $this->matterRepository->findOneBy(['nom' => $canonical]);

        if ($canonicalMatter === null) {
            $canonicalMatter = new Matter();
            $canonicalMatter->setNom($canonical);
            $this->entityManager->persist($canonicalMatter);
            $this->entityManager->flush();
        }

        $connection = $this->entityManager->getConnection();

        $connection->executeStatement(
            'UPDATE enseignement SET matter_id = :canonicalId WHERE matter_id = :aliasId',
            ['canonicalId' => $canonicalMatter->getId(), 'aliasId' => $aliasMatter->getId()]
        );
        $connection->executeStatement(
            'UPDATE examen SET matiere_id = :canonicalId WHERE matiere_id = :aliasId',
            ['canonicalId' => $canonicalMatter->getId(), 'aliasId' => $aliasMatter->getId()]
        );
        $connection->executeStatement(
            'UPDATE stagiaire SET matiere_de_stage_id = :canonicalId WHERE matiere_de_stage_id = :aliasId',
            ['canonicalId' => $canonicalMatter->getId(), 'aliasId' => $aliasMatter->getId()]
        );

        $this->entityManager->remove($aliasMatter);
    }
}
