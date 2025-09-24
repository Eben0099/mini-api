<?php

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ServiceResponseDto",
    description: "Informations complètes d'un service",
    type: "object"
)]
class ServiceResponseDto
{
    #[OA\Property(
        property: "id",
        description: "Identifiant unique du service",
        type: "integer",
        example: 1
    )]
    public ?int $id = null;

    #[OA\Property(
        property: "name",
        description: "Nom du service",
        type: "string",
        example: "Coupe + Brushing"
    )]
    public ?string $name = null;

    #[OA\Property(
        property: "description",
        description: "Description détaillée du service",
        type: "string",
        example: "Coupe personnalisée avec brushing professionnel"
    )]
    public ?string $description = null;

    #[OA\Property(
        property: "durationMinutes",
        description: "Durée du service en minutes",
        type: "integer",
        example: 60
    )]
    public ?int $durationMinutes = null;

    #[OA\Property(
        property: "priceCents",
        description: "Prix du service en centimes",
        type: "integer",
        example: 4500
    )]
    public ?int $priceCents = null;

    #[OA\Property(
        property: "priceEuros",
        description: "Prix du service en euros",
        type: "number",
        format: "float",
        example: 45.0
    )]
    public ?float $priceEuros = null;

    #[OA\Property(
        property: "isActive",
        description: "Service actif ou non",
        type: "boolean",
        example: true
    )]
    public ?bool $isActive = null;

    #[OA\Property(
        property: "createdAt",
        description: "Date de création du service",
        type: "string",
        format: "date-time",
        example: "2024-01-15T10:30:00+00:00"
    )]
    public ?\DateTimeImmutable $createdAt = null;

    #[OA\Property(
        property: "updatedAt",
        description: "Date de dernière mise à jour du service",
        type: "string",
        format: "date-time",
        example: "2024-01-15T15:45:00+00:00"
    )]
    public ?\DateTimeImmutable $updatedAt = null;
}
