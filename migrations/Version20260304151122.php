<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304151122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enseignement DROP FOREIGN KEY `FK_BD310CC41807E1D`');
        $this->addSql('ALTER TABLE enseignement DROP FOREIGN KEY `FK_BD310CCB462FB2A`');
        $this->addSql('ALTER TABLE enseignement DROP FOREIGN KEY `FK_BD310CCD614E59F`');
        $this->addSql('ALTER TABLE enseignement CHANGE teacher_id teacher_id INT NOT NULL, CHANGE class_name_id class_name_id INT NOT NULL, CHANGE matter_id matter_id INT NOT NULL');
        $this->addSql('ALTER TABLE enseignement ADD CONSTRAINT FK_BD310CC41807E1D FOREIGN KEY (teacher_id) REFERENCES teatchers (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enseignement ADD CONSTRAINT FK_BD310CCB462FB2A FOREIGN KEY (class_name_id) REFERENCES class_name (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE enseignement ADD CONSTRAINT FK_BD310CCD614E59F FOREIGN KEY (matter_id) REFERENCES matter (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enseignement DROP FOREIGN KEY FK_BD310CC41807E1D');
        $this->addSql('ALTER TABLE enseignement DROP FOREIGN KEY FK_BD310CCB462FB2A');
        $this->addSql('ALTER TABLE enseignement DROP FOREIGN KEY FK_BD310CCD614E59F');
        $this->addSql('ALTER TABLE enseignement CHANGE teacher_id teacher_id INT DEFAULT NULL, CHANGE class_name_id class_name_id INT DEFAULT NULL, CHANGE matter_id matter_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE enseignement ADD CONSTRAINT `FK_BD310CC41807E1D` FOREIGN KEY (teacher_id) REFERENCES teatchers (id)');
        $this->addSql('ALTER TABLE enseignement ADD CONSTRAINT `FK_BD310CCB462FB2A` FOREIGN KEY (class_name_id) REFERENCES class_name (id)');
        $this->addSql('ALTER TABLE enseignement ADD CONSTRAINT `FK_BD310CCD614E59F` FOREIGN KEY (matter_id) REFERENCES matter (id)');
    }
}
