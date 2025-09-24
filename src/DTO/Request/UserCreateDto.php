<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UserCreateDto",
    description: "Données requises pour créer un nouvel utilisateur",
    type: "object",
    required: ["email", "password", "firstName", "lastName"]
)]
class UserCreateDto
{
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "Invalid email format")]
    #[OA\Property(
        property: "email",
        description: "Adresse email de l'utilisateur (doit être unique)",
        type: "string",
        format: "email",
        example: "john.doe@example.com"
    )]
    public string $email;

    #[Assert\NotBlank(message: "Password is required")]
    #[Assert\Length(min: 8, minMessage: "Password must be at least 8 characters long")]
    #[OA\Property(
        property: "password",
        description: "Mot de passe (minimum 8 caractères)",
        type: "string",
        format: "password",
        minLength: 8,
        example: "mySecurePassword123"
    )]
    public string $password;

    #[Assert\NotBlank(message: "First name is required")]
    #[Assert\Length(min: 2, max: 50, minMessage: "First name must be at least 2 characters", maxMessage: "First name must be less than 50 characters")]
    #[OA\Property(
        property: "firstName",
        description: "Prénom de l'utilisateur",
        type: "string",
        minLength: 2,
        maxLength: 50,
        example: "Jean"
    )]
    public string $firstName;

    #[Assert\NotBlank(message: "Last name is required")]
    #[Assert\Length(min: 2, max: 50, minMessage: "Last name must be at least 2 characters", maxMessage: "Last name must be less than 50 characters")]
    #[OA\Property(
        property: "lastName",
        description: "Nom de famille de l'utilisateur",
        type: "string",
        minLength: 2,
        maxLength: 50,
        example: "Dupont"
    )]
    public string $lastName;

    #[Assert\Choice(choices: ['client', 'owner'], message: "Account type must be either 'client' or 'owner'")]
    #[OA\Property(
        property: "accountType",
        description: "Type de compte utilisateur",
        type: "string",
        enum: ["client", "owner"],
        default: "client",
        example: "client"
    )]
    public string $accountType = 'client';

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';
        $dto->firstName = $data['firstName'] ?? '';
        $dto->lastName = $data['lastName'] ?? '';
        $dto->accountType = $data['accountType'] ?? 'client';

        return $dto;
    }

    public function getRole(): string
    {
        return match ($this->accountType) {
            'owner' => 'ROLE_OWNER',
            default => 'ROLE_CLIENT',
        };
    }
}
