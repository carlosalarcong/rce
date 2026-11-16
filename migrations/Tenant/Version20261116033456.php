<?php

declare(strict_types=1);

namespace DoctrineMigrations\Tenant;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Bandeja de encargos (outbox) de la cola, en la base de cada tenant.
 * Ver src/Integration/Messaging/CLAUDE.md.
 */
final class Version20261116033456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Outbox de la cola de encargos (Integration/Messaging)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE outbox_message (
                id UUID NOT NULL,
                tenant VARCHAR(63) NOT NULL,
                message_class VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                headers JSONB NOT NULL,
                status VARCHAR(16) NOT NULL,
                attempts INT DEFAULT 0 NOT NULL,
                available_at TIMESTAMP(6) WITH TIME ZONE NOT NULL,
                notified_at TIMESTAMP(6) WITH TIME ZONE DEFAULT NULL,
                locked_at TIMESTAMP(6) WITH TIME ZONE DEFAULT NULL,
                last_error VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP(6) WITH TIME ZONE NOT NULL,
                updated_at TIMESTAMP(6) WITH TIME ZONE NOT NULL,
                PRIMARY KEY (id),
                CONSTRAINT outbox_message_status_check CHECK (status IN ('pending', 'processing', 'done', 'failed')),
                CONSTRAINT outbox_message_attempts_check CHECK (attempts >= 0)
            )
            SQL);
        $this->addSql('CREATE INDEX outbox_message_due_idx ON outbox_message (status, available_at)');
        $this->addSql('CREATE INDEX outbox_message_locked_idx ON outbox_message (locked_at) WHERE status = \'processing\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE outbox_message');
    }
}
