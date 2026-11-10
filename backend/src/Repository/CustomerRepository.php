<?php

namespace OmniCore\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use OmniCore\Entity\Customer;
use OmniCore\Entity\Tenant;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * @param Tenant $tenant
     * @param array{city?: string, age?: int, tag?: string} $filters
     * @return Customer[]
     */
    public function findFilteredForTenant(Tenant $tenant, array $filters): array
    {
        $rsm = $this->createResultSetMappingBuilder('c');

        $sql = "SELECT " . $rsm->generateSelectClause() . "
                FROM customers c
                WHERE c.tenant_id = CAST(:native_tenant_id AS uuid)";

        $params = [
            'native_tenant_id' => $tenant->getId()->toString()
        ];

        if (!empty($filters['city'])) {
            $sql .= " AND c.properties ->> 'city' = :city";
            $params['city'] = $filters['city'];
        }

        if (!empty($filters['age'])) {
            $sql .= " AND CAST(c.properties ->> 'age' AS integer) = :age";
            $params['age'] = (int)$filters['age'];
        }

        if (!empty($filters['tag'])) {
            $sql .= " AND c.properties ->> 'tags' LIKE :tag";
            $params['tag'] = '%' . $filters['tag'] . '%';
        }

        $sql .= " ORDER BY c.created_at DESC LIMIT 50";

        $filtersManager = $this->getEntityManager()->getFilters();
        $filterEnabled = $filtersManager->isEnabled('tenant_filter');

        if ($filterEnabled) {
            $filtersManager->disable('tenant_filter');
        }

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        foreach ($params as $key => $value) {
            $query->setParameter($key, $value);
        }

        $result = $query->getResult();

        if ($filterEnabled) {
            $filtersManager->enable('tenant_filter');
        }

        return $result;
    }

}
