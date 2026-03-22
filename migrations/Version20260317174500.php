<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317174500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supprime email enseignant et aligne la table teatchers sur les colonnes du PDF (sexe, matricule, statut, disciplines)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE teatchers DROP email');
        $this->addSql('ALTER TABLE teatchers CHANGE fonction statut VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE teatchers ADD sexe VARCHAR(20) DEFAULT NULL, ADD matricule VARCHAR(50) DEFAULT NULL, ADD disciplines VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE teatchers DROP sexe, DROP matricule, DROP disciplines');
        $this->addSql('ALTER TABLE teatchers CHANGE statut fonction VARCHAR(255) DEFAULT NULL');
        $this->addSql("ALTER TABLE teatchers ADD email VARCHAR(255) NOT NULL DEFAULT 'personnel@college-adele.local'");
    }
}
