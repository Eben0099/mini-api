<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class AvailabilityExceptionCreateDto
{
    public ?int $salonId = null;

    public ?int $stylistId = null;

    #[Assert\NotBlank(message: 'La date est requise')]
    #[Assert\Date(message: 'Format de date invalide (Y-m-d attendu)')]
    public string $date; // Format: Y-m-d

    #[Assert\NotNull(message: 'Le champ closed est requis')]
    public bool $closed;

    public ?string $reason = null;

    #[Assert\Callback]
    public function validateEntityReference($payload, $context): void
    {
        if ($this->salonId === null && $this->stylistId === null) {
            $context->buildViolation('Au moins un salon ou un coiffeur doit être spécifié')
                ->atPath('salonId')
                ->addViolation();
        }

        if ($this->salonId !== null && $this->stylistId !== null) {
            $context->buildViolation('Vous ne pouvez spécifier qu\'un salon OU un coiffeur, pas les deux')
                ->atPath('salonId')
                ->addViolation();
        }
    }

    public function getDateAsDateTime(): \DateTimeInterface
    {
        return new \DateTime($this->date);
    }
}
