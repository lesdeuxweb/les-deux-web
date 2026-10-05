<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261003110036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE option_tarifaire (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(120) NOT NULL, prix VARCHAR(60) NOT NULL, position INT NOT NULL, publie TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE offre ADD categorie VARCHAR(20) NOT NULL, ADD sur_titre VARCHAR(60) DEFAULT NULL, ADD badge VARCHAR(30) DEFAULT NULL, ADD sur_accueil TINYINT NOT NULL, ADD a_partir_de TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE option_tarifaire');
        $this->addSql('ALTER TABLE offre DROP categorie, DROP sur_titre, DROP badge, DROP sur_accueil, DROP a_partir_de');
    }
}
