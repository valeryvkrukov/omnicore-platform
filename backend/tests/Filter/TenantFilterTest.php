<?php

namespace OmniCore\Tests\Filter;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use OmniCore\Entity\Customer;
use OmniCore\Entity\Tenant;
use OmniCore\Filter\TenantFilter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TenantFilterTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->executeStatement('TRUNCATE tenants CASCADE');
    }

    public function testFilterAutomaticallyIsolatesData(): void
    {
        // 1. Create two absolutely different companies
        $tenantA = new Tenant('Company A');
        $tenantB = new Tenant('Company B');

        $this->entityManager->persist($tenantA);
        $this->entityManager->persist($tenantB);

        // 2. Create a single client for each company
        $customerA = new Customer($tenantA);
        $customerA->setEmail('alpha@omnicore.io');
        $customerB = new Customer($tenantB);
        $customerB->setEmail('beta@omnicore.io');

        $this->entityManager->persist($customerA);
        $this->entityManager->persist($customerB);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // 3. Activating the Doctrine filter and imitating the behavior of Tenant A
        /** @var TenantFilter $filter */
        $filter = $this->entityManager->getFilters()->enable(TenantFilter::FILTER_NAME);
        $filter->setParameter('tenant_id', $tenantA->getId()->toString());

        // 4. Trying to get ALL customers via standard repository
        $customerRepository = $this->entityManager->getRepository(Customer::class);
        $allCustomers = $customerRepository->findAll();

        // Checking: Doctrine should return only customers of Tenant A,
        // completely ignoring Tenant B record on the SQL query level
        self::assertCount(1, $allCustomers);
        self::assertEquals('alpha@omnicore.io', $allCustomers[0]->getEmail());
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
