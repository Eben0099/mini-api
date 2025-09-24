<?php

// Script pour créer un utilisateur de test

require_once 'vendor/autoload.php';

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// Bootstrap Symfony
$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get(EntityManagerInterface::class);
$passwordHasher = $container->get(UserPasswordHasherInterface::class);

// Vérifier si l'utilisateur existe déjà
$existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);

if ($existingUser) {
    echo "L'utilisateur test@example.com existe déjà.\n";
    exit(0);
}

// Créer un nouvel utilisateur
$user = new User();
$user->setEmail('test@example.com');
$user->setFirstName('Test');
$user->setLastName('User');
$user->setPhone('0123456789');
$user->setIsVerified(true);
$user->setCreatedAt(new \DateTimeImmutable());
$user->setUpdatedAt(new \DateTimeImmutable());

// Hasher le mot de passe
$hashedPassword = $passwordHasher->hashPassword($user, 'password123');
$user->setPassword($hashedPassword);

// Sauvegarder
$entityManager->persist($user);
$entityManager->flush();

echo "✅ Utilisateur de test créé avec succès!\n";
echo "Email: test@example.com\n";
echo "Mot de passe: password123\n";

$kernel->shutdown();
