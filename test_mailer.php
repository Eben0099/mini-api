<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

// Charger l'environnement
$dotenv = new Dotenv();
if (file_exists(__DIR__ . '/.env')) {
    $dotenv->load(__DIR__ . '/.env');
}

// Créer le transport mailer
$dsn = getenv('MAILER_DSN') ?: 'filesystem://?path=' . __DIR__ . '/var/spool';
$transport = Transport::fromDsn($dsn);

// Créer le mailer
$mailer = new Mailer($transport);

// Créer un email de test
$email = (new Email())
    ->from('test@example.com')
    ->to('recipient@example.com')
    ->subject('Test Mailer Configuration')
    ->text('Ceci est un test pour vérifier que le mailer fonctionne correctement.');

try {
    $mailer->send($email);
    echo "✅ Email envoyé avec succès !" . PHP_EOL;
    echo "📧 Vérifiez le dossier var/spool/ pour voir l'email sauvegardé." . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Erreur lors de l'envoi de l'email : " . $e->getMessage() . PHP_EOL;
}
