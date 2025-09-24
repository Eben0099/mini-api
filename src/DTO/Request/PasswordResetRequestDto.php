<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PasswordResetRequest",
    description: "Données pour réinitialiser le mot de passe avec un token",
    type: "object",
    required: ["token", "password"]
)]
class PasswordResetRequestDto
{
    #[Assert\NotBlank(message: "Token is required")]
    #[OA\Property(
        property: "token",
        description: "Token de réinitialisation reçu par email",
        type: "string",
        example: "abcd1234efgh5678ijkl9012mnop3456"
    )]
    public string $token;

    #[Assert\NotBlank(message: "Password is required")]
    #[Assert\Length(min: 8, minMessage: "Password must be at least 8 characters long")]
    #[OA\Property(
        property: "password",
        description: "Nouveau mot de passe (minimum 8 caractères)",
        type: "string",
        format: "password",
        minLength: 8,
        example: "newSecurePassword123"
    )]
    public string $password;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->token = $data['token'] ?? '';
        $dto->password = $data['password'] ?? '';

        return $dto;
    }
}
