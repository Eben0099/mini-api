<?php

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: "ServiceUpdateDto",
    description: "Données pour modifier un service existant",
    type: "object"
)]
class ServiceUpdateDto
{
    #[OA\Property(
        property: "name",
        description: "Nom du service",
        type: "string",
        minLength: 2,
        maxLength: 255,
        example: "Coupe + Brushing Premium"
    )]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    public ?string $name = null;

    #[OA\Property(
        property: "description",
        description: "Description détaillée du service",
        type: "string",
        maxLength: 1000,
        example: "Coupe personnalisée avec brushing professionnel et soins capillaires"
    )]
    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères"
    )]
    public ?string $description = null;

    #[OA\Property(
        property: "durationMinutes",
        description: "Durée du service en minutes",
        type: "integer",
        minimum: 5,
        maximum: 480,
        example: 90
    )]
    #[Assert\Range(
        min: 5,
        max: 480,
        notInRangeMessage: "La durée doit être comprise entre {{ min }} et {{ max }} minutes"
    )]
    public ?int $durationMinutes = null;

    #[OA\Property(
        property: "priceCents",
        description: "Prix du service en centimes",
        type: "integer",
        minimum: 1,
        example: 5500
    )]
    #[Assert\Positive(message: "Le prix doit être positif")]
    public ?int $priceCents = null;

    #[OA\Property(
        property: "isActive",
        description: "Service actif ou non",
        type: "boolean",
        example: true
    )]
    public ?bool $isActive = null;
}
