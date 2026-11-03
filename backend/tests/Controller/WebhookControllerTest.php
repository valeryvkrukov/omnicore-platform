<?php

namespace OmniCore\Tests\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use OmniCore\Entity\Tenant;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class WebhookControllerTest extends WebTestCase
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

        // Create test tenant
        $this->testTenant = new Tenant('Stripe Core Integration');
        $this->entityManager->persist($this->testTenant);
        $this->entityManager->flush();
    }

    public function testReceiveWebhookSuccess(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/v1/webhooks/stripe',
            server: [
                'HTTP_X-Tenant-ID' => $this->testTenant->getId()->toString(),
                'CONTENT_TYPE' => 'application/json'
            ],
            content: json_encode([
                'email' => 'customer@stripe.com',
                'attributes' => ['tier' => 'enterprise_gold']
            ])
        );

        // Check for status 202 HTTP_ACCEPTED
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('accepted', $responseData['status']);
        self::assertEquals('Stripe Core Integration', $responseData['tenant']);
    }

    public function testRateLimiterBlocksExcessiveRequests(): void
    {
        // Current limit is 5 requests per minute.
        // Will complete 10 requests to guarantee rate limiter blocks
        for ($i = 0; $i < 10; $i++) {
            $this->client->request(
                method: 'POST',
                uri: '/api/v1/webhooks/stripe',
                server: [
                    'HTTP_X-Tenant-ID' => $this->testTenant->getId()->toString(),
                    'CONTENT_TYPE' => 'application/json'
                ],
                content: json_encode([
                    'email' => 'flood@test.com'
                ])
            );
        }

        // Check for status 429 HTTP_TOO_MANY_REQUESTS (latest request must fail with status code 429)
        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
