<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261008215359 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create project and link tables';
    }

    public function isTransactional(): bool
    {
        // MariaDB DDL commits implicitly, so a wrapping transaction can't roll it back.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE link (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(2048) NOT NULL, icon VARCHAR(64) NOT NULL, project_id INT NOT NULL, INDEX IDX_36AC99F1166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, tags JSON NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE link ADD CONSTRAINT FK_36AC99F1166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE link DROP FOREIGN KEY FK_36AC99F1166D1F9C');
        $this->addSql('DROP TABLE link');
        $this->addSql('DROP TABLE project');
    }
}
