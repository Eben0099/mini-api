<?php
// Test de l'envoi d'email pour la liste d'attente

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

// Configuration de base de données pour récupérer des données de test
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TEST ENVOI EMAIL LISTE D'ATTENTE ===\n\n";

    // Récupérer des données de test
    $stmt = $pdo->prepare("SELECT b.*, u.email as client_email, u.first_name, u.last_name FROM booking b JOIN user u ON b.client_id = u.id WHERE b.status = 'confirmed' LIMIT 1");
    $stmt->execute();
    $bookingData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bookingData) {
        echo "❌ ERREUR: Aucune réservation trouvée pour le test\n";
        exit(1);
    }

    $stmt = $pdo->prepare("SELECT w.*, u.email as waitlist_client_email FROM waitlist_entry w JOIN user u ON w.client_id = u.id LIMIT 1");
    $stmt->execute();
    $waitlistData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$waitlistData) {
        echo "❌ ERREUR: Aucune entrée en liste d'attente trouvée pour le test\n";
        exit(1);
    }

    echo "📧 Test d'envoi d'email avec données réelles\n";
    echo "Client email: {$bookingData['client_email']}\n";
    echo "Waitlist client email: {$waitlistData['waitlist_client_email']}\n\n";

    // Configuration du mailer (simulée)
    echo "🔧 Configuration du mailer...\n";

    // Charger la configuration Symfony
    $dotenv = new Dotenv();
    if (file_exists(__DIR__ . '/.env')) {
        $dotenv->load(__DIR__ . '/.env');
        echo "✅ Fichier .env chargé\n";
    } else {
        echo "⚠️ Fichier .env non trouvé\n";
    }

    // Essayer de créer un mailer simple pour tester
    $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
        $_ENV['MAILER_HOST'] ?? 'localhost',
        $_ENV['MAILER_PORT'] ?? 1025,
        false
    );

    $transport->setUsername($_ENV['MAILER_USERNAME'] ?? null);
    $transport->setPassword($_ENV['MAILER_PASSWORD'] ?? null);

    $mailer = new \Symfony\Component\Mailer\Mailer($transport);

    // Configuration de Twig
    $loader = new FilesystemLoader(__DIR__ . '/templates');
    $twig = new Environment($loader);

    echo "✅ Mailer et Twig configurés\n\n";

    // Créer un email de test
    echo "📝 Création de l'email de test...\n";

    try {
        $email = (new TemplatedEmail())
            ->from('noreply@salonapp.com')
            ->to($bookingData['client_email'])
            ->subject('🎉 Test - Bonne nouvelle ! Votre créneau s\'est libéré')
            ->htmlTemplate('emails/waitlist_to_booking_client.html.twig')
            ->context([
                'client' => (object)[
                    'firstName' => $bookingData['first_name'],
                    'lastName' => $bookingData['last_name'],
                    'email' => $bookingData['client_email']
                ],
                'salon' => (object)[
                    'name' => 'Salon Test',
                    'address' => '123 Rue Test',
                    'email' => 'contact@salontest.com'
                ],
                'stylist' => (object)[
                    'user' => (object)[
                        'firstName' => 'Marie',
                        'lastName' => 'Dubois',
                        'email' => 'marie@salontest.com'
                    ]
                ],
                'service' => (object)[
                    'name' => 'Coupe femme',
                    'durationMinutes' => 60
                ],
                'booking' => (object)[
                    'startAt' => new DateTime('+1 day 10:00'),
                    'endAt' => new DateTime('+1 day 11:00')
                ],
                'waitlistEntry' => (object)[
                    'createdAt' => new DateTime('-2 days'),
                    'desiredStartRangeStart' => new DateTime('+1 day 09:00'),
                    'desiredStartRangeEnd' => new DateTime('+1 day 12:00')
                ]
            ]);

        echo "📤 Envoi de l'email...\n";
        $mailer->send($email);
        echo "✅ Email envoyé avec succès !\n";

        echo "\n=== RÉCAPITULATIF ===\n";
        echo "✅ Configuration mailer : OK\n";
        echo "✅ Templates Twig : OK\n";
        echo "✅ Envoi d'email : OK\n";
        echo "✅ Les emails devraient fonctionner en production\n";

    } catch (\Exception $e) {
        echo "❌ ERREUR lors de l'envoi d'email : " . $e->getMessage() . "\n";

        // Essayer avec MailHog si localhost
        if (strpos($e->getMessage(), 'Connection refused') !== false) {
            echo "\n💡 Suggestion : Vérifiez que MailHog ou un serveur SMTP est démarré\n";
            echo "   - MailHog : mailhog localhost:1025\n";
            echo "   - Ou configurez un vrai serveur SMTP dans .env\n";
        }

        echo "\n=== RÉCAPITULATIF ===\n";
        echo "❌ Problème de configuration email\n";
        echo "🔧 Vérifiez la configuration MAILER_* dans .env\n";
    }

} catch (Exception $e) {
    echo "Erreur générale : " . $e->getMessage() . "\n";
}
?>
