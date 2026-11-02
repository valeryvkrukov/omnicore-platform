<?php

namespace OmniCore\Message;

use Symfony\Component\Uid\Uuid;

final readonly class ProcessWebhookMessage
{
    public function __construct(
        public Uuid $tenantId,
        public string $source,
        public array $payload
    ) {}
}
