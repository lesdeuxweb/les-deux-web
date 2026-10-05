<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261002111453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_contact ADD offre_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message_contact ADD CONSTRAINT FK_DCEADC344CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_DCEADC344CC8505A ON message_contact (offre_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_contact DROP FOREIGN KEY FK_DCEADC344CC8505A');
        $this->addSql('DROP INDEX IDX_DCEADC344CC8505A ON message_contact');
        $this->addSql('ALTER TABLE message_contact DROP offre_id');
    }
}
