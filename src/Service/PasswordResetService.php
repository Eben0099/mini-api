<?php

namespace App\Service;

use App\DTO\Request\PasswordForgotRequestDto;
use App\DTO\Request\PasswordResetRequestDto;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Environment;

class PasswordResetService
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

    public function requestReset(PasswordForgotRequestDto $dto): void
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

        $user = $this->userRepository->findOneBy(['email' => $dto->email]);

        // Si l'utilisateur n'existe pas, on ne fait rien pour des raisons de sécurité
        if (!$user) {
            return;
        }

        // Générer un token de réinitialisation
        $resetToken = bin2hex(random_bytes(32));
        $resetExpires = new \DateTime('+1 hour');

        $user->setResetToken($resetToken);
        $user->setResetTokenExpiresAt($resetExpires);

        $this->entityManager->flush();

        // Envoyer l'email de réinitialisation
        $this->sendResetEmail($user);
    }

    public function resetPassword(PasswordResetRequestDto $dto): void
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

        $user = $this->userRepository->findOneBy(['resetToken' => $dto->token]);

        if (!$user) {
            throw new \Symfony\Component\HttpFoundation\Exception\BadRequestException('Invalid reset token');
        }

        if (!$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTime()) {
            throw new \Symfony\Component\HttpFoundation\Exception\BadRequestException('Reset token has expired');
        }

        // Mettre à jour le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        // Réinitialiser les tokens
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);

        $this->entityManager->flush();
    }

    private function sendResetEmail(\App\Entity\User $user): void
    {
        $resetUrl = $this->urlGenerator->generate(
            'auth_reset_password',
            ['token' => $user->getResetToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from('noreply@yourapp.com')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/password_reset.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
            ]);

        $this->mailer->send($email);
    }
}
