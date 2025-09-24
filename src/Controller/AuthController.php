<?php

namespace App\Controller;

use App\DTO\Request\AdminCreateDto;
use App\DTO\Request\LoginRequestDto;
use App\DTO\Request\UserCreateDto;
use App\Entity\User;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;


/**
 * Contrôleur d'authentification utilisateur.
 * Fournit les endpoints: inscription, login, refresh token, vérification d'email,
 * renvoi d'email de vérification, profil courant et création d'admin.
 */
class AuthController extends AbstractController
{
    #[Route('/api/auth/register', name: 'auth_register', methods: ['POST'])]
    #[OA\Post(
        path: "/api/auth/register",
        summary: "Créer un nouvel utilisateur",
        description: "Inscription d'un nouvel utilisateur avec email, mot de passe et username",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de création d'utilisateur",
            content: new OA\JsonContent(ref: "#/components/schemas/UserCreateDto")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Utilisateur créé avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "User registered successfully"),
                        new OA\Property(property: "user", ref: "#/components/schemas/UserResponse")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erreur de validation - Email déjà existant ou données invalides",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),
            new OA\Response(
                response: 422,
                description: "Données d'entrée invalides",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function register(Request $request, AuthService $authService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'error' => 'Invalid JSON format',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }

            // On utilise UserCreateDto tel que demandé
            $dto = UserCreateDto::fromRequest($data);
            $response = $authService->register($dto); // Retourne un AuthResponseDto

            return $this->json($response, Response::HTTP_CREATED);

        } catch (\RuntimeException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            // Ajoutez le logging pour debugger
            error_log('Registration error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return $this->json([
                'error' => 'An unexpected error occurred: ' . $e->getMessage(),
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/api/auth/login', name: 'auth_login', methods: ['POST'])]
    #[OA\Post(
        path: "/api/auth/login",
        summary: "Connexion utilisateur",
        description: "Authentification d'un utilisateur avec username/email et mot de passe",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Identifiants de connexion",
            content: new OA\JsonContent(ref: "#/components/schemas/LoginRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Connexion réussie - Retourne les tokens JWT",
                content: new OA\JsonContent(ref: "#/components/schemas/LoginResponse")
            ),
            new OA\Response(
                response: 401,
                description: "Identifiants invalides",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function login(Request $request, AuthService $authService, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $entityManager, RefreshTokenGeneratorInterface $refreshTokenGenerator): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'error' => 'Invalid JSON format',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }

            $dto = LoginRequestDto::fromRequest($data);
            $userResponse = $authService->login($dto);

            // Récupérer l'utilisateur depuis la base de données pour générer le token
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $dto->email]);

            if (!$user) {
                throw new \RuntimeException('User not found');
            }

            $token = $jwtManager->create($user);

            // Générer le refresh token avec Gesdinet
            $refreshTokenEntity = $refreshTokenGenerator->createForUserWithTtl($user, 604800); // 7 jours

            // Créer la réponse avec le token JWT et le refresh token
            return $this->json([
                'message' => 'Authentication successful',
                'token' => $token,
                'refreshToken' => $refreshTokenEntity->getRefreshToken()
            ], Response::HTTP_OK);

        } catch (\RuntimeException|\Symfony\Component\HttpFoundation\Exception\BadRequestException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/auth/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    #[OA\Post(
        path: "/api/auth/refresh",
        summary: "Rafraîchir le token JWT",
        description: "Utilise un refresh token pour obtenir un nouveau token JWT",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Refresh token",
            content: new OA\JsonContent(
                type: "object",
                required: ["refresh_token"],
                properties: [
                    new OA\Property(property: "refresh_token", type: "string", description: "Le refresh token reçu lors de la connexion")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Nouveau token généré avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "token", type: "string", description: "Nouveau token JWT"),
                        new OA\Property(property: "refreshToken", type: "string", description: "Nouveau refresh token")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Refresh token manquant ou invalide",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function refreshToken(Request $request, RefreshTokenManagerInterface $refreshTokenManager, JWTTokenManagerInterface $jwtManager, RefreshTokenGeneratorInterface $refreshTokenGenerator, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'error' => 'Invalid JSON format',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }

            $refreshTokenString = $data['refresh_token'] ?? null;

            if (!$refreshTokenString) {
                return $this->json([
                    'error' => 'Refresh token is required',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }

            // Récupérer le refresh token depuis la base
            $refreshToken = $refreshTokenManager->get($refreshTokenString);

            if (!$refreshToken || !$refreshToken->isValid()) {
                return $this->json([
                    'error' => 'Invalid or expired refresh token',
                    'code' => Response::HTTP_UNAUTHORIZED
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Récupérer l'utilisateur associé au refresh token
            $username = $refreshToken->getUsername();
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $username]);

            if (!$user) {
                return $this->json([
                    'error' => 'User not found',
                    'code' => Response::HTTP_UNAUTHORIZED
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Générer un nouveau JWT token pour l'utilisateur
            $newToken = $jwtManager->create($user);

            // Générer un nouveau refresh token
            $newRefreshTokenEntity = $refreshTokenGenerator->createForUserWithTtl($user, 604800); // 7 jours

            // Supprimer l'ancien refresh token
            $refreshTokenManager->delete($refreshToken);

            return $this->json([
                'token' => $newToken,
                'refreshToken' => $newRefreshTokenEntity->getRefreshToken()
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            error_log('Refresh token error: ' . $e->getMessage());
            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/auth/verify-email', name: 'auth_verify_email', methods: ['GET', 'POST'])]
    #[OA\Get(
        path: "/api/auth/verify-email",
        summary: "Vérifier l'email via lien",
        description: "Vérifier l'adresse email en cliquant sur le lien envoyé par email (GET) ou via API (POST)",
        tags: ["Auth"],
        parameters: [
            new OA\Parameter(
                name: "token",
                in: "query",
                required: false,
                description: "Token de vérification envoyé par email (pour GET)",
                schema: new OA\Schema(type: "string")
            )
        ],
        requestBody: new OA\RequestBody(
            required: false,
            description: "Données de vérification (pour POST)",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "token", type: "string", description: "Token de vérification")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Email vérifié avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Email verified successfully. You can now login."),
                        new OA\Property(property: "user", ref: "#/components/schemas/UserResponse")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Token invalide ou expiré",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    #[OA\Post(
        path: "/api/auth/verify-email",
        summary: "Vérifier l'email via API",
        description: "Vérifier l'adresse email via API REST (POST)",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de vérification",
            content: new OA\JsonContent(
                type: "object",
                required: ["token"],
                properties: [
                    new OA\Property(property: "token", type: "string", description: "Token de vérification")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Email vérifié avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Email verified successfully. You can now login."),
                        new OA\Property(property: "user", ref: "#/components/schemas/UserResponse")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Token invalide ou expiré",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function verifyEmail(Request $request, AuthService $authService): JsonResponse
    {
        try {
            $token = '';

            // Pour GET : token dans les paramètres de requête
            if ($request->isMethod('GET')) {
                $token = $request->query->get('token');
            } // Pour POST : token dans le JSON
            elseif ($request->isMethod('POST')) {
                $data = json_decode($request->getContent(), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->json([
                        'error' => 'Invalid JSON format',
                        'code' => Response::HTTP_BAD_REQUEST
                    ], Response::HTTP_BAD_REQUEST);
                }

                $token = $data['token'] ?? '';
            }

            if (empty($token)) {
                return $this->json([
                    'error' => 'Verification token is required',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = $authService->verifyEmail($token);
            return $this->json([
                'message' => 'Email verified successfully. You can now login.',
                'user' => $result->toArray()
            ], Response::HTTP_OK);

        } catch (\RuntimeException|\Symfony\Component\HttpFoundation\Exception\BadRequestException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            error_log('Email verification error: ' . $e->getMessage());
            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/auth/resend-verification', name: 'auth_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request, AuthService $authService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? '';
            if (empty($email)) {
                return $this->json([
                    'error' => 'Email is required'
                ], Response::HTTP_BAD_REQUEST);
            }
            $result = $authService->resendVerificationEmail($email);
            return $this->json($result, Response::HTTP_OK);
        } catch (\RuntimeException|\Symfony\Component\HttpFoundation\Exception\BadRequestException $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An unexpected error occurred'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/auth/me', name: 'auth_me', methods: ['GET'])]
    #[OA\Get(
        path: "/api/auth/me",
        summary: "Informations de l'utilisateur connecté",
        description: "Récupère les informations du profil de l'utilisateur actuellement authentifié",
        tags: ["Auth"],
        security: [["Bearer" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Profil utilisateur récupéré avec succès",
                content: new OA\JsonContent(ref: "#/components/schemas/UserResponse")
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié - Token manquant ou invalide",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Not authenticated',
                'code' => Response::HTTP_UNAUTHORIZED
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName()
        ]);
    }

    #[Route('/api/admin/register', name: 'api_admin_register', methods: ['POST'])]
    #[OA\Post(
        path: "/api/admin/register",
        summary: "Créer un nouvel administrateur",
        description: "Création d'un compte administrateur (réservé aux super-admins)",
        tags: ["Auth"],
        security: [["Bearer" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de création d'administrateur",
            content: new OA\JsonContent(ref: "#/components/schemas/AdminCreateDto")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Administrateur créé avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Admin registered successfully"),
                        new OA\Property(property: "admin", ref: "#/components/schemas/UserResponse")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Erreur de validation - Email ou username déjà existant",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            ),

        ]
    )]
    public function registerAdmin(
        Request     $request,
        AuthService $authService
    ): JsonResponse
    {
        try {

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'error' => 'Invalid JSON format',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }

            $dto = AdminCreateDto::fromRequest($data);
            $adminResponse = $authService->createAdmin($dto);

            return $this->json([
                'message' => 'Admin registered successfully',
                'admin' => $adminResponse
            ], Response::HTTP_CREATED);

        } catch (\RuntimeException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/admin/login', name: 'api_admin_login', methods: ['POST'])]
    #[OA\Post(
        path: "/api/admin/login",
        summary: "Connexion administrateur",
        description: "Authentification d'un administrateur système avec email et mot de passe",
        tags: ["Admin Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Identifiants de connexion administrateur",
            content: new OA\JsonContent(ref: "#/components/schemas/LoginRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Connexion administrateur réussie - Retourne les tokens JWT",
                content: new OA\JsonContent(ref: "#/components/schemas/LoginResponse")
            ),
            new OA\Response(
                response: 401,
                description: "Identifiants invalides ou accès non autorisé",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function loginAdmin(Request $request, AuthService $authService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'error' => 'Invalid JSON format',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }

            $dto = LoginRequestDto::fromRequest($data);
            $result = $authService->loginAdmin($dto);

            return $this->json($result, Response::HTTP_OK);

        } catch (\RuntimeException|\Symfony\Component\HttpFoundation\Exception\BadRequestException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/admin/verify-otp', name: 'api_admin_verify_otp', methods: ['POST'])]
    #[OA\Post(
        path: "/api/admin/verify-otp",
        summary: "Vérifier le code OTP d'un administrateur",
        description: "Valider l'adresse email d'un administrateur à l'aide du code OTP envoyé par email",
        tags: ["Admin Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de vérification OTP administrateur",
            content: new OA\JsonContent(
                type: "object",
                required: ["email", "otp_code"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
                    new OA\Property(property: "otp_code", type: "string", example: "123456")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "OTP administrateur vérifié avec succès - Retourne les tokens JWT",
                content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Erreur de vérification - Code OTP invalide, expiré ou trop de tentatives",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function verifyAdminOtp(Request $request, AuthService $authService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'error' => 'Invalid JSON format',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }

            $email = $data['email'] ?? '';
            $otpCode = $data['otp_code'] ?? $data['otpCode'] ?? '';

            if (empty($email) || empty($otpCode)) {
                return $this->json([
                    'error' => 'Email et code OTP sont requis',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }

            $result = $authService->verifyAdminOtp($email, $otpCode);
            return $this->json($result, Response::HTTP_OK);

        } catch (\RuntimeException|\Symfony\Component\HttpFoundation\Exception\BadRequestException $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An unexpected error occurred',
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/auth/forgot-password', name: 'auth_forgot_password', methods: ['POST'])]
    #[OA\Post(
        path: "/api/auth/forgot-password",
        summary: "Demander la réinitialisation du mot de passe",
        description: "Envoie un email avec un lien de réinitialisation si l'email existe",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Email de l'utilisateur",
            content: new OA\JsonContent(ref: "#/components/schemas/PasswordForgotRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Email de réinitialisation envoyé (si l'email existe)",
                content: new OA\JsonContent(type: "object", properties: [
                    new OA\Property(property: "message", type: "string", example: "If the email exists, a reset link has been sent.")
                ])
            )
        ]
    )]
    public function passwordForgot(Request $request, \App\Service\PasswordResetService $passwordResetService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json([
                'error' => 'Invalid JSON format',
                'code' => Response::HTTP_BAD_REQUEST
            ], Response::HTTP_BAD_REQUEST);
        }

        $dto = \App\DTO\Request\PasswordForgotRequestDto::fromRequest($data);
        $passwordResetService->requestReset($dto);
        return $this->json(['message' => 'If the email exists, a reset link has been sent.']);
    }

    #[Route('/api/auth/reset-password', name: 'auth_reset_password', methods: ['POST'])]
    #[OA\Post(
        path: "/api/auth/reset-password",
        summary: "Réinitialiser le mot de passe",
        description: "Réinitialise le mot de passe à l'aide d'un token reçu par email",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Token et nouveau mot de passe",
            content: new OA\JsonContent(ref: "#/components/schemas/PasswordResetRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Mot de passe réinitialisé avec succès",
                content: new OA\JsonContent(type: "object", properties: [
                    new OA\Property(property: "message", type: "string", example: "Password successfully reset. You can now login.")
                ])
            ),
            new OA\Response(
                response: 400,
                description: "Token invalide ou expiré",
                content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")
            )
        ]
    )]
    public function passwordReset(Request $request, \App\Service\PasswordResetService $passwordResetService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'error' => 'Invalid JSON format',
                    'code' => Response::HTTP_BAD_REQUEST
                ], Response::HTTP_BAD_REQUEST);
            }
            $dto = \App\DTO\Request\PasswordResetRequestDto::fromRequest($data);
            $passwordResetService->resetPassword($dto);
            return $this->json(['message' => 'Password successfully reset. You can now login.']);
        } catch (\Symfony\Component\HttpFoundation\Exception\BadRequestException|\Symfony\Component\Security\Core\Exception\UserNotFoundException $e) {
            return $this->json(['error' => $e->getMessage(), 'code' => Response::HTTP_BAD_REQUEST], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'An unexpected error occurred', 'code' => Response::HTTP_INTERNAL_SERVER_ERROR], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

