<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sépare les stagiaires des enseignants et rattache les surveillances au nouveau modèle';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stagiaire (id INT AUTO_INCREMENT NOT NULL, matiere_de_stage_id INT DEFAULT NULL, encadrant_id INT DEFAULT NULL, cycle_id INT DEFAULT NULL, lastname VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, phone_number VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, INDEX IDX_11DC88AA763104D4 (matiere_de_stage_id), INDEX IDX_11DC88AA44F5D008 (encadrant_id), INDEX IDX_11DC88AACF5E72D (cycle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE stagiaire ADD CONSTRAINT FK_11DC88AA763104D4 FOREIGN KEY (matiere_de_stage_id) REFERENCES matter (id)');
        $this->addSql('ALTER TABLE stagiaire ADD CONSTRAINT FK_11DC88AA44F5D008 FOREIGN KEY (encadrant_id) REFERENCES teatchers (id)');
        $this->addSql('ALTER TABLE stagiaire ADD CONSTRAINT FK_11DC88AACF5E72D FOREIGN KEY (cycle_id) REFERENCES cycles (id)');

        $this->addSql('ALTER TABLE surveillance ADD stagiaire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE surveillance CHANGE enseignant_id enseignant_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_C17BAD5B827C1D3C ON surveillance (stagiaire_id)');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT FK_C17BAD5B827C1D3C FOREIGN KEY (stagiaire_id) REFERENCES stagiaire (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO stagiaire (id, lastname, firstname, phone_number, email, matiere_de_stage_id, encadrant_id, cycle_id)
            SELECT t.id, t.lastname, t.firstname, t.phone_number, t.email, stage_data.matter_id, NULL, stage_data.cycle_id
            FROM teatchers t
            LEFT JOIN (
                SELECT e.teacher_id, MIN(e.matter_id) AS matter_id, MIN(n.cycle_id) AS cycle_id
                FROM enseignement e
                INNER JOIN class_name c ON c.id = e.class_name_id
                INNER JOIN niveau n ON n.id = c.niveau_id
                GROUP BY e.teacher_id
            ) AS stage_data ON stage_data.teacher_id = t.id
            WHERE t.person_type = 'stagiaire'");

        $this->addSql("UPDATE surveillance s
            INNER JOIN teatchers t ON t.id = s.enseignant_id
            SET s.stagiaire_id = s.enseignant_id, s.enseignant_id = NULL
            WHERE t.person_type = 'stagiaire'");

        $this->addSql("DELETE e FROM enseignement e INNER JOIN teatchers t ON t.id = e.teacher_id WHERE t.person_type = 'stagiaire'");
        $this->addSql("DELETE FROM teatchers WHERE person_type = 'stagiaire'");
        $this->addSql('ALTER TABLE teatchers DROP person_type');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('La séparation des stagiaires en entité dédiée ne peut pas être annulée automatiquement.');
    }
}
