<?php
// Script pour créer des données de test pour la liste d'attente

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== CONFIGURATION TEST LISTE D'ATTENTE ===\n\n";

    // Étape 1: Créer des données de test
    echo "1. Création des données de test :\n";

    // Trouver des IDs existants
    $stmt = $pdo->prepare("SELECT id FROM salon LIMIT 1");
    $stmt->execute();
    $salonId = $stmt->fetch()['id'];

    $stmt = $pdo->prepare("SELECT s.id FROM stylist s JOIN user u ON s.user_id = u.id WHERE s.salon_id = ? LIMIT 1");
    $stmt->execute([$salonId]);
    $stylistId = $stmt->fetch()['id'];

    $stmt = $pdo->prepare("SELECT id FROM service WHERE salon_id = ? LIMIT 1");
    $stmt->execute([$salonId]);
    $serviceId = $stmt->fetch()['id'];

    $stmt = $pdo->prepare("SELECT id FROM user WHERE roles LIKE '%ROLE_CLIENT%' LIMIT 2");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($clients) < 2) {
        echo "❌ Pas assez de clients pour le test\n";
        exit(1);
    }

    $clientId1 = $clients[0]['id'];
    $clientId2 = $clients[1]['id'];

    echo "   - Salon ID: $salonId\n";
    echo "   - Styliste ID: $stylistId\n";
    echo "   - Service ID: $serviceId\n";
    echo "   - Client 1 ID: $clientId1 (pour réservation)\n";
    echo "   - Client 2 ID: $clientId2 (pour liste d'attente)\n";

    // Créer une réservation dans le futur
    $futureDate = new DateTime('+1 day 14:00:00');
    $futureEndDate = clone $futureDate;
    $futureEndDate->modify('+60 minutes');

    $stmt = $pdo->prepare("
        INSERT INTO booking (salon_id, stylist_id, service_id, client_id, start_at, end_at, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW(), NOW())
        ON DUPLICATE KEY UPDATE status = 'confirmed'
    ");
    $stmt->execute([
        $salonId,
        $stylistId,
        $serviceId,
        $clientId1,
        $futureDate->format('Y-m-d H:i:s'),
        $futureEndDate->format('Y-m-d H:i:s')
    ]);
    $bookingId = $pdo->lastInsertId();
    echo "   ✅ Réservation créée (ID: $bookingId) pour le {$futureDate->format('d/m/Y H:i')}\n";

    // Ajouter le client 2 en liste d'attente
    $desiredStart = clone $futureDate;
    $desiredStart->modify('-2 hours');
    $desiredEnd = clone $futureDate;
    $desiredEnd->modify('+4 hours');

    $stmt = $pdo->prepare("
        INSERT INTO waitlist_entry (salon_id, service_id, client_id, desired_start_range_start, desired_start_range_end, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE created_at = NOW()
    ");
    $stmt->execute([
        $salonId,
        $serviceId,
        $clientId2,
        $desiredStart->format('Y-m-d H:i:s'),
        $desiredEnd->format('Y-m-d H:i:s')
    ]);
    $waitlistId = $pdo->lastInsertId();
    echo "   ✅ Entrée liste d'attente créée (ID: $waitlistId)\n";

    echo "\n2. État actuel :\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM booking WHERE status = 'confirmed'");
    $stmt->execute();
    echo "   - Réservations confirmées: " . $stmt->fetch()['total'] . "\n";

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM waitlist_entry");
    $stmt->execute();
    echo "   - Entrées liste d'attente: " . $stmt->fetch()['total'] . "\n";

    echo "\n=== INSTRUCTIONS POUR TEST ===\n";
    echo "1. 📧 Démarrer MailHog : mailhog\n";
    echo "2. 🌐 Ouvrir http://localhost:8025 pour voir les emails\n";
    echo "3. 🔄 Faire un DELETE sur la réservation ID: $bookingId\n";
    echo "4. 📋 Vérifier les logs :\n";
    echo "   - var/log/waitlist_process.log (processus)\n";
    echo "   - var/log/waitlist_emails.log (emails)\n";
    echo "5. 📧 Vérifier les emails dans MailHog\n";

    echo "\n=== COMMANDES API ===\n";
    echo "# Annuler la réservation (remplacera par liste d'attente) :\n";
    echo "curl -X DELETE http://localhost:8000/api/v1/bookings/$bookingId \\\n";
    echo "  -H \"Authorization: Bearer [TOKEN_JWT]\" \\\n";
    echo "  -H \"Content-Type: application/json\"\n";

    echo "\n# Ou utiliser votre client API préféré\n";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
?>
