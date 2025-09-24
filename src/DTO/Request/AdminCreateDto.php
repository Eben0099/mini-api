<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminCreateDto",
    description: "Données requises pour créer un administrateur",
    type: "object",
    required: ["email", "password", "firstName", "lastName"]
)]
class AdminCreateDto
{
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "Invalid email format")]
    #[OA\Property(
        property: "email",
        description: "Adresse email de l'administrateur (doit être unique)",
        type: "string",
        format: "email",
        example: "admin@example.com"
    )]
    public string $email;

    #[Assert\NotBlank(message: "Password is required")]
    #[Assert\Length(min: 8, minMessage: "Password must be at least 8 characters long")]
    #[OA\Property(
        property: "password",
        description: "Mot de passe administrateur (minimum 8 caractères)",
        type: "string",
        format: "password",
        minLength: 8,
        example: "adminSecurePassword123"
    )]
    public string $password;

    #[Assert\NotBlank(message: "First name is required")]
    #[Assert\Length(min: 2, max: 50, minMessage: "First name must be at least 2 characters", maxMessage: "First name must be less than 50 characters")]
    #[OA\Property(
        property: "firstName",
        description: "Prénom de l'administrateur",
        type: "string",
        minLength: 2,
        maxLength: 50,
        example: "Admin"
    )]
    public string $firstName;

    #[Assert\NotBlank(message: "Last name is required")]
    #[Assert\Length(min: 2, max: 50, minMessage: "Last name must be at least 2 characters", maxMessage: "Last name must be less than 50 characters")]
    #[OA\Property(
        property: "lastName",
        description: "Nom de famille de l'administrateur",
        type: "string",
        minLength: 2,
        maxLength: 50,
        example: "System"
    )]
    public string $lastName;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';
        $dto->firstName = $data['firstName'] ?? '';
        $dto->lastName = $data['lastName'] ?? '';

        return $dto;
    }
}
