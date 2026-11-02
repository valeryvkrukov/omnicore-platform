<?php

namespace OmniCore\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use OmniCore\Entity\Tenant;
use OmniCore\Filter\TenantFilter;
use OmniCore\Security\TenantContext;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: 'kernel.request', priority: 256)]
final readonly class TenantListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        // Extract tenant ID from header (or later from subdomain/JWT)
        $tenantHeader = $request->headers->get('X-Tenant-ID');

        if (!$tenantHeader) {
            return;
        }

        if (!Uuid::isValid($tenantHeader)) {
            throw new BadRequestHttpException('Invalid X-Tenant-ID format. Must be UUID.');
        }

        $tenantId = Uuid::fromString($tenantHeader);
        $tenant = $this->entityManager->find(Tenant::class, $tenantId);

        if (!$tenant) {
            throw new BadRequestHttpException('Tenant not found.');
        }

        // Set the tenant in the global context
        $this->tenantContext->setTenant($tenant);

        // Activate Doctrine filter and push binary UUID for PostgreSQL
        /** @var TenantFilter $filter */
        $filter = $this->entityManager->getFilters()->enable(TenantFilter::FILTER_NAME);
        $filter->setParameter('tenant_id', $tenant->getId()->toString());
    }
}
