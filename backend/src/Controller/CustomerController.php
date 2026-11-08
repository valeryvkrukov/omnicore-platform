<?php

namespace OmniCore\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use OmniCore\Entity\Customer;
use OmniCore\Repository\CustomerRepository;
use OmniCore\Security\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/customers')]
final class CustomerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TenantContext $tenantContext,
        private readonly SerializerInterface $serializer,
    ) {}

    /**
     * Returns a list of customers for the current tenant.
     * @throws ExceptionInterface
     */
    #[Route('', name: 'api_customers_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getTenant();

        if (!$tenant) {
            return new JsonResponse(['error' => 'Unauthorized tenant context'], Response::HTTP_UNAUTHORIZED);
        }

        // Extract filtration parameters (?city=Kharkiv&age=25&tag=VIP)
        $filters = [
            'city' => $request->query->get('city'),
            'age' => $request->query->get('age'),
            'tag' => $request->query->get('tag'),
        ];

        // Exclude blank and null values
        $filters = array_filter($filters, fn($value) => $value !== null && $value !== '');

        /** @var CustomerRepository $repository */
        $repository = $this->entityManager->getRepository(Customer::class);

        // Get filtered customers tied to current Tenant
        $customers = $repository->findFilteredForTenant($tenant, $filters);

        $jsonContent = $this->serializer->serialize($customers, 'json', [
            'ignored_attributes' => ['tenant'] // Exclude the cyclical reference to 'tenant' field
        ]);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }

    /**
     * Returns aggregated analytics data by customers for the front-end graphs.
     * @throws Exception
     */
    #[Route('/analytics', name: 'api_customers_analytics', methods: ['GET'])]
    public function analytics(): JsonResponse
    {
        $tenant = $this->tenantContext->getTenant();
        if (!$tenant) {
            return new JsonResponse(['error' => 'Unauthorized tenant context'], Response::HTTP_UNAUTHORIZED);
        }

        $connection = $this->entityManager->getConnection();

        // Native SQL query for PostgreSQL:
        // Aggregate number of customers registered by days on lastest week
        $sql = "
            SELECT
                TO_CHAR(c.created_at, 'YYYY-MM-DD') as date_label,
                COUNT(c.id) as total_count
            FROM customers c
            WHERE c.tenant_id = CAST(:tenant_id AS uuid)
                AND c.created_at >= NOW() - INTERVAL '7 days'
            GROUP BY date_label
            ORDER BY date_label ASC
        ";

        try {
            $result = $connection->fetchAllAssociative($sql, [
                'tenant_id' => $tenant->getId()->toString()
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Database Query Execution Failed',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $labels = [];
        $values = [];

        foreach ($result as $row) {
            $labels[] = $row['date_label'] ?? '';
            $values[] = (int) ($row['total_count'] ?? 0);
        }

        return new JsonResponse([
            'metrics' => [
                'total_customers' => array_sum($values),
                'current_plan' => $tenant->getSettings()['plan'] ?? 'free',
                'rate_limit' => 5
            ],
            'chart' => [
                'labels' => $labels,
                'data' => $values
            ]
        ], Response::HTTP_OK);
    }
}
