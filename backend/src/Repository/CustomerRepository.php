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
                WHERE c.tenant_id = :tenant_id";

        $params = [
            'tenant_id' => $tenant->getId()->toString()
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

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        foreach ($params as $key => $value) {
            $query->setParameter($key, $value);
        }

        return $query->getResult();

        /*$qb = $this->createQueryBuilder('c')
            ->where('c.tenant = :tenant')
            ->setParameter('tenant', $tenant)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(50);

        if (!empty($filters['city'])) {
            $qb->andWhere("JSON_GET_TEXT(c.customFields, 'city') = :city")
                ->setParameter('city', $filters['city']);
        }

        if (!empty($filters['age'])) {
            $qb->andWhere("JSON_GET_TEXT(c.customFields, 'age') = :age")
                ->setParameter('age', (string)$filters['age']);
        }

        if (!empty($filters['tag'])) {
            // If JSONB contains tags as a string or substring
            $qb->andWhere("JSON_GET_TEXT(c.customFields, 'tag') LIKE :tag")
                ->setParameter('tag', '%' . $filters['tag'] . '%');
        }

        return $qb->getQuery()->getResult();*/
    }

}
