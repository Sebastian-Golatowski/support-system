<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230619110244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log ADD by_who_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE log ADD CONSTRAINT FK_8F3F68C5B73E54EB FOREIGN KEY (by_who_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_8F3F68C5B73E54EB ON log (by_who_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE log DROP FOREIGN KEY FK_8F3F68C5B73E54EB');
        $this->addSql('DROP INDEX IDX_8F3F68C5B73E54EB ON log');
        $this->addSql('ALTER TABLE log DROP by_who_id');
    }
}
