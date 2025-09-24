<?php
// Script de test pour vérifier les disponibilités

require_once 'vendor/autoload.php';

use App\Service\AvailabilityService;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$paths = [__DIR__ . '/src/Entity'];
$isDevMode = true;

// Configuration de la base de données
$dbParams = [
    'driver'   => 'pdo_mysql',
    'host'     => 'localhost',
    'port'     => '4310',
    'user'     => 'symfony',
    'password' => 'symfony',
    'dbname'   => 'mini_api',
];

$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
$entityManager = EntityManager::create($dbParams, $config);

try {
    echo "=== TEST DES DISPONIBILITÉS ===\n\n";

    $availabilityService = new AvailabilityService(
        $entityManager,
        $entityManager->getRepository(\App\Entity\Booking::class),
        $entityManager->getRepository(\App\Entity\AvailabilityException::class)
    );

    // Récupérer le salon
    $salon = $entityManager->getRepository(\App\Entity\Salon::class)->find(1);
    if (!$salon) {
        echo "❌ Aucun salon trouvé\n";
        exit(1);
    }

    echo "Salon trouvé : {$salon->getName()}\n";

    // Récupérer un service
    $service = $entityManager->getRepository(\App\Entity\Service::class)->find(1);
    if (!$service) {
        echo "❌ Aucun service trouvé\n";
        exit(1);
    }

    echo "Service trouvé : {$service->getName()} (durée: {$service->getDurationMinutes()} min)\n\n";

    // Tester les disponibilités pour le 15 janvier 2025 (mercredi)
    $date = new \DateTimeImmutable('2025-01-15');
    $duration = 60; // 60 minutes

    echo "Test des disponibilités pour le {$date->format('l d/m/Y')} :\n";
    echo "Service : {$service->getName()}\n";
    echo "Durée : {$duration} minutes\n\n";

    $availableSlots = $availabilityService->getAvailableSlots($salon, $service, $date, $duration);

    if (empty($availableSlots)) {
        echo "❌ AUCUN CRÉNEAU DISPONIBLE\n";

        // Debug : vérifier les stylists
        $stylists = $salon->getStylists();
        echo "\nDebug - Stylists du salon :\n";
        foreach ($stylists as $stylist) {
            echo "- {$stylist->getUser()->getFirstName()} {$stylist->getUser()->getLastName()}\n";
            echo "  Compétences : ";
            $skills = $stylist->getSkills();
            if ($skills->isEmpty()) {
                echo "AUCUNE ❌\n";
            } else {
                $skillNames = [];
                foreach ($skills as $skill) {
                    $skillNames[] = $skill->getName();
                }
                echo implode(', ', $skillNames) . " ✅\n";
            }
        }

        // Debug : vérifier les horaires du salon
        $openHours = $salon->getOpenHours();
        echo "\nDebug - Horaires du salon pour {$date->format('l')} :\n";
        $dayName = strtolower($date->format('l'));
        if (isset($openHours[$dayName])) {
            echo "Horaires : " . json_encode($openHours[$dayName]) . " ✅\n";
        } else {
            echo "❌ AUCUN HORAIRE DÉFINI pour ce jour\n";
        }

    } else {
        echo "✅ CRÉNEAUX DISPONIBLES :\n";
        foreach ($availableSlots as $stylistId => $data) {
            $stylist = $data['stylist'];
            $slots = $data['slots'];

            echo "\nStylist : {$stylist->getUser()->getFirstName()} {$stylist->getUser()->getLastName()}\n";
            echo "Créneaux disponibles : " . implode(', ', $slots) . "\n";
        }
    }

    echo "\n=== FIN DU TEST ===\n";

} catch (Exception $e) {
    echo "Erreur lors du test : " . $e->getMessage() . "\n";
    echo "Stack trace : " . $e->getTraceAsString() . "\n";
}
?>
