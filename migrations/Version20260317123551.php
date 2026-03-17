<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317123551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY `FK_proposition_auteur`');
        $this->addSql('ALTER TABLE proposition CHANGE est_lu_par_admin est_lu_par_admin TINYINT NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_proposition_auteur ON proposition');
        $this->addSql('CREATE INDEX IDX_C7CDC35360BB6FE6 ON proposition (auteur_id)');
        $this->addSql('ALTER TABLE proposition ADD CONSTRAINT `FK_proposition_auteur` FOREIGN KEY (auteur_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY FK_C7CDC35360BB6FE6');
        $this->addSql('ALTER TABLE proposition CHANGE est_lu_par_admin est_lu_par_admin TINYINT DEFAULT 0 NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_c7cdc35360bb6fe6 ON proposition');
        $this->addSql('CREATE INDEX IDX_proposition_auteur ON proposition (auteur_id)');
        $this->addSql('ALTER TABLE proposition ADD CONSTRAINT FK_C7CDC35360BB6FE6 FOREIGN KEY (auteur_id) REFERENCES user (id) ON DELETE CASCADE');
    }
}
