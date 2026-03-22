<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la catégorie de personnel enseignant ou stagiaire';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE teatchers ADD person_type VARCHAR(20) DEFAULT 'enseignant' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE teatchers DROP person_type');
    }
}
