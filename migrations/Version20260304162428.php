<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304162428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE class_name CHANGE niveau_id niveau_id INT NOT NULL');
        $this->addSql('ALTER TABLE class_name ADD CONSTRAINT FK_EA5E4949B3E9C81 FOREIGN KEY (niveau_id) REFERENCES niveau (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE examen DROP FOREIGN KEY `FK_514C8FECF46CD258`');
        $this->addSql('ALTER TABLE examen CHANGE matiere_id matiere_id INT NOT NULL');
        $this->addSql('ALTER TABLE examen ADD CONSTRAINT FK_514C8FECF46CD258 FOREIGN KEY (matiere_id) REFERENCES matter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY `FK_C17BAD5B5C8659A`');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY `FK_C17BAD5B8F5EA509`');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY `FK_C17BAD5BE455FCC0`');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT FK_C17BAD5B5C8659A FOREIGN KEY (examen_id) REFERENCES examen (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT FK_C17BAD5B8F5EA509 FOREIGN KEY (classe_id) REFERENCES class_name (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT FK_C17BAD5BE455FCC0 FOREIGN KEY (enseignant_id) REFERENCES teatchers (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE class_name DROP FOREIGN KEY FK_EA5E4949B3E9C81');
        $this->addSql('ALTER TABLE class_name CHANGE niveau_id niveau_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE examen DROP FOREIGN KEY FK_514C8FECF46CD258');
        $this->addSql('ALTER TABLE examen CHANGE matiere_id matiere_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE examen ADD CONSTRAINT `FK_514C8FECF46CD258` FOREIGN KEY (matiere_id) REFERENCES matter (id)');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY FK_C17BAD5B5C8659A');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY FK_C17BAD5BE455FCC0');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY FK_C17BAD5B8F5EA509');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT `FK_C17BAD5B5C8659A` FOREIGN KEY (examen_id) REFERENCES examen (id)');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT `FK_C17BAD5BE455FCC0` FOREIGN KEY (enseignant_id) REFERENCES teatchers (id)');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT `FK_C17BAD5B8F5EA509` FOREIGN KEY (classe_id) REFERENCES class_name (id)');
    }
}
