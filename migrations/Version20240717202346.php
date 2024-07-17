<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240717202346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, color VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_article (category_id INT NOT NULL, article_id INT NOT NULL, INDEX IDX_C5E24E1812469DE2 (category_id), INDEX IDX_C5E24E187294869C (article_id), PRIMARY KEY(category_id, article_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_presse (category_id INT NOT NULL, presse_id INT NOT NULL, INDEX IDX_559FBB6C12469DE2 (category_id), INDEX IDX_559FBB6C876DDCB7 (presse_id), PRIMARY KEY(category_id, presse_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category_podcast (category_id INT NOT NULL, podcast_id INT NOT NULL, INDEX IDX_103045C312469DE2 (category_id), INDEX IDX_103045C3786136AB (podcast_id), PRIMARY KEY(category_id, podcast_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE category_article ADD CONSTRAINT FK_C5E24E1812469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_article ADD CONSTRAINT FK_C5E24E187294869C FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_presse ADD CONSTRAINT FK_559FBB6C12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_presse ADD CONSTRAINT FK_559FBB6C876DDCB7 FOREIGN KEY (presse_id) REFERENCES presse (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_podcast ADD CONSTRAINT FK_103045C312469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_podcast ADD CONSTRAINT FK_103045C3786136AB FOREIGN KEY (podcast_id) REFERENCES podcast (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category_article DROP FOREIGN KEY FK_C5E24E1812469DE2');
        $this->addSql('ALTER TABLE category_article DROP FOREIGN KEY FK_C5E24E187294869C');
        $this->addSql('ALTER TABLE category_presse DROP FOREIGN KEY FK_559FBB6C12469DE2');
        $this->addSql('ALTER TABLE category_presse DROP FOREIGN KEY FK_559FBB6C876DDCB7');
        $this->addSql('ALTER TABLE category_podcast DROP FOREIGN KEY FK_103045C312469DE2');
        $this->addSql('ALTER TABLE category_podcast DROP FOREIGN KEY FK_103045C3786136AB');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE category_article');
        $this->addSql('DROP TABLE category_presse');
        $this->addSql('DROP TABLE category_podcast');
    }
}
