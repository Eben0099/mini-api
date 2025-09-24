<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "PasswordForgotRequest",
    description: "Demande de réinitialisation de mot de passe",
    type: "object",
    required: ["email"]
)]
class PasswordForgotRequestDto
{
    #[Assert\NotBlank(message: "Email is required")]
    #[Assert\Email(message: "Invalid email format")]
    #[OA\Property(
        property: "email",
        description: "Adresse email pour laquelle demander la réinitialisation",
        type: "string",
        format: "email",
        example: "user@example.com"
    )]
    public string $email;

    public static function fromRequest(array $data): self
    {
        $dto = new self();
        $dto->email = $data['email'] ?? '';

        return $dto;
    }
}
