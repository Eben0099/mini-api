<?php

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: "SalonUpdateDto",
    description: "Données pour modifier un salon existant",
    type: "object"
)]
class SalonUpdateDto
{
    #[OA\Property(
        property: "name",
        description: "Nom du salon",
        type: "string",
        minLength: 2,
        maxLength: 255,
        example: "Salon Beauté Parisienne Premium"
    )]
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
        example: "456 Avenue de la Mode, 75008 Paris"
    )]
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
        example: 48.8736
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
        example: 2.2950
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
        example: '{"monday": {"open": "08:30", "close": "19:00"}, "tuesday": {"open": "08:30", "close": "19:00"}, "wednesday": {"open": "08:30", "close": "19:00"}, "thursday": {"open": "08:30", "close": "20:00"}, "friday": {"open": "08:30", "close": "20:00"}, "saturday": {"open": "09:00", "close": "18:00"}, "sunday": {"open": "10:00", "close": "17:00"}}'
    )]
    public ?array $openHours = null;
}
