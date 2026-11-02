<?php

namespace OmniCore\Security;

use OmniCore\Entity\Tenant;
use Symfony\Component\Uid\Uuid;

class TenantContext
{
    private ?Tenant $currentTenant = null;

    public function setTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function getTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    public function getTenantId(): ?Uuid
    {
        return $this->currentTenant?->getId();
    }
}
