<?php

declare(strict_types=1);

namespace DoctrineMigrations\Main;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261116223628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cuentas de personas y enlaces de recuperación de contraseña (Platform/Identity, ADR 0005)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE password_reset_token (account_id UUID NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6B7BA4B6B3BC57DA ON password_reset_token (token_hash)');
        $this->addSql('CREATE INDEX password_reset_token_account_idx ON password_reset_token (account_id)');
        $this->addSql('CREATE TABLE user_account (email VARCHAR(180) NOT NULL, display_name VARCHAR(120) NOT NULL, password_hash VARCHAR(255) NOT NULL, status VARCHAR(16) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_253B48AEE7927C74 ON user_account (email)');
        $this->addSql("ALTER TABLE user_account ADD CONSTRAINT user_account_status_check CHECK (status IN ('active', 'suspended'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE user_account');
    }
}
