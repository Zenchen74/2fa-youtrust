<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter le champ google_authenticator_secret à la table user
 */
final class Version20260916153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le champ google_authenticator_secret pour l\'authentification à deux facteurs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD google_authenticator_secret VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP google_authenticator_secret');
    }
}
