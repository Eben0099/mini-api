<?php

namespace App\DTO\Response;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AuthResponse",
    description: "Réponse d'authentification avec informations utilisateur et tokens",
    type: "object"
)]
class AuthResponseDto
{
    #[OA\Property(
        property: "message",
        description: "Message de confirmation",
        type: "string",
        example: "Authentication successful"
    )]
    public string $message;

    #[OA\Property(
        property: "token",
        description: "Token JWT pour l'authentification",
        type: "string",
        example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
    )]
    public ?string $token = null;

    #[OA\Property(
        property: "refreshToken",
        description: "Token de rafraîchissement pour renouveler le JWT",
        type: "string",
        example: "def50200a8f4c123..."
    )]
    public ?string $refreshToken = null;

    #[OA\Property(
        property: "user",
        description: "Informations de l'utilisateur",
        ref: "#/components/schemas/UserResponse"
    )]
    public ?UserResponseDto $user = null;

    public function __construct(string $message, ?string $token = null, ?string $refreshToken = null, ?UserResponseDto $user = null)
    {
        $this->message = $message;
        $this->token = $token;
        $this->refreshToken = $refreshToken;
        $this->user = $user;
    }

    public function toArray(): array
    {
        $data = ['message' => $this->message];

        if ($this->token) {
            $data['token'] = $this->token;
        }

        if ($this->refreshToken) {
            $data['refreshToken'] = $this->refreshToken;
        }

        if ($this->user) {
            $data['user'] = $this->user->toArray();
        }

        return $data;
    }

    /**
     * Créer une réponse pour un utilisateur qui doit vérifier son email
     */
    public static function requiresVerification(UserResponseDto $user): self
    {
        return new self(
            message: 'User registered successfully. Please check your email to verify your account.',
            token: null,
            refreshToken: null,
            user: $user
        );
    }

    /**
     * Créer une réponse de succès avec utilisateur
     */
    public static function success(UserResponseDto $user, ?string $token = null, ?string $refreshToken = null): self
    {
        return new self(
            message: 'Authentication successful',
            token: $token,
            refreshToken: $refreshToken,
            user: $user
        );
    }
}
