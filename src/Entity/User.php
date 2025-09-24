<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $resetTokenExpiresAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Booking::class)]
    private Collection $bookings;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Review::class)]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: WaitlistEntry::class)]
    private Collection $waitlistEntries;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Stylist::class)]
    private Collection $stylistProfiles;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Salon::class)]
    private Collection $ownedSalons;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->waitlistEntries = new ArrayCollection();
        $this->stylistProfiles = new ArrayCollection();
        $this->ownedSalons = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        // Ne sauvegarde pas ROLE_USER en base de données car il est ajouté automatiquement dans getRoles()
        $this->roles = array_values(array_filter($roles, function($role) {
            return $role !== 'ROLE_USER';
        }));

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
    }

    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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
            $booking->setUser($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getUser() === $this) {
                $booking->setUser(null);
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
            $review->setUser($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getUser() === $this) {
                $review->setUser(null);
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
            $waitlistEntry->setUser($this);
        }

        return $this;
    }

    public function removeWaitlistEntry(WaitlistEntry $waitlistEntry): static
    {
        if ($this->waitlistEntries->removeElement($waitlistEntry)) {
            // set the owning side to null (unless already changed)
            if ($waitlistEntry->getUser() === $this) {
                $waitlistEntry->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Stylist>
     */
    public function getStylistProfiles(): Collection
    {
        return $this->stylistProfiles;
    }

    public function addStylistProfile(Stylist $stylist): static
    {
        if (!$this->stylistProfiles->contains($stylist)) {
            $this->stylistProfiles->add($stylist);
            $stylist->setUser($this);
        }

        return $this;
    }

    public function removeStylistProfile(Stylist $stylist): static
    {
        if ($this->stylistProfiles->removeElement($stylist)) {
            // set the owning side to null (unless already changed)
            if ($stylist->getUser() === $this) {
                $stylist->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Salon>
     */
    public function getOwnedSalons(): Collection
    {
        return $this->ownedSalons;
    }

    public function addOwnedSalon(Salon $salon): static
    {
        if (!$this->ownedSalons->contains($salon)) {
            $this->ownedSalons->add($salon);
            $salon->setOwner($this);
        }

        return $this;
    }

    public function removeOwnedSalon(Salon $salon): static
    {
        if ($this->ownedSalons->removeElement($salon)) {
            // set the owning side to null (unless already changed)
            if ($salon->getOwner() === $this) {
                $salon->setOwner(null);
            }
        }

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTime
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTime $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
