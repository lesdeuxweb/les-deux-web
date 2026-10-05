<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260930132203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message_contact (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, entreprise VARCHAR(150) DEFAULT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL, traite TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE offre (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, accroche VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, prix_a_partir_de INT DEFAULT NULL, prix_suffixe VARCHAR(20) DEFAULT NULL, points_forts JSON NOT NULL, position INT NOT NULL, publie TINYINT NOT NULL, slug VARCHAR(180) NOT NULL, meta_title VARCHAR(70) DEFAULT NULL, meta_description VARCHAR(160) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_AF86866F989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE realisation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(150) NOT NULL, client VARCHAR(150) DEFAULT NULL, ville VARCHAR(100) DEFAULT NULL, resume VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, url VARCHAR(255) DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, image_alt VARCHAR(150) DEFAULT NULL, date_realisation DATE DEFAULT NULL, publie TINYINT NOT NULL, mis_en_avant TINYINT NOT NULL, slug VARCHAR(180) NOT NULL, meta_title VARCHAR(70) DEFAULT NULL, meta_description VARCHAR(160) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, secteur_id INT DEFAULT NULL, zone_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_EAA5610E989D9B62 (slug), INDEX IDX_EAA5610E9F7E4405 (secteur_id), INDEX IDX_EAA5610E9F2C3FAB (zone_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE secteur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, libelle_cible VARCHAR(150) DEFAULT NULL, accroche VARCHAR(255) DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, position INT NOT NULL, publie TINYINT NOT NULL, slug VARCHAR(180) NOT NULL, meta_title VARCHAR(70) DEFAULT NULL, meta_description VARCHAR(160) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8045251F989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE zone (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, code_departement VARCHAR(3) DEFAULT NULL, accroche VARCHAR(255) DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, position INT NOT NULL, publie TINYINT NOT NULL, slug VARCHAR(180) NOT NULL, meta_title VARCHAR(70) DEFAULT NULL, meta_description VARCHAR(160) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_A0EBC007989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE realisation ADD CONSTRAINT FK_EAA5610E9F7E4405 FOREIGN KEY (secteur_id) REFERENCES secteur (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE realisation ADD CONSTRAINT FK_EAA5610E9F2C3FAB FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE realisation DROP FOREIGN KEY FK_EAA5610E9F7E4405');
        $this->addSql('ALTER TABLE realisation DROP FOREIGN KEY FK_EAA5610E9F2C3FAB');
        $this->addSql('DROP TABLE message_contact');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE realisation');
        $this->addSql('DROP TABLE secteur');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE zone');
    }
}
