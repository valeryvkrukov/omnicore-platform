<?php

namespace OmniCore\Command;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
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
     * @throws \DateMalformedStringException
     * @throws RandomException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        $tenantIdList = $connection->fetchAllAssociative('SELECT id FROM tenants');
        if (!$tenantIdList) {
            $io->error('No tenants found in the database. Please seed tenants first.');
            return Command::FAILURE;
        }

        $pickWeighted = function (array $weightedValues) {
            $rand = rand(1, (int) array_sum($weightedValues));
            foreach ($weightedValues as $key => $value) {
                $rand -= $value;
                if ($rand <= 0) {
                    return $key;
                }
            }
            return array_key_first($weightedValues);
        };

        // Weights of cities (100% at all)
        $citiesWeights = [
            'Kyiv' => 40,
            'Kharkiv' => 25,
            'Lviv' => 18,
            'Odesa' => 12,
            'Dnipro' => 5,
        ];

        // Weights of tags
        $tagsWeights = [
            'New' => 35,
            'Active' => 30,
            'VIP' => 15,
            'Problematic' => 12,
            'Archive' => 8,
        ];

        $totalRecords = 15000;
        $batchSize = 2000;
        $inserted = 0;

        $io->title('Starting Realistic Bulk Seeding for Customers...');
        $io->progressStart($totalRecords);

        while ($inserted < $totalRecords) {
            $rows = [];
            $currentBatch = min($batchSize, $totalRecords - $inserted);

            for ($i = 0; $i < $currentBatch; $i++) {
                $city = $pickWeighted($citiesWeights);

                // Age range with typical values (the majority are 24–38 years old)
                $age = (int) round((rand(18, 65) + rand(22, 42)) / 2);

                // Generate 1-3 unique tags based on their rarity
                $tagsCount = rand(1, 3);
                $customerTags = [];
                for ($t = 0; $t < $tagsCount; $t++) {
                    $customerTags[] = $pickWeighted($tagsWeights);
                }
                $customerTags = array_values(array_unique($customerTags));

                $properties = json_encode([
                    'city' => $city,
                    'age' => $age,
                    'tags' => $customerTags,
                ]);

                // Imitation of organic growth over the past 7 days:
                // The closer to today's date (0), the higher the chance of record appearance
                $daysAgo = match (true) {
                    rand(1, 100) <= 28 => 0, // Today (peak traffic)
                    rand(1, 100) <= 45 => 1, // Yesterday
                    rand(1, 100) <= 60 => 2,
                    rand(1, 100) <= 72 => 3,
                    rand(1, 100) <= 82 => 4,
                    rand(1, 100) <= 92 => 5,
                    default => 6, // 6 days ago (start of the week)
                };

                // Add random hours and minutes within the selected day
                $randomSeconds = ($daysAgo * 86400) + rand(0, 86399);
                $createdAt = new \DateTimeImmutable()->modify("-{$randomSeconds} seconds")->format('Y-m-d H:i:s');

                $rows[] = sprintf(
                    "('%s', 'customer_%s@omnicore.io', '%s', '%s', '%s')",
                    Uuid::v7()->toRfc4122(),
                    uniqid('user_', true),
                    $properties,
                    $tenantIdList[mt_rand(0, count($tenantIdList) - 1)]['id'],
                    $createdAt
                );
            }

            $sql = "INSERT INTO customers (id, email, properties, tenant_id, created_at) VALUES " . implode(',', $rows);
            $connection->executeStatement($sql);

            $inserted += $currentBatch;
            $io->progressAdvance($currentBatch);
        }

        $io->progressFinish();

        return Command::SUCCESS;
    }
}
