<?php

namespace OmniCore\Entity;

use OmniCore\Repository\TenantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[ORM\Table(name: 'tenants')]
class Tenant
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * Tariffs and limits configuration (for ex.: {"plan": "enterprise", "rate_limit": 500})
     */
    #[ORM\Column(type: 'json')]
    private array $settings = [];

    /**
     * @var Collection<int, Customer>
     */
    #[ORM\OneToMany(targetEntity: Customer::class, mappedBy: 'tenant', cascade: ['remove'])]
    private Collection $customers;

    public function __construct(string $name)
    {
        // Using UUIDv7 ensures chronological sorting of records at the PostgreSQL disk level
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->customers = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return Collection<int, Customer>
     */
    public function getCustomers(): Collection
    {
        return $this->customers;
    }
}
