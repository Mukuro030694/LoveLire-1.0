<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250726144008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        $this->addSql('
            CREATE TABLE books (
                id UUID NOT NULL PRIMARY KEY DEFAULT uuid_generate_v4(),
                title VARCHAR(200) NOT NULL,
                note INT DEFAULT NULL,
                status VARCHAR(50) DEFAULT NULL,
                comment TEXT DEFAULT NULL,
                user_id UUID NOT NULL
            )
        ');
        $this->addSql('CREATE TABLE app_user (id UUID NOT NULL, username VARCHAR(50) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN app_user.id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE books');
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE app_user');
    }
}
