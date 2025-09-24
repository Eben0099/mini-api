<?php
// src/Entity/WaitlistEntry.php

namespace App\Entity;

use App\Repository\WaitlistEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WaitlistEntryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class WaitlistEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'waitlistEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Salon $salon = null;

    #[ORM\ManyToOne(inversedBy: 'waitlistEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\ManyToOne(inversedBy: 'waitlistEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $client = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $desiredStartRangeStart = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $desiredStartRangeEnd = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSalon(): ?Salon
    {
        return $this->salon;
    }

    public function setSalon(?Salon $salon): static
    {
        $this->salon = $salon;
        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;
        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getDesiredStartRangeStart(): ?\DateTimeImmutable
    {
        return $this->desiredStartRangeStart;
    }

    public function setDesiredStartRangeStart(\DateTimeImmutable $desiredStartRangeStart): static
    {
        $this->desiredStartRangeStart = $desiredStartRangeStart;
        return $this;
    }

    public function getDesiredStartRangeEnd(): ?\DateTimeImmutable
    {
        return $this->desiredStartRangeEnd;
    }

    public function setDesiredStartRangeEnd(\DateTimeImmutable $desiredStartRangeEnd): static
    {
        $this->desiredStartRangeEnd = $desiredStartRangeEnd;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isRangeValid(): bool
    {
        return $this->desiredStartRangeStart < $this->desiredStartRangeEnd;
    }

    public function getDurationMinutes(): int
    {
        return $this->service?->getDurationMinutes() ?? 0;
    }
}