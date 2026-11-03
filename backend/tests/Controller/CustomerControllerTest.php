<?php

namespace OmniCore\Tests\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use OmniCore\Entity\Customer;
use OmniCore\Entity\Tenant;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CustomerControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private Tenant $testTenant;
    private KernelBrowser $client;

    protected static function getKernelClass(): string
    {
        return \OmniCore\Kernel::class;
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->executeStatement('TRUNCATE tenants CASCADE');

        $this->testTenant = new Tenant('Acme Corp Marketing');
        $this->testTenant->setSettings(['plan' => 'scale_up']);
        $this->entityManager->persist($this->testTenant);

        // Create a test customer
        $customer = new Customer($this->testTenant);
        $customer->setEmail('frontend-dev@omnicore.io');
        $customer->setProperties(['city' => 'Kharkiv']);
        $this->entityManager->persist($customer);

        $this->entityManager->flush();
    }

    public function testGetCustomersListSuccess(): void
    {
        $this->client->request(
            method: 'GET',
            uri: '/api/v1/customers',
            server: [
                'HTTP_X-Tenant-ID' => $this->testTenant->getId()->toString(),
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        self::assertIsArray($responseData);
        self::assertNotEmpty($responseData);
        self::assertEquals('frontend-dev@omnicore.io', $responseData[0]['email']);
        self::assertEquals('Kharkiv', $responseData[0]['properties']['city']);
    }

    public function testGetAnalyticsSuccess(): void
    {
        $this->client->request(
            method: 'GET',
            uri: '/api/v1/customers/analytics',
            server: [
                'HTTP_X-Tenant-ID' => $this->testTenant->getId()->toString(),
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        self::assertArrayHasKey('metrics', $responseData);
        self::assertArrayHasKey('chart', $responseData);
        self::assertEquals('scale_up', $responseData['metrics']['current_plan']);
        self::assertEquals(1, $responseData['metrics']['total_customers']);
    }
}
