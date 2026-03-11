<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225041058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE examen_class_name (examen_id INT NOT NULL, class_name_id INT NOT NULL, INDEX IDX_121ED9965C8659A (examen_id), INDEX IDX_121ED996B462FB2A (class_name_id), PRIMARY KEY (examen_id, class_name_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE examen_class_name ADD CONSTRAINT FK_121ED9965C8659A FOREIGN KEY (examen_id) REFERENCES examen (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE examen_class_name ADD CONSTRAINT FK_121ED996B462FB2A FOREIGN KEY (class_name_id) REFERENCES class_name (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE examen DROP FOREIGN KEY `FK_514C8FEC8F5EA509`');
        $this->addSql('DROP INDEX IDX_514C8FEC8F5EA509 ON examen');
        $this->addSql('ALTER TABLE examen DROP classe_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE examen_class_name DROP FOREIGN KEY FK_121ED9965C8659A');
        $this->addSql('ALTER TABLE examen_class_name DROP FOREIGN KEY FK_121ED996B462FB2A');
        $this->addSql('DROP TABLE examen_class_name');
        $this->addSql('ALTER TABLE examen ADD classe_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE examen ADD CONSTRAINT `FK_514C8FEC8F5EA509` FOREIGN KEY (classe_id) REFERENCES class_name (id)');
        $this->addSql('CREATE INDEX IDX_514C8FEC8F5EA509 ON examen (classe_id)');
    }
}
