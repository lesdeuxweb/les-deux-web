<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005113748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message_contact (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, entreprise VARCHAR(150) DEFAULT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL, traite TINYINT NOT NULL, offre_id INT DEFAULT NULL, INDEX IDX_DCEADC344CC8505A (offre_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, categorie VARCHAR(20) NOT NULL, sur_titre VARCHAR(60) DEFAULT NULL, en_carte TINYINT NOT NULL, badge VARCHAR(30) DEFAULT NULL, nom VARCHAR(100) NOT NULL, accroche VARCHAR(255) NOT NULL, prix_a_partir_de INT DEFAULT NULL, a_partir_de TINYINT NOT NULL, prix_suffixe VARCHAR(20) DEFAULT NULL, points_forts JSON NOT NULL, position INT NOT NULL, publie TINYINT NOT NULL, slug VARCHAR(180) NOT NULL, UNIQUE INDEX UNIQ_AF86866F989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE option_tarifaire (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) NOT NULL, prix VARCHAR(60) NOT NULL, position INT NOT NULL, publie TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE message_contact ADD CONSTRAINT FK_DCEADC344CC8505A FOREIGN KEY (offre_id) REFERENCES offre (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_contact DROP FOREIGN KEY FK_DCEADC344CC8505A');
        $this->addSql('DROP TABLE message_contact');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE option_tarifaire');
        $this->addSql('DROP TABLE user');
    }
}
