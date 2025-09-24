<?php

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ErrorResponse",
    description: "Réponse d'erreur standardisée",
    type: "object"
)]
class ErrorResponseDto
{
    #[OA\Property(
        property: "error",
        description: "Message d'erreur",
        type: "string",
        example: "Validation failed"
    )]
    public string $error;

    #[OA\Property(
        property: "code",
        description: "Code de statut HTTP",
        type: "integer",
        example: 400
    )]
    public ?int $code = null;

    #[OA\Property(
        property: "details",
        description: "Détails de l'erreur (optionnel)",
        type: "object",
        example: '{"field": "name", "message": "This field is required"}'
    )]
    public ?array $details = null;

    public function __construct(string $error, ?int $code = null, ?array $details = null)
    {
        $this->error = $error;
        $this->code = $code;
        $this->details = $details;
    }
}
