<?php

namespace App\Service;

use App\DTO\Request\AdminCreateDto;
use App\DTO\Request\LoginRequestDto;
use App\DTO\Request\UserCreateDto;
use App\DTO\Response\AuthResponseDto;
use App\DTO\Response\UserResponseDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

class AuthService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
    ) {}

    public function register(UserCreateDto $dto): AuthResponseDto
    {
        // Validation des données
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new \RuntimeException('Validation failed: ' . json_encode($errorMessages));
        }

        // Vérifier si l'email existe déjà
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            throw new \RuntimeException('Email already exists');
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($dto->email);
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setRoles([$dto->getRole()]);

        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        // Générer un token de vérification d'email
        $verificationToken = bin2hex(random_bytes(32));
        $user->setVerificationToken($verificationToken);
        $user->setIsVerified(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer l'email de vérification
        $this->sendVerificationEmail($user);

        return AuthResponseDto::requiresVerification(UserResponseDto::fromEntity($user));
    }

    public function login(LoginRequestDto $dto): AuthResponseDto
    {
        $user = $this->userRepository->findOneBy(['email' => $dto->email]);

        if (!$user) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Vérifier le mot de passe
        if (!$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Vérifier si l'email est vérifié
        if (!$user->isVerified()) {
            throw new \RuntimeException('Please verify your email before logging in');
        }

        return AuthResponseDto::success(UserResponseDto::fromEntity($user));
    }

    public function createAdmin(AdminCreateDto $dto): UserResponseDto
    {
        // Validation des données
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            throw new \RuntimeException('Validation failed: ' . json_encode($errorMessages));
        }

        // Vérifier si l'email existe déjà
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            throw new \RuntimeException('Email already exists');
        }

        // Créer l'admin
        $user = new User();
        $user->setEmail($dto->email);
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsVerified(true); // Les admins sont automatiquement vérifiés

        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return UserResponseDto::fromEntity($user);
    }

    public function loginAdmin(LoginRequestDto $dto): AuthResponseDto
    {
        $user = $this->userRepository->findOneBy(['email' => $dto->email]);

        if (!$user) {
            throw new AuthenticationException('Invalid credentials');
        }

        // Vérifier que c'est un admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            throw new AuthenticationException('Access denied');
        }

        // Vérifier le mot de passe
        if (!$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new AuthenticationException('Invalid credentials');
        }

        return AuthResponseDto::success(UserResponseDto::fromEntity($user));
    }

    public function verifyEmail(string $token): AuthResponseDto
    {
        $user = $this->userRepository->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            throw new \RuntimeException('Invalid verification token');
        }

        if ($user->isVerified()) {
            throw new \RuntimeException('Account already verified');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $this->entityManager->flush();

        return AuthResponseDto::success(UserResponseDto::fromEntity($user));
    }

    public function resendVerificationEmail(string $email): array
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            // Ne pas révéler si l'email existe ou non pour des raisons de sécurité
            return ['message' => 'If the email exists, a verification email has been sent.'];
        }

        if ($user->isVerified()) {
            return ['message' => 'Account already verified.'];
        }

        // Générer un nouveau token
        $verificationToken = bin2hex(random_bytes(32));
        $user->setVerificationToken($verificationToken);
        $this->entityManager->flush();

        // Renvoyer l'email
        $this->sendVerificationEmail($user);

        return ['message' => 'Verification email sent.'];
    }

    private function sendVerificationEmail(User $user): void
    {
        $verificationUrl = $this->urlGenerator->generate(
            'auth_verify_email',
            ['token' => $user->getVerificationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from('noreply@yourapp.com')
            ->to($user->getEmail())
            ->subject('Vérification de votre adresse email')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }
}
