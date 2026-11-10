<?php

namespace OmniCore\Tests\Entity;

use OmniCore\Entity\Customer;
use OmniCore\Entity\Tenant;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\EntityManagerInterface;

class CustomerPersistenceTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->executeStatement('TRUNCATE tenants CASCADE;');
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testCustomerWithJsonbDataIsPersistedCorrectly(): void
    {
        // 1. Create a new tenant
        $tenant = new Tenant('Test Agency');
        $tenant->setSettings([
            'rate_limit' => 200,
            'tier' => 'premium'
        ]);
        $this->entityManager->persist($tenant);

        // 2. Create a new customer
        $customer = new Customer($tenant);
        $customer->setEmail('client@omnicore.test');
        $customer->setProperties([
            'age' => 45,
            'location' => 'New York',
            'hobbies' => ['reading', 'traveling'],
            'metadata' => [
                'is_active' => true,
            ],
        ]);
        $this->entityManager->persist($customer);

        $this->entityManager->getFilters()
            ->enable('tenant_filter')
            ->setParameter(
                'tenant_id',
                $tenant->getId()->toString()
            );

        $this->entityManager->flush();
        $this->entityManager->clear();

        /**
         * @var Customer|null $retrievedCustomer
         *
         * 3. Check that the customer was persisted correctly
         */
        $retrievedCustomer = $this->entityManager->find(Customer::class, $customer->getId());

        self::assertNotNull($retrievedCustomer);
        self::assertEquals('client@omnicore.test', $retrievedCustomer->getEmail());
        self::assertEquals('New York', $retrievedCustomer->getProperties()['location']);
        self::assertEquals(45, $retrievedCustomer->getProperties()['age']);
        self::assertContains('reading', $retrievedCustomer->getProperties()['hobbies']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }
}
