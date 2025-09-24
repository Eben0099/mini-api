<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LoginRequest",
    description: "Données de connexion utilisateur",
    type: "object",
    required: ["email", "password"]
)]
class LoginRequestDto
{
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "Invalid email format")]
    #[OA\Property(
        property: "email",
        description: "Adresse email de l'utilisateur",
        type: "string",
        format: "email",
        example: "john.doe@example.com"
    )]
    public string $email;

    #[Assert\NotBlank(message: "Password is required")]
    #[OA\Property(
        property: "password",
        description: "Mot de passe de l'utilisateur",
        type: "string",
        format: "password",
        example: "mySecurePassword123"
    )]
    public string $password;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';

        return $dto;
    }
}
