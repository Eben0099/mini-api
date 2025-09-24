<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class AvailabilityRequestDto
{
    #[Assert\NotBlank(message: 'L\'ID du service est requis')]
    public int $serviceId;

    public ?int $stylistId = null; // Optionnel : null = tous les coiffeurs disponibles

    #[Assert\NotBlank(message: 'La date est requise')]
    #[Assert\Date(message: 'Format de date invalide (Y-m-d attendu)')]
    public string $date; // Format: Y-m-d

    #[Assert\NotBlank(message: 'La durée est requise')]
    #[Assert\Positive(message: 'La durée doit être positive')]
    public int $duration; // En minutes

    public function getDateAsDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable($this->date);
    }
}
