<?php

namespace App\DTO\Response;

use OpenApi\Attributes as OA;
use App\Entity\User;

#[OA\Schema(
    schema: "UserResponse",
    description: "Informations de l'utilisateur",
    type: "object"
)]
class UserResponseDto
{
    #[OA\Property(
        property: "id",
        description: "Identifiant unique de l'utilisateur",
        type: "integer",
        example: 1
    )]
    public int $id;

    #[OA\Property(
        property: "email",
        description: "Adresse email de l'utilisateur",
        type: "string",
        format: "email",
        example: "user@example.com"
    )]
    public string $email;

    #[OA\Property(
        property: "firstName",
        description: "Prénom de l'utilisateur",
        type: "string",
        example: "Jean"
    )]
    public string $firstName;

    #[OA\Property(
        property: "lastName",
        description: "Nom de famille de l'utilisateur",
        type: "string",
        example: "Dupont"
    )]
    public string $lastName;

    #[OA\Property(
        property: "phone",
        description: "Numéro de téléphone (optionnel)",
        type: "string",
        nullable: true,
        example: "0123456789"
    )]
    public ?string $phone = null;

    #[OA\Property(
        property: "roles",
        description: "Rôles de l'utilisateur",
        type: "array",
        items: new OA\Items(type: "string"),
        example: ["ROLE_USER"]
    )]
    public array $roles;

    #[OA\Property(
        property: "isVerified",
        description: "Email vérifié ou non",
        type: "boolean",
        example: true
    )]
    public bool $isVerified;

    #[OA\Property(
        property: "createdAt",
        description: "Date de création du compte",
        type: "string",
        format: "date-time",
        example: "2024-01-15T10:30:00+00:00"
    )]
    public string $createdAt;

    public static function fromEntity(User $user): self
    {
        $dto = new self();
        $dto->id = $user->getId();
        $dto->email = $user->getEmail();
        $dto->firstName = $user->getFirstName();
        $dto->lastName = $user->getLastName();
        $dto->phone = $user->getPhone();
        $dto->roles = $user->getRoles();
        $dto->isVerified = $user->isVerified();
        $dto->createdAt = $user->getCreatedAt()->format('c');

        return $dto;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'phone' => $this->phone,
            'roles' => $this->roles,
            'isVerified' => $this->isVerified,
            'createdAt' => $this->createdAt,
        ];
    }
}
