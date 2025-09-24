<?php
// src/Entity/Salon.php

namespace App\Entity;

use App\Repository\SalonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalonRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Salon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ownedSalons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text')]
    private ?string $address = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(nullable: true)]
    private ?float $lat = null;

    #[ORM\Column(nullable: true)]
    private ?float $lng = null;

    #[ORM\Column(type: 'json')]
    private array $openHours = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'salon', targetEntity: Service::class, orphanRemoval: true)]
    private Collection $services;

    #[ORM\OneToMany(mappedBy: 'salon', targetEntity: Stylist::class, orphanRemoval: true)]
    private Collection $stylists;

    #[ORM\OneToMany(mappedBy: 'salon', targetEntity: Booking::class)]
    private Collection $bookings;

    #[ORM\OneToMany(mappedBy: 'salon', targetEntity: AvailabilityException::class)]
    private Collection $availabilityExceptions;

    #[ORM\OneToMany(mappedBy: 'salon', targetEntity: WaitlistEntry::class)]
    private Collection $waitlistEntries;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->stylists = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->availabilityExceptions = new ArrayCollection();
        $this->waitlistEntries = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setTimestamps(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getLat(): ?float
    {
        return $this->lat;
    }

    public function setLat(?float $lat): static
    {
        $this->lat = $lat;
        return $this;
    }

    public function getLng(): ?float
    {
        return $this->lng;
    }

    public function setLng(?float $lng): static
    {
        $this->lng = $lng;
        return $this;
    }

    public function getOpenHours(): array
    {
        return $this->openHours;
    }

    public function setOpenHours(array $openHours): static
    {
        $this->openHours = $openHours;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setSalon($this);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        if ($this->services->removeElement($service)) {
            // set the owning side to null (unless already changed)
            if ($service->getSalon() === $this) {
                $service->setSalon(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Stylist>
     */
    public function getStylists(): Collection
    {
        return $this->stylists;
    }

    public function addStylist(Stylist $stylist): static
    {
        if (!$this->stylists->contains($stylist)) {
            $this->stylists->add($stylist);
            $stylist->setSalon($this);
        }

        return $this;
    }

    public function removeStylist(Stylist $stylist): static
    {
        if ($this->stylists->removeElement($stylist)) {
            // set the owning side to null (unless already changed)
            if ($stylist->getSalon() === $this) {
                $stylist->setSalon(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setSalon($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getSalon() === $this) {
                $booking->setSalon(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AvailabilityException>
     */
    public function getAvailabilityExceptions(): Collection
    {
        return $this->availabilityExceptions;
    }

    public function addAvailabilityException(AvailabilityException $availabilityException): static
    {
        if (!$this->availabilityExceptions->contains($availabilityException)) {
            $this->availabilityExceptions->add($availabilityException);
            $availabilityException->setSalon($this);
        }

        return $this;
    }

    public function removeAvailabilityException(AvailabilityException $availabilityException): static
    {
        if ($this->availabilityExceptions->removeElement($availabilityException)) {
            // set the owning side to null (unless already changed)
            if ($availabilityException->getSalon() === $this) {
                $availabilityException->setSalon(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WaitlistEntry>
     */
    public function getWaitlistEntries(): Collection
    {
        return $this->waitlistEntries;
    }

    public function addWaitlistEntry(WaitlistEntry $waitlistEntry): static
    {
        if (!$this->waitlistEntries->contains($waitlistEntry)) {
            $this->waitlistEntries->add($waitlistEntry);
            $waitlistEntry->setSalon($this);
        }

        return $this;
    }

    public function removeWaitlistEntry(WaitlistEntry $waitlistEntry): static
    {
        if ($this->waitlistEntries->removeElement($waitlistEntry)) {
            // set the owning side to null (unless already changed)
            if ($waitlistEntry->getSalon() === $this) {
                $waitlistEntry->setSalon(null);
            }
        }

        return $this;
    }

    public function getAverageRating(): float
    {
        $totalRating = 0;
        $reviewCount = 0;

        foreach ($this->stylists as $stylist) {
            foreach ($stylist->getReviews() as $review) {
                if ($review->getStatus() === 'APPROVED') {
                    $totalRating += $review->getRating();
                    $reviewCount++;
                }
            }
        }

        return $reviewCount > 0 ? round($totalRating / $reviewCount, 1) : 0.0;
    }
}