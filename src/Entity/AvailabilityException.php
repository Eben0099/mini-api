<?php
// src/Entity/AvailabilityException.php

namespace App\Entity;

use App\Repository\AvailabilityExceptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityExceptionRepository::class)]
class AvailabilityException
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'availabilityExceptions')]
    private ?Salon $salon = null;

    #[ORM\ManyToOne(inversedBy: 'availabilityExceptions')]
    private ?Stylist $stylist = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column]
    private ?bool $closed = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

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

    public function getStylist(): ?Stylist
    {
        return $this->stylist;
    }

    public function setStylist(?Stylist $stylist): static
    {
        $this->stylist = $stylist;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function isClosed(): ?bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): static
    {
        $this->closed = $closed;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function affectsSalon(): bool
    {
        return $this->salon !== null && $this->stylist === null;
    }

    public function affectsStylist(): bool
    {
        return $this->stylist !== null;
    }
}