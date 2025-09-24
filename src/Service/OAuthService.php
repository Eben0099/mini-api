<?php

namespace App\Service;

use App\DTO\Response\UserResponseDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class OAuthService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function verifyFirebaseIdToken(string $idToken): array
    {
        // Cette méthode devrait vérifier le token Firebase
        // Pour l'instant, on simule une vérification basique
        // En production, utiliseriez Firebase Admin SDK

        // Simulation de décodage du token
        $claims = [
            'sub' => 'firebase_user_id_' . rand(1000, 9999),
            'email' => 'oauth@example.com',
            'email_verified' => true,
            'name' => 'OAuth User',
            'picture' => 'https://example.com/avatar.jpg',
        ];

        return $claims;
    }

    public function findOrCreateAdminFromFirebaseClaims(array $claims): array
    {
        $firebaseId = $claims['sub'];
        $email = $claims['email'];

        // Chercher un admin existant par email
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user) {
            // Vérifier que c'est un admin
            if (!in_array('ROLE_ADMIN', $user->getRoles())) {
                throw new \RuntimeException('Access denied: not an admin');
            }
            return ['user' => $user, 'isNewUser' => false];
        }

        // Créer un nouvel admin
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName(explode(' ', $claims['name'] ?? 'Admin')[0]);
        $user->setLastName(explode(' ', $claims['name'] ?? 'Admin')[1] ?? 'User');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsVerified(true);

        // Générer un mot de passe aléatoire (l'utilisateur devra le changer)
        $randomPassword = bin2hex(random_bytes(16));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return ['user' => $user, 'isNewUser' => true];
    }

    public function getOAuthUserData(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'roles' => $user->getRoles(),
            'isVerified' => $user->isVerified(),
        ];
    }
}
