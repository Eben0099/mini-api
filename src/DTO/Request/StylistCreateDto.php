<?php

namespace App\DTO\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: "StylistCreateDto",
    description: "Données pour ajouter un coiffeur à un salon",
    type: "object",
    required: ["userId", "languages", "skillIds"]
)]
class StylistCreateDto
{
    #[OA\Property(
        property: "userId",
        description: "ID de l'utilisateur à ajouter comme coiffeur",
        type: "integer",
        minimum: 1,
        example: 5
    )]
    #[Assert\NotBlank(message: "L'ID de l'utilisateur est obligatoire")]
    #[Assert\Positive(message: "L'ID de l'utilisateur doit être positif")]
    public ?int $userId = null;

    #[OA\Property(
        property: "languages",
        description: "Langues parlées par le coiffeur",
        type: "array",
        items: new OA\Items(type: "string", minLength: 2, maxLength: 5),
        example: ["fr", "en", "es"]
    )]
    #[Assert\NotNull(message: "Les langues parlées sont obligatoires")]
    #[Assert\All([
        new Assert\Length(min: 2, max: 5, minMessage: "Chaque langue doit contenir au moins {{ limit }} caractères", maxMessage: "Chaque langue ne peut pas dépasser {{ limit }} caractères")
    ])]
    public ?array $languages = null;

    #[OA\Property(
        property: "skillIds",
        description: "IDs des services que le coiffeur peut réaliser",
        type: "array",
        items: new OA\Items(type: "integer", minimum: 1),
        example: [1, 2, 3]
    )]
    #[Assert\NotNull(message: "Les compétences sont obligatoires")]
    #[Assert\All([
        new Assert\Positive(message: "Chaque ID de service doit être positif")
    ])]
    public ?array $skillIds = null;
}
