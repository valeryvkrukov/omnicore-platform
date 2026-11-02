<?php

namespace OmniCore\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use OmniCore\Entity\Customer;

class TenantFilter extends SQLFilter
{
    public const string FILTER_NAME = 'tenant_filter';

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        // Check if the entity has a relationship to the tenant.
        // Currently, this is Customer (or any other entities that will be added later)
        if ($targetEntity->getReflectionClass()->getName() !== Customer::class) {
            return '';
        }

        // Return native PostgreSQL SQL condition.
        // Parameter 'tenant_id' will be dynamically passed from the query context.
        return sprintf('%s.tenant_id = %s::uuid', $targetTableAlias, $this->getParameter('tenant_id'));
    }
}
