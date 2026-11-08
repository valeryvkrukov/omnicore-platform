<?php

declare(strict_types=1);

namespace OmniCore\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add GIN index to customers properties JSONB field for high-performance searching
 */
final class Version20261108130630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add GIN index to customers properties JSONB field for high-performance searching';
    }

    public function up(Schema $schema): void
    {
        // Use jsonb_path_ops for high-performance searching with JSONB fields using @>, ?, LIKE operators
        $this->addSql('CREATE INDEX idx_customers_properties_gin ON customers USING gin (properties jsonb_path_ops);');

        // Add B-Tree indexes on expressions to optimize point lookups for fields accessed via the ->> operator ('city' and 'age')
        $this->addSql("CREATE INDEX idx_customers_properties_city ON customers ((properties->>'city'));");
        $this->addSql("CREATE INDEX idx_customers_properties_age ON customers (CAST(properties->>'age' AS integer));");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_customers_properties_gin;');
        $this->addSql('DROP INDEX idx_customers_properties_city;');
        $this->addSql('DROP INDEX idx_customers_properties_age;');
    }
}
