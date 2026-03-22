<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313201000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rattache les niveaux aux cycles et crée les classes du lycée';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE niveau SET cycle_id = 1 WHERE name IN ('6eme', '5eme', '4eme', '3eme')");
        $this->addSql("UPDATE niveau SET cycle_id = 2 WHERE name IN ('2nde CD', '2nde A', '1ere D', '1ere A', 'Tle C', 'Tle D', 'Tle A')");

        $this->insertClassIfMissing('2nde CD1', '2nde CD', null);
        $this->insertClassIfMissing('2nde CD2', '2nde CD', null);
        $this->insertClassIfMissing('2nde CD3', '2nde CD', null);
        $this->insertClassIfMissing('2nde A', '2nde A', 'A');
        $this->insertClassIfMissing('1ere D1', '1ere D', 'D');
        $this->insertClassIfMissing('1ere D2', '1ere D', 'D');
        $this->insertClassIfMissing('1ere A', '1ere A', 'A');
        $this->insertClassIfMissing('Tle C', 'Tle C', 'C');
        $this->insertClassIfMissing('Tle D1', 'Tle D', 'D');
        $this->insertClassIfMissing('Tle D2', 'Tle D', 'D');
        $this->insertClassIfMissing('Tle A1', 'Tle A', 'A');
        $this->insertClassIfMissing('Tle A2', 'Tle A', 'A');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM class_name WHERE name IN ('2nde CD1', '2nde CD2', '2nde CD3', '2nde A', '1ere D1', '1ere D2', '1ere A', 'Tle C', 'Tle D1', 'Tle D2', 'Tle A1', 'Tle A2')");
    }

    private function insertClassIfMissing(string $className, string $niveauName, ?string $serieName): void
    {
        if ($serieName === null) {
            $this->addSql(sprintf(
                "INSERT INTO class_name (name, niveau_id, serie_id)
                SELECT '%s', n.id, NULL
                FROM niveau n
                WHERE n.name = '%s'
                  AND NOT EXISTS (SELECT 1 FROM class_name c WHERE c.name = '%s')",
                $className,
                $niveauName,
                $className
            ));

            return;
        }

        $this->addSql(sprintf(
            "INSERT INTO class_name (name, niveau_id, serie_id)
            SELECT '%s', n.id, s.id
            FROM niveau n
            LEFT JOIN serie s ON s.name = '%s'
            WHERE n.name = '%s'
              AND NOT EXISTS (SELECT 1 FROM class_name c WHERE c.name = '%s')",
            $className,
            $serieName,
            $niveauName,
            $className
        ));
    }
}
