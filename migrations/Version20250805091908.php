<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805091908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id UUID NOT NULL, username VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, roles JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN app_user.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE books (id UUID NOT NULL, title VARCHAR(200) NOT NULL, note INT DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, comment TEXT DEFAULT NULL, user_id UUID NOT NULL, username VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN books.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN books.user_id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE books');
    }
}
