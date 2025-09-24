<?php
// Test qui reproduit exactement ce qui se passe dans BookingController

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    echo "=== VÉRIFICATION DES DONNÉES DE BASE ===\n\n";

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier les IDs utilisés dans Postman
    echo "Vérification des entités avec les IDs du salon {{salon_id}} = 1:\n";

    // Salon
    $stmt = $pdo->query("SELECT id, name FROM salon WHERE id = 1");
    $salon = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "- Salon ID 1: " . ($salon ? $salon['name'] : "❌ NON TROUVÉ") . "\n";

    // Services du salon
    $stmt = $pdo->query("SELECT id, name, duration_minutes FROM service WHERE salon_id = 1");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "- Services du salon: " . count($services) . " trouvés\n";
    foreach ($services as $service) {
        echo "  * ID {$service['id']}: {$service['name']} ({$service['duration_minutes']} min)\n";
    }

    // Stylists du salon
    $stmt = $pdo->prepare("
        SELECT s.id, u.first_name, u.last_name
        FROM stylist s
        JOIN user u ON s.user_id = u.id
        WHERE s.salon_id = ?
    ");
    $stmt->execute([1]);
    $stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "- Stylists du salon: " . count($stylists) . " trouvés\n";
    foreach ($stylists as $stylist) {
        echo "  * ID {$stylist['id']}: {$stylist['first_name']} {$stylist['last_name']}\n";
    }

    echo "\n=== VÉRIFICATION DE L'APPEL API ===\n";
    echo "L'utilisateur fait un POST à /api/v1/bookings avec:\n";
    echo "{\n";
    echo "  \"salonId\": 1,\n";
    echo "  \"stylistId\": 7,\n";
    echo "  \"serviceId\": 1,\n";
    echo "  \"startAt\": \"2025-01-15 10:00:00\"\n";
    echo "}\n\n";

    // Vérifier si les IDs existent
    $salonExists = $pdo->query("SELECT COUNT(*) FROM salon WHERE id = 1")->fetchColumn() > 0;
    $stylistExists = $pdo->query("SELECT COUNT(*) FROM stylist WHERE id = 7")->fetchColumn() > 0;
    $serviceExists = $pdo->query("SELECT COUNT(*) FROM service WHERE id = 1")->fetchColumn() > 0;

    echo "Vérification des IDs:\n";
    echo "- Salon ID 1: " . ($salonExists ? "✅ Existe" : "❌ N'existe pas") . "\n";
    echo "- Stylist ID 7: " . ($stylistExists ? "✅ Existe" : "❌ N'existe pas") . "\n";
    echo "- Service ID 1: " . ($serviceExists ? "✅ Existe" : "❌ N'existe pas") . "\n";

    if (!$salonExists || !$stylistExists || !$serviceExists) {
        echo "\n❌ PROBLÈME: Un ou plusieurs IDs n'existent pas!\n";
        exit(1);
    }

    // Vérifier les relations
    echo "\nVérification des relations:\n";

    // Le stylist travaille-t-il dans le salon ?
    $stmt = $pdo->query("SELECT salon_id FROM stylist WHERE id = 7");
    $stylistSalonId = $stmt->fetchColumn();
    echo "- Stylist 7 travaille dans salon: " . ($stylistSalonId == 1 ? "✅ Salon 1" : "❌ Salon $stylistSalonId") . "\n";

    // Le service appartient-il au salon ?
    $stmt = $pdo->query("SELECT salon_id FROM service WHERE id = 1");
    $serviceSalonId = $stmt->fetchColumn();
    echo "- Service 1 appartient au salon: " . ($serviceSalonId == 1 ? "✅ Salon 1" : "❌ Salon $serviceSalonId") . "\n";

    // Le stylist a-t-il la compétence pour ce service ?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stylist_service WHERE stylist_id = ? AND service_id = ?");
    $stmt->execute([7, 1]);
    $hasSkill = $stmt->fetchColumn() > 0;
    echo "- Stylist 7 a compétence pour service 1: " . ($hasSkill ? "✅ Oui" : "❌ Non") . "\n";

    // Calculer le créneau demandé
    $serviceDuration = $pdo->query("SELECT duration_minutes FROM service WHERE id = 1")->fetchColumn();
    $startDateTime = new DateTime('2025-01-15 10:00:00');
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('PT' . $serviceDuration . 'M'));

    echo "\nCréneau demandé:\n";
    echo "- Service durée: {$serviceDuration} minutes\n";
    echo "- Début: {$startDateTime->format('Y-m-d H:i:s')}\n";
    echo "- Fin: {$endDateTime->format('Y-m-d H:i:s')}\n";

    echo "\n🎯 Le problème pourrait être:\n";
    echo "1. Les IDs dans Postman ne correspondent pas à la réalité\n";
    echo "2. Une relation est brisée (stylist pas dans salon, service pas dans salon, compétence manquante)\n";
    echo "3. Les horaires ne permettent pas ce créneau\n";
    echo "4. Il y a une exception ou une réservation existante\n";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
?>
