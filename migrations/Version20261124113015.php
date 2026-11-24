<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261124113015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE category (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              name VARCHAR(80) NOT NULL,
              description VARCHAR(240) NOT NULL
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C15E237E06 ON category (name)');
        $this->addSql(<<<'SQL'
            CREATE TABLE repair_service (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              title VARCHAR(120) NOT NULL,
              search_title VARCHAR(120) NOT NULL,
              description CLOB NOT NULL,
              price INTEGER NOT NULL,
              duration INTEGER NOT NULL,
              available BOOLEAN NOT NULL,
              category_id INTEGER NOT NULL,
              CONSTRAINT FK_7F26CF4112469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
            )
        SQL);
        $this->addSql('CREATE INDEX IDX_7F26CF4112469DE2 ON repair_service (category_id)');
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (
              id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
              email VARCHAR(180) NOT NULL,
              roles CLOB NOT NULL,
              password VARCHAR(255) NOT NULL
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE repair_service');
        $this->addSql('DROP TABLE "user"');
    }
}
