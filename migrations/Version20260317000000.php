<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de la table proposition (entite Doctrine)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE proposition (
                id INT AUTO_INCREMENT NOT NULL,
                auteur_id INT NOT NULL,
                titre VARCHAR(200) NOT NULL,
                contenu LONGTEXT NOT NULL,
                est_lu_par_admin TINYINT(1) NOT NULL DEFAULT 0,
                date_creation DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_proposition_auteur (auteur_id),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");

        $this->addSql('
            ALTER TABLE proposition
            ADD CONSTRAINT FK_proposition_auteur
            FOREIGN KEY (auteur_id) REFERENCES user (id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proposition DROP FOREIGN KEY FK_proposition_auteur');
        $this->addSql('DROP TABLE proposition');
    }
}
