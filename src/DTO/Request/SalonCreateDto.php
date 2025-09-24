<?php

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: "SalonCreateDto",
    description: "Données pour créer un nouveau salon",
    type: "object",
    required: ["name", "address", "city", "lat", "lng", "openHours"]
)]
class SalonCreateDto
{
    #[OA\Property(
        property: "name",
        description: "Nom du salon",
        type: "string",
        minLength: 2,
        maxLength: 255,
        example: "Salon Beauté Parisienne"
    )]
    #[Assert\NotBlank(message: "Le nom du salon est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères"
    )]
    public ?string $name = null;

    #[OA\Property(
        property: "address",
        description: "Adresse complète du salon",
        type: "string",
        minLength: 5,
        maxLength: 500,
        example: "123 Rue de la Beauté, 75001 Paris"
    )]
    #[Assert\NotBlank(message: "L'adresse du salon est obligatoire")]
    #[Assert\Length(
        min: 5,
        max: 500,
        minMessage: "L'adresse doit contenir au moins {{ limit }} caractères",
        maxMessage: "L'adresse ne peut pas dépasser {{ limit }} caractères"
    )]
    public ?string $address = null;

    #[OA\Property(
        property: "city",
        description: "Ville du salon",
        type: "string",
        minLength: 2,
        maxLength: 100,
        example: "Paris"
    )]
    #[Assert\NotBlank(message: "La ville du salon est obligatoire")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "La ville doit contenir au moins {{ limit }} caractères",
        maxMessage: "La ville ne peut pas dépasser {{ limit }} caractères"
    )]
    public ?string $city = null;

    #[OA\Property(
        property: "lat",
        description: "Latitude du salon",
        type: "number",
        format: "float",
        minimum: -90,
        maximum: 90,
        example: 48.8566
    )]
    #[Assert\Range(
        min: -90,
        max: 90,
        notInRangeMessage: "La latitude doit être comprise entre {{ min }} et {{ max }}"
    )]
    public ?float $lat = null;

    #[OA\Property(
        property: "lng",
        description: "Longitude du salon",
        type: "number",
        format: "float",
        minimum: -180,
        maximum: 180,
        example: 2.3522
    )]
    #[Assert\Range(
        min: -180,
        max: 180,
        notInRangeMessage: "La longitude doit être comprise entre {{ min }} et {{ max }}"
    )]
    public ?float $lng = null;

    #[OA\Property(
        property: "openHours",
        description: "Horaires d'ouverture du salon par jour de la semaine",
        type: "object",
        example: '{"monday": {"open": "09:00", "close": "18:00"}, "tuesday": {"open": "09:00", "close": "18:00"}, "wednesday": {"open": "09:00", "close": "18:00"}, "thursday": {"open": "09:00", "close": "18:00"}, "friday": {"open": "09:00", "close": "18:00"}, "saturday": {"open": "09:00", "close": "17:00"}, "sunday": null}'
    )]
    #[Assert\NotNull(message: "Les horaires d'ouverture sont obligatoires")]
    public ?array $openHours = null;
}
