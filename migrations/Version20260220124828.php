<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220124828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE class_name (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE enseignement (id INT AUTO_INCREMENT NOT NULL, teacher_id INT DEFAULT NULL, class_name_id INT DEFAULT NULL, matter_id INT DEFAULT NULL, INDEX IDX_BD310CC41807E1D (teacher_id), INDEX IDX_BD310CCB462FB2A (class_name_id), INDEX IDX_BD310CCD614E59F (matter_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE matter (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE enseignement ADD CONSTRAINT FK_BD310CC41807E1D FOREIGN KEY (teacher_id) REFERENCES teatchers (id)');
        $this->addSql('ALTER TABLE enseignement ADD CONSTRAINT FK_BD310CCB462FB2A FOREIGN KEY (class_name_id) REFERENCES class_name (id)');
        $this->addSql('ALTER TABLE enseignement ADD CONSTRAINT FK_BD310CCD614E59F FOREIGN KEY (matter_id) REFERENCES matter (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enseignement DROP FOREIGN KEY FK_BD310CC41807E1D');
        $this->addSql('ALTER TABLE enseignement DROP FOREIGN KEY FK_BD310CCB462FB2A');
        $this->addSql('ALTER TABLE enseignement DROP FOREIGN KEY FK_BD310CCD614E59F');
        $this->addSql('DROP TABLE class_name');
        $this->addSql('DROP TABLE enseignement');
        $this->addSql('DROP TABLE matter');
    }
}
