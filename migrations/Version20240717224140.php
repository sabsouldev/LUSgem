<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240717224140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ADD slug VARCHAR(255) NOT NULL, ADD featured_text VARCHAR(100) NOT NULL, DROP created_at');
        $this->addSql('ALTER TABLE podcast ADD slug VARCHAR(255) NOT NULL, ADD featured_text VARCHAR(100) NOT NULL, DROP created_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP slug, DROP featured_text');
        $this->addSql('ALTER TABLE podcast ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP slug, DROP featured_text');
    }
}
