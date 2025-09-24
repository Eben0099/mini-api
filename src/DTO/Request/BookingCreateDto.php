<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class BookingCreateDto
{
    #[Assert\NotBlank(message: 'Le salon est requis')]
    public int $salonId;

    #[Assert\NotBlank(message: 'Le coiffeur est requis')]
    public int $stylistId;

    #[Assert\NotBlank(message: 'Le service est requis')]
    public int $serviceId;

    #[Assert\NotBlank(message: 'La date et heure de début sont requises')]
    #[Assert\DateTime(message: 'Format de date invalide')]
    public string $startAt; // Format: Y-m-d H:i:s

    public function getStartAtAsDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->startAt);
    }
}
