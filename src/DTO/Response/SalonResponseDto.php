<?php

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SalonResponseDto",
    description: "Informations complètes d'un salon",
    type: "object"
)]
class SalonResponseDto
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
        property: "address",
        description: "Adresse complète du salon",
        type: "string",
        example: "123 Rue de la Beauté, 75001 Paris"
    )]
    public ?string $address = null;

    #[OA\Property(
        property: "city",
        description: "Ville du salon",
        type: "string",
        example: "Paris"
    )]
    public ?string $city = null;

    #[OA\Property(
        property: "lat",
        description: "Latitude du salon",
        type: "number",
        format: "float",
        example: 48.8566
    )]
    public ?float $lat = null;

    #[OA\Property(
        property: "lng",
        description: "Longitude du salon",
        type: "number",
        format: "float",
        example: 2.3522
    )]
    public ?float $lng = null;

    #[OA\Property(
        property: "openHours",
        description: "Horaires d'ouverture du salon",
        type: "object",
        example: '{"monday": {"open": "09:00", "close": "18:00"}, "tuesday": {"open": "09:00", "close": "18:00"}, "wednesday": {"open": "09:00", "close": "18:00"}, "thursday": {"open": "09:00", "close": "18:00"}, "friday": {"open": "09:00", "close": "18:00"}, "saturday": {"open": "09:00", "close": "17:00"}, "sunday": null}'
    )]
    public ?array $openHours = null;

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
        property: "createdAt",
        description: "Date de création du salon",
        type: "string",
        format: "date-time",
        example: "2024-01-15T10:30:00+00:00"
    )]
    public ?\DateTimeImmutable $createdAt = null;

    #[OA\Property(
        property: "updatedAt",
        description: "Date de dernière mise à jour",
        type: "string",
        format: "date-time",
        example: "2024-01-15T15:45:00+00:00"
    )]
    public ?\DateTimeImmutable $updatedAt = null;

    #[OA\Property(
        property: "owner",
        description: "Informations du propriétaire du salon",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 1),
            new OA\Property(property: "firstName", type: "string", example: "Marie"),
            new OA\Property(property: "lastName", type: "string", example: "Dubois"),
            new OA\Property(property: "email", type: "string", format: "email", example: "marie.dubois@example.com")
        ]
    )]
    public ?array $owner = null;

    #[OA\Property(
        property: "services",
        description: "Services proposés par le salon",
        type: "array",
        items: new OA\Items(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "Coupe + Brushing"),
                new OA\Property(property: "description", type: "string", example: "Coupe personnalisée avec brushing professionnel"),
                new OA\Property(property: "durationMinutes", type: "integer", example: 60),
                new OA\Property(property: "priceCents", type: "integer", example: 4500),
                new OA\Property(property: "priceEuros", type: "number", format: "float", example: 45.0),
                new OA\Property(property: "isActive", type: "boolean", example: true)
            ]
        )
    )]
    public ?array $services = null;

    #[OA\Property(
        property: "stylists",
        description: "Coiffeurs travaillant dans le salon",
        type: "array",
        items: new OA\Items(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(
                    property: "user",
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 5),
                        new OA\Property(property: "firstName", type: "string", example: "Pierre"),
                        new OA\Property(property: "lastName", type: "string", example: "Martin"),
                        new OA\Property(property: "email", type: "string", format: "email", example: "pierre.martin@example.com")
                    ]
                ),
                new OA\Property(property: "languages", type: "array", items: new OA\Items(type: "string"), example: ["fr", "en"]),
                new OA\Property(property: "averageRating", type: "number", format: "float", example: 4.2)
            ]
        )
    )]
    public ?array $stylists = null;
}
