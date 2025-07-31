<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250727125040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_user ADD roles JSON NOT NULL');
        $this->addSql('ALTER TABLE app_user DROP role');
        $this->addSql('ALTER TABLE books ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE books ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE books ALTER user_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN books.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN books.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE books ADD username VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE books ALTER id TYPE UUID');
        $this->addSql('ALTER TABLE books ALTER id SET DEFAULT \'uuid_generate_v4()\'');
        $this->addSql('ALTER TABLE books ALTER user_id TYPE UUID');
        $this->addSql('COMMENT ON COLUMN books.id IS NULL');
        $this->addSql('COMMENT ON COLUMN books.user_id IS NULL');
        $this->addSql('ALTER TABLE app_user ADD role VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE app_user DROP roles');
        $this->addSql('ALTER TABLE books DROP username');
    }
}
