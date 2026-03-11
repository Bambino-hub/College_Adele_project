<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225033717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE examen (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, heurs_debut TIME NOT NULL, heure_fin TIME NOT NULL, matiere_id INT DEFAULT NULL, classe_id INT DEFAULT NULL, INDEX IDX_514C8FECF46CD258 (matiere_id), INDEX IDX_514C8FEC8F5EA509 (classe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE surveillance (id INT AUTO_INCREMENT NOT NULL, salle VARCHAR(255) DEFAULT NULL, examen_id INT NOT NULL, enseignant_id INT NOT NULL, classe_id INT NOT NULL, INDEX IDX_C17BAD5B5C8659A (examen_id), INDEX IDX_C17BAD5BE455FCC0 (enseignant_id), INDEX IDX_C17BAD5B8F5EA509 (classe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE examen ADD CONSTRAINT FK_514C8FECF46CD258 FOREIGN KEY (matiere_id) REFERENCES matter (id)');
        $this->addSql('ALTER TABLE examen ADD CONSTRAINT FK_514C8FEC8F5EA509 FOREIGN KEY (classe_id) REFERENCES class_name (id)');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT FK_C17BAD5B5C8659A FOREIGN KEY (examen_id) REFERENCES examen (id)');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT FK_C17BAD5BE455FCC0 FOREIGN KEY (enseignant_id) REFERENCES teatchers (id)');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT FK_C17BAD5B8F5EA509 FOREIGN KEY (classe_id) REFERENCES class_name (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE examen DROP FOREIGN KEY FK_514C8FECF46CD258');
        $this->addSql('ALTER TABLE examen DROP FOREIGN KEY FK_514C8FEC8F5EA509');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY FK_C17BAD5B5C8659A');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY FK_C17BAD5BE455FCC0');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY FK_C17BAD5B8F5EA509');
        $this->addSql('DROP TABLE examen');
        $this->addSql('DROP TABLE surveillance');
    }
}
