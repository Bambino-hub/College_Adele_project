<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313195333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stagiaire DROP FOREIGN KEY `FK_11DC88AA44F5D008`');
        $this->addSql('ALTER TABLE stagiaire DROP FOREIGN KEY `FK_11DC88AA763104D4`');
        $this->addSql('ALTER TABLE stagiaire DROP FOREIGN KEY `FK_11DC88AACF5E72D`');
        $this->addSql('DROP INDEX idx_11dc88aa763104d4 ON stagiaire');
        $this->addSql('CREATE INDEX IDX_4F62F73167A95D5B ON stagiaire (matiere_de_stage_id)');
        $this->addSql('DROP INDEX idx_11dc88aa44f5d008 ON stagiaire');
        $this->addSql('CREATE INDEX IDX_4F62F731FEF1BA4 ON stagiaire (encadrant_id)');
        $this->addSql('DROP INDEX idx_11dc88aacf5e72d ON stagiaire');
        $this->addSql('CREATE INDEX IDX_4F62F7315EC1162 ON stagiaire (cycle_id)');
        $this->addSql('ALTER TABLE stagiaire ADD CONSTRAINT `FK_11DC88AA44F5D008` FOREIGN KEY (encadrant_id) REFERENCES teatchers (id)');
        $this->addSql('ALTER TABLE stagiaire ADD CONSTRAINT `FK_11DC88AA763104D4` FOREIGN KEY (matiere_de_stage_id) REFERENCES matter (id)');
        $this->addSql('ALTER TABLE stagiaire ADD CONSTRAINT `FK_11DC88AACF5E72D` FOREIGN KEY (cycle_id) REFERENCES cycles (id)');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY `FK_C17BAD5B827C1D3C`');
        $this->addSql('DROP INDEX idx_c17bad5b827c1d3c ON surveillance');
        $this->addSql('CREATE INDEX IDX_C17BAD5BBBA93DD6 ON surveillance (stagiaire_id)');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT `FK_C17BAD5B827C1D3C` FOREIGN KEY (stagiaire_id) REFERENCES stagiaire (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE stagiaire DROP FOREIGN KEY FK_4F62F73167A95D5B');
        $this->addSql('ALTER TABLE stagiaire DROP FOREIGN KEY FK_4F62F731FEF1BA4');
        $this->addSql('ALTER TABLE stagiaire DROP FOREIGN KEY FK_4F62F7315EC1162');
        $this->addSql('DROP INDEX idx_4f62f73167a95d5b ON stagiaire');
        $this->addSql('CREATE INDEX IDX_11DC88AA763104D4 ON stagiaire (matiere_de_stage_id)');
        $this->addSql('DROP INDEX idx_4f62f731fef1ba4 ON stagiaire');
        $this->addSql('CREATE INDEX IDX_11DC88AA44F5D008 ON stagiaire (encadrant_id)');
        $this->addSql('DROP INDEX idx_4f62f7315ec1162 ON stagiaire');
        $this->addSql('CREATE INDEX IDX_11DC88AACF5E72D ON stagiaire (cycle_id)');
        $this->addSql('ALTER TABLE stagiaire ADD CONSTRAINT FK_4F62F73167A95D5B FOREIGN KEY (matiere_de_stage_id) REFERENCES matter (id)');
        $this->addSql('ALTER TABLE stagiaire ADD CONSTRAINT FK_4F62F731FEF1BA4 FOREIGN KEY (encadrant_id) REFERENCES teatchers (id)');
        $this->addSql('ALTER TABLE stagiaire ADD CONSTRAINT FK_4F62F7315EC1162 FOREIGN KEY (cycle_id) REFERENCES cycles (id)');
        $this->addSql('ALTER TABLE surveillance DROP FOREIGN KEY FK_C17BAD5BBBA93DD6');
        $this->addSql('DROP INDEX idx_c17bad5bbba93dd6 ON surveillance');
        $this->addSql('CREATE INDEX IDX_C17BAD5B827C1D3C ON surveillance (stagiaire_id)');
        $this->addSql('ALTER TABLE surveillance ADD CONSTRAINT FK_C17BAD5BBBA93DD6 FOREIGN KEY (stagiaire_id) REFERENCES stagiaire (id) ON DELETE CASCADE');
    }
}
