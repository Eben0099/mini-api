<?php

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LoginResponse",
    description: "Réponse de connexion utilisateur",
    type: "object",
    required: ["message", "token", "refreshToken"]
)]
class LoginResponseDto
{
    #[OA\Property(
        property: "message",
        description: "Message de confirmation de connexion",
        type: "string",
        example: "Authentication successful"
    )]
    public string $message;

    #[OA\Property(
        property: "token",
        description: "Token JWT d'authentification",
        type: "string",
        example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6..."
    )]
    public string $token;

    #[OA\Property(
        property: "refreshToken",
        description: "Token de rafraîchissement JWT",
        type: "string",
        example: "def50200a8f4c123456789abcdef0123456789"
    )]
    public string $refreshToken;

    public static function fromData(string $message, string $token, string $refreshToken): self
    {
        $dto = new self();
        $dto->message = $message;
        $dto->token = $token;
        $dto->refreshToken = $refreshToken;

        return $dto;
    }
}
