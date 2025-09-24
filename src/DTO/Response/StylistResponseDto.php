<?php

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "StylistResponseDto",
    description: "Informations complètes d'un coiffeur",
    type: "object"
)]
class StylistResponseDto
{
    #[OA\Property(
        property: "id",
        description: "Identifiant unique du coiffeur",
        type: "integer",
        example: 1
    )]
    public ?int $id = null;

    #[OA\Property(
        property: "user",
        description: "Informations de l'utilisateur coiffeur",
        type: "object",
        properties: [
            new OA\Property(property: "id", type: "integer", example: 5),
            new OA\Property(property: "firstName", type: "string", example: "Marie"),
            new OA\Property(property: "lastName", type: "string", example: "Dubois"),
            new OA\Property(property: "email", type: "string", format: "email", example: "marie.dubois@example.com")
        ]
    )]
    public ?array $user = null;

    #[OA\Property(
        property: "languages",
        description: "Langues parlées par le coiffeur",
        type: "array",
        items: new OA\Items(type: "string"),
        example: ["fr", "en", "es"]
    )]
    public ?array $languages = null;

    #[OA\Property(
        property: "skills",
        description: "Services que le coiffeur peut réaliser",
        type: "array",
        items: new OA\Items(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "name", type: "string", example: "Coupe + Brushing"),
                new OA\Property(property: "description", type: "string", example: "Coupe personnalisée avec brushing professionnel")
            ]
        )
    )]
    public ?array $skills = null;

    #[OA\Property(
        property: "averageRating",
        description: "Note moyenne du coiffeur",
        type: "number",
        format: "float",
        minimum: 0,
        maximum: 5,
        example: 4.2
    )]
    public ?float $averageRating = null;

    #[OA\Property(
        property: "createdAt",
        description: "Date d'ajout du coiffeur au salon",
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
}
