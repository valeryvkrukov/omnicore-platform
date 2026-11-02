<?php

namespace OmniCore\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use OmniCore\Entity\Customer;
use OmniCore\Entity\Tenant;
use OmniCore\Message\ProcessWebhookMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessWebhookHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function __invoke(ProcessWebhookMessage $message): void
    {
        $this->logger->info('Start async webhook processing', [
            'tenantId' => $message->tenantId->toString(),
            'source' => $message->source
        ]);

        $tenant = $this->entityManager->find(Tenant::class, $message->tenantId);
        if (!$tenant) {
            $this->logger->error('Tenant not found for webhook', [
                'message' => $message->tenantId->toString()
            ]);
            return;
        }

        $email = $message->payload['email'] ?? null;
        if ($email) {
            $customerRepository = $this->entityManager->getRepository(Customer::class);
            $customer = $customerRepository->findOneBy([
                'tenant' => $tenant,
                'email' => $email
            ]);

            if (!$customer) {
                $customer = new Customer($tenant);
                $customer->setEmail($email);
            }

            $currentProperties = $customer->getProperties();
            $incomingAttributes = $message->payload['attributes'] ?? [];
            $newProperties = array_merge($currentProperties, $incomingAttributes);

            $customer->setProperties($newProperties);

            $this->entityManager->persist($customer);
            $this->entityManager->flush();
        }
    }
}
