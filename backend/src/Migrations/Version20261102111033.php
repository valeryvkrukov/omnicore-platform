<?php

declare(strict_types=1);

namespace OmniCore\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261102111033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customers (id UUID NOT NULL, email VARCHAR(255) DEFAULT NULL, properties jsonb, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, tenant_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_customers_properties ON customers (properties)');
        $this->addSql('CREATE INDEX IDX_62534E219033212A ON customers (tenant_id)');
        $this->addSql('CREATE TABLE tenants (id UUID NOT NULL, name VARCHAR(255) NOT NULL, settings JSON NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE customers ADD CONSTRAINT FK_62534E219033212A FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customers DROP CONSTRAINT FK_62534E219033212A');
        $this->addSql('DROP TABLE customers');
        $this->addSql('DROP TABLE tenants');
    }
}
