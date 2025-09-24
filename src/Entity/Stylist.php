<?php
// src/Entity/Stylist.php

namespace App\Entity;

use App\Repository\StylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StylistRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Stylist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stylists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Salon $salon = null;

    #[ORM\ManyToOne(inversedBy: 'stylistProfiles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'json')]
    private array $languages = []; // ex: ["fr", "en", "es"]

    #[ORM\Column(name: 'open_hours', type: 'json', nullable: true)]
    private ?array $openHours = null; // Horaires spécifiques du coiffeur, null = utilise horaires salon

    #[ORM\ManyToMany(targetEntity: Service::class)]
    #[ORM\JoinTable(name: 'stylist_service')]
    private Collection $skills;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'stylist', targetEntity: Booking::class)]
    private Collection $bookings;

    #[ORM\OneToMany(mappedBy: 'stylist', targetEntity: Review::class)]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'stylist', targetEntity: Media::class, orphanRemoval: true)]
    private Collection $media;

    #[ORM\OneToMany(mappedBy: 'stylist', targetEntity: AvailabilityException::class)]
    private Collection $availabilityExceptions;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->availabilityExceptions = new ArrayCollection();
        $this->skills = new ArrayCollection();
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

    public function getSalon(): ?Salon
    {
        return $this->salon;
    }

    public function setSalon(?Salon $salon): static
    {
        $this->salon = $salon;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function setLanguages(array $languages): static
    {
        $this->languages = $languages;
        return $this;
    }

    public function getOpenHours(): ?array
    {
        return $this->openHours;
    }

    public function setOpenHours(?array $openHours): static
    {
        $this->openHours = $openHours;
        return $this;
    }

    /**
     * @return Collection<int, Service>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Service $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Service $skill): static
    {
        $this->skills->removeElement($skill);

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
            $booking->setStylist($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getStylist() === $this) {
                $booking->setStylist(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setStylist($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getStylist() === $this) {
                $review->setStylist(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedium(Media $medium): static
    {
        if (!$this->media->contains($medium)) {
            $this->media->add($medium);
            $medium->setStylist($this);
        }

        return $this;
    }

    public function removeMedium(Media $medium): static
    {
        if ($this->media->removeElement($medium)) {
            // set the owning side to null (unless already changed)
            if ($medium->getStylist() === $this) {
                $medium->setStylist(null);
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
            $availabilityException->setStylist($this);
        }

        return $this;
    }

    public function removeAvailabilityException(AvailabilityException $availabilityException): static
    {
        if ($this->availabilityExceptions->removeElement($availabilityException)) {
            // set the owning side to null (unless already changed)
            if ($availabilityException->getStylist() === $this) {
                $availabilityException->setStylist(null);
            }
        }

        return $this;
    }

    public function getAverageRating(): float
    {
        $totalRating = 0;
        $reviewCount = 0;

        foreach ($this->reviews as $review) {
            if ($review->getStatus() === 'APPROVED') {
                $totalRating += $review->getRating();
                $reviewCount++;
            }
        }

        return $reviewCount > 0 ? round($totalRating / $reviewCount, 1) : 0.0;
    }
}