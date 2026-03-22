<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311094734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE class_name ADD serie_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE class_name ADD CONSTRAINT FK_EA5E4949D94388BD FOREIGN KEY (serie_id) REFERENCES serie (id)');
        $this->addSql('CREATE INDEX IDX_EA5E4949D94388BD ON class_name (serie_id)');
        $this->addSql('ALTER TABLE niveau ADD cycle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE niveau ADD CONSTRAINT FK_4BDFF36B5EC1162 FOREIGN KEY (cycle_id) REFERENCES cycles (id)');
        $this->addSql('CREATE INDEX IDX_4BDFF36B5EC1162 ON niveau (cycle_id)');
        $this->addSql('ALTER TABLE serie ADD cycle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE serie ADD CONSTRAINT FK_AA3A93345EC1162 FOREIGN KEY (cycle_id) REFERENCES cycles (id)');
        $this->addSql('CREATE INDEX IDX_AA3A93345EC1162 ON serie (cycle_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE class_name DROP FOREIGN KEY FK_EA5E4949D94388BD');
        $this->addSql('DROP INDEX IDX_EA5E4949D94388BD ON class_name');
        $this->addSql('ALTER TABLE class_name DROP serie_id');
        $this->addSql('ALTER TABLE niveau DROP FOREIGN KEY FK_4BDFF36B5EC1162');
        $this->addSql('DROP INDEX IDX_4BDFF36B5EC1162 ON niveau');
        $this->addSql('ALTER TABLE niveau DROP cycle_id');
        $this->addSql('ALTER TABLE serie DROP FOREIGN KEY FK_AA3A93345EC1162');
        $this->addSql('DROP INDEX IDX_AA3A93345EC1162 ON serie');
        $this->addSql('ALTER TABLE serie DROP cycle_id');
    }
}
