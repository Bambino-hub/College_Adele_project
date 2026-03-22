<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les metadonnees personnel PDF sur les enseignants (fonction, cycle PDF, autorisation de surveillance)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE teatchers ADD fonction VARCHAR(255) DEFAULT NULL, ADD pdf_cycle VARCHAR(3) DEFAULT NULL, ADD can_supervise TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE teatchers DROP fonction, DROP pdf_cycle, DROP can_supervise');
    }
}
