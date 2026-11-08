<?php

namespace OmniCore\Command;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'omnicore:seed-large-customers',
    description: 'Seeds the database with a high volume of customer records containing JSONB properties for indexing tests.'
)]
class SeedLargeCustomersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        // 1. Get ID of existing test tenant
        $tenantId = $connection->fetchOne('SELECT id FROM tenants LIMIT 1');
        if (!$tenantId) {
            $io->error('No tenants found in the database. Please seed tenants first.');
            return Command::FAILURE;
        }

        $io->title('Starting Bulk Database Seeding for Customers...');
        $cities = ['Kharkiv', 'Kyiv', 'Lviv', 'Odessa', 'Dnipro'];
        $tagsPool = ['VIP', 'New', 'Problematic', 'Active', 'Archive'];

        $totalRecords = 15000; // Generate 15,000 records
        $batchSize = 2000;
        $inserted = 0;

        $io->progressStart($totalRecords);

        while ($inserted < $totalRecords) {
            $rows = [];
            $currentBatch = min($batchSize, $totalRecords - $inserted);

            for ($i = 0; $i < $currentBatch; $i++) {
                $randomCity = $cities[array_rand($cities)];
                $randomAge = rand(18, 65);

                // Build random tags array
                $randomTags = [];
                for ($t = 0; $t < rand(1, 3); $t++) {
                    $randomTags[] = $tagsPool[array_rand($tagsPool)];
                }
                $tagsString = implode(', ', array_unique($randomTags));

                // Build valid JSON-properties
                $properties = json_encode([
                    'city' => $randomCity,
                    'age' => $randomAge,
                    'tags' => $tagsString,
                ]);

                $rows[] = sprintf(
                    "('%s', 'customer_%s@omnicore.io', '%s', '%s', NOW())",
                    Uuid::v7()->toRfc4122(),
                    uniqid('user_', true),
                    $properties,
                    $tenantId
                );
            }

            // Make a quick bulk insert
            $sql = "INSERT INTO customers (id, email, properties, tenant_id, created_at) VALUES " . implode(', ', $rows);
            $connection->executeStatement($sql);

            $inserted += $currentBatch;
            $io->progressAdvance($currentBatch);
        }

        $io->progressFinish();
        $io->success(sprintf('Successfully injected %d high-volume customer records into PostgreSQL!', $totalRecords));

        return Command::SUCCESS;
    }
}
