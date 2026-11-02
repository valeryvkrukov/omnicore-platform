<?php

namespace OmniCore\Entity;

use OmniCore\Repository\CustomerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Table(name: 'customers')]
#[ORM\Index(name: 'idx_customers_properties', columns: ['properties'], flags: ['jsonb_path_ops'])]
class Customer
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Tenant::class, inversedBy: 'customers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tenant $tenant;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    /**
     * Dynamic attributes of the customer (PostgreSQL JSONB).
     * Here, any custom fields of the tenant can be written: {"age": 34, "city": "Kharkiv", "group": "B2B"}
     */
    #[ORM\Column(type: 'json', columnDefinition: 'jsonb')]
    private array $properties = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Tenant $tenant)
    {
        $this->id = Uuid::v7();
        $this->tenant = $tenant;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTenant(): Tenant
    {
        return $this->tenant;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    public function getProperty(string $key, mixed $default = null): mixed
    {
        return $this->properties[$key] ?? $default;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
