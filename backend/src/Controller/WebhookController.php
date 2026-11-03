<?php

namespace OmniCore\Controller;

use OmniCore\Message\ProcessWebhookMessage;
use OmniCore\Security\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/webhooks')]
final class WebhookController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly MessageBusInterface $messageBus,
        private readonly RateLimiterFactory $webhookLimiterFactory,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    #[Route(path: '/{source}', name: 'api_webhook_receive', methods: ['POST'])]
    public function receive(string $source, Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getTenant();
        if (!$tenant) {
            return new JsonResponse(['error' => 'Tenant context missing.'], Response::HTTP_UNAUTHORIZED);
        }

        // 1. Rate limiting based on tenant ID
        $limiter = $this->webhookLimiterFactory->create($tenant->getId()->toString());
        if (!$limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException(retryAfter: 1, message: 'Rate limit exceeded. Too many webhooks.');
        }

        // 2. Getting and validating source data
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || empty($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON payload.'], Response::HTTP_BAD_REQUEST);
        }

        // Later will be added validation for HMAC
        // $signature = $request->headers->get('X-OmniCore-Signature');
        // if (!$this->isValidSignature($request->getContent(), $signature, $tenant->getSettings()['secret'])) { ... }

        // 3. Push the task to async queue
        $this->messageBus->dispatch(new ProcessWebhookMessage(
            tenantId: $tenant->getId(),
            source: $source,
            payload: $payload
        ));

        // 4. Immediate response 202 Accepted (task in processing)
        return new JsonResponse(
            data: [
                'status' => 'accepted',
                'message' => 'Webhook successfully queued for asynchronous processing.',
                'tenant' => $tenant->getName()
            ],
            status: Response::HTTP_ACCEPTED
        );
    }
}
