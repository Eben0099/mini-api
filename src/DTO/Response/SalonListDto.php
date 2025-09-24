<?php

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SalonListDto",
    description: "Informations de base d'un salon pour les listes",
    type: "object"
)]
class SalonListDto
{
    #[OA\Property(
        property: "id",
        description: "Identifiant unique du salon",
        type: "integer",
        example: 1
    )]
    public ?int $id = null;

    #[OA\Property(
        property: "name",
        description: "Nom du salon",
        type: "string",
        example: "Salon Beauté Parisienne"
    )]
    public ?string $name = null;

    #[OA\Property(
        property: "slug",
        description: "Slug du salon pour les URLs",
        type: "string",
        example: "salon-beaute-parisienne"
    )]
    public ?string $slug = null;

    #[OA\Property(
        property: "city",
        description: "Ville du salon",
        type: "string",
        example: "Paris"
    )]
    public ?string $city = null;

    #[OA\Property(
        property: "averageRating",
        description: "Note moyenne du salon",
        type: "number",
        format: "float",
        minimum: 0,
        maximum: 5,
        example: 4.5
    )]
    public ?float $averageRating = null;

    #[OA\Property(
        property: "servicesCount",
        description: "Nombre de services proposés",
        type: "integer",
        example: 8
    )]
    public ?int $servicesCount = null;

    #[OA\Property(
        property: "stylistsCount",
        description: "Nombre de coiffeurs dans le salon",
        type: "integer",
        example: 3
    )]
    public ?int $stylistsCount = null;
}
