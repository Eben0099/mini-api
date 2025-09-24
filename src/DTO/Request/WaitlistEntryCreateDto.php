<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class WaitlistEntryCreateDto
{
    #[Assert\NotBlank(message: 'Le salon est requis')]
    public int $salonId;

    #[Assert\NotBlank(message: 'Le service est requis')]
    public int $serviceId;

    #[Assert\NotBlank(message: 'La date et heure de début souhaitée est requise')]
    #[Assert\DateTime(message: 'Format de date invalide')]
    public string $desiredStartRangeStart; // Format: Y-m-d H:i:s

    #[Assert\NotBlank(message: 'La date et heure de fin souhaitée est requise')]
    #[Assert\DateTime(message: 'Format de date invalide')]
    public string $desiredStartRangeEnd; // Format: Y-m-d H:i:s

    #[Assert\Callback]
    public function validateDateRange($payload, $context): void
    {
        $start = new \DateTimeImmutable($this->desiredStartRangeStart);
        $end = new \DateTimeImmutable($this->desiredStartRangeEnd);

        if ($start >= $end) {
            $context->buildViolation('La date de début doit être antérieure à la date de fin')
                ->atPath('desiredStartRangeStart')
                ->addViolation();
        }
    }

    public function getDesiredStartRangeStartAsDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->desiredStartRangeStart);
    }

    public function getDesiredStartRangeEndAsDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->desiredStartRangeEnd);
    }
}
