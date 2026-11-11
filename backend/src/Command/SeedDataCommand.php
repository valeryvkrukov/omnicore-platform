<?php

namespace OmniCore\Command;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use OmniCore\Entity\Customer;
use OmniCore\Entity\Tenant;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'omnicore:seed-data',
    description: 'Generating demo chronological data for development and testing purposes',
)]
class SeedDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws \DateMalformedStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Launching the OmniCore Data Seeder');

        // Clean old data before seeding
        $this->entityManager->getConnection()->executeStatement('TRUNCATE tenants CASCADE');

        // 1. Create tenants bound to UUID of frontend
        $tenantsData = [
            [
                'id' => '018f9b2d-3c4a-7123-bc45-6789abcdef01',
                'name' => 'Acme Marketing Corp',
                'plan' => 'scale_up',
                'cities' => ['Kharkiv', 'Kyiv', 'Lviv'],
                'tags' => ['premium', 'b2b', 'enterprise', 'trial']
            ],
            [
                'id' => '01901a2f-5b6c-7234-ad56-1234567890ab',
                'name' => 'Acme Marketing Corp',
                'plan' => 'scale_up',
                'cities' => ['Kharkiv', 'Kyiv', 'Lviv'],
                'tags' => ['premium', 'b2b', 'enterprise', 'trial']
            ]
        ];

        foreach ($tenantsData as $data) {
            $tenant = new Tenant($data['name']);

            // Forcefully replace the UUID BEFORE calling persist()
            // so that Doctrine immediately indexes it correctly.
            $reflection = new \ReflectionProperty(Tenant::class, 'id');
            $reflection->setValue($tenant, Uuid::fromString($data['id']));

            $tenant->setSettings([
                'plan' => $data['plan'],
                'rate_limit' => 5
            ]);
            $this->entityManager->persist($tenant);

            $io->text(sprintf('Tenant created: %s [%s]', $tenant->getName(), $tenant->getId()->toString()));

            // 2. Generate a client history for the last 7 days for graphs
            $totalInserted = 0;
            for ($day = 6; $day >= 0; $day--) {
                $registrationsCount = rand(10, 30);

                for ($i = 0; $i < $registrationsCount; $i++) {
                    $customer = new Customer($tenant);
                    str_replace(' ', '', $tenant->getName())
                        |> strtolower(...)
                        |> (fn($x) => sprintf('user_%d_%d@%s.test', $day, $i, $x))
                        |> $customer->setEmail(...);

                    // Constructing a diverse JSONB metadata structure
                    $customer->setProperties([
                        'age' => rand(18, 65),
                        'city' => $data['cities'][array_rand($data['cities'])],
                        'score' => rand(70, 100),
                        'tags' => [
                            $data['cities'][array_rand($data['cities'])],
                            $data['cities'][array_rand($data['cities'])]
                        ],
                        'system_meta' => [
                            'ip' => '192.168.1.' . rand(2, 254),
                            'verified' => (rand(0, 4) > 0)
                        ]
                    ]);

                    // Forcefully shift the creation date back in time to construct an aesthetically pleasing graph
                    $targetDate = new \DateTimeImmutable(sprintf('-%d days %d hours', $day, rand(0, 23)));
                    $dateReflection = new \ReflectionProperty(Customer::class, 'createdAt');
                    $dateReflection->setValue($customer, $targetDate);

                    $this->entityManager->persist($customer);
                    $totalInserted++;
                }
            }
            // Flush the current batch of data to PostgreSQL
            $this->entityManager->flush();
            // Completely clear the Identity Map so that objects belonging to the
            // next tenant do not conflict with those from the previous one.
            $this->entityManager->clear();

            $io->success(sprintf('Successfully generated %d customers for %s', $totalInserted, $tenant->getName()));
        }

        $io->success('Database seeding completed successfully!');

        return Command::SUCCESS;
    }
}
