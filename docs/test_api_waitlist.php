<?php
// Test API réel avec logs de debug pour la liste d'attente

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TEST API RÉEL AVEC LOGS DEBUG ===\n\n";

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
    $futureDate = new DateTime('+3 days 15:00:00');
    $futureEndDate = clone $futureDate;
    $futureEndDate->modify('+60 minutes');

    $stmt = $pdo->prepare("
        INSERT INTO booking (salon_id, stylist_id, service_id, client_id, start_at, end_at, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'confirmed', NOW(), NOW())
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

    echo "\n2. État avant test :\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM booking WHERE status = 'confirmed'");
    $stmt->execute();
    echo "   - Réservations confirmées: " . $stmt->fetch()['total'] . "\n";

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM waitlist_entry");
    $stmt->execute();
    echo "   - Entrées liste d'attente: " . $stmt->fetch()['total'] . "\n";

    // Étape 2: Faire un appel API réel pour déclencher les logs
    echo "\n3. Test API réel avec logs de debug :\n";
    echo "   🔍 Les logs suivants devraient apparaître dans les logs du serveur web :\n";
    echo "   - PROCESS WAITLIST REPLACEMENT DEBUG\n";
    echo "   - NOTIFY WAITLIST TO BOOKING DEBUG\n";
    echo "   - Préparation email client/styliste\n";
    echo "   - Envoi email...\n\n";

    // Utiliser curl pour faire un DELETE sur la réservation
    $apiUrl = "http://localhost:8000/api/v1/bookings/$bookingId";
    $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3MzI0NDY4NjEsImV4cCI6MTczMjQ1MDQ2MSwicm9sZXMiOlsiUk9MRV9TVFMiLCJST0xFX1VTRVIiXSwidXNlcm5hbWUiOiJzdHlsaXN0QGV4YW1wbGUuY29tIn0"; // Token d'exemple

    echo "   📡 Appel API: DELETE $apiUrl\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_error($ch)) {
        echo "   ❌ Erreur curl: " . curl_error($ch) . "\n";
    } else {
        echo "   ✅ Réponse HTTP: $httpCode\n";
        if ($httpCode === 200) {
            echo "   ✅ Annulation réussie\n";
        } else {
            echo "   ⚠️ Code HTTP inattendu: $httpCode\n";
            echo "   📄 Réponse: " . substr($response, 0, 200) . "...\n";
        }
    }

    curl_close($ch);

    // Attendre un peu pour que les logs soient écrits
    sleep(2);

    echo "\n4. Vérification de l'état après annulation :\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM booking WHERE status = 'confirmed'");
    $stmt->execute();
    echo "   - Réservations confirmées: " . $stmt->fetch()['total'] . "\n";

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM booking WHERE status = 'cancelled'");
    $stmt->execute();
    echo "   - Réservations annulées: " . $stmt->fetch()['total'] . "\n";

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM waitlist_entry");
    $stmt->execute();
    echo "   - Entrées liste d'attente: " . $stmt->fetch()['total'] . "\n";

    // Étape 3: Nettoyer
    echo "\n5. Nettoyage :\n";
    $stmt = $pdo->prepare("DELETE FROM booking WHERE id = ?");
    $stmt->execute([$bookingId]);
    echo "   ✅ Réservation de test supprimée\n";

    echo "\n=== INSTRUCTIONS POUR VÉRIFIER LES LOGS ===\n";
    echo "📋 Vérifiez les logs suivants :\n";
    echo "1. 📄 Logs du serveur web (Apache/Nginx) : /var/log/apache2/error.log\n";
    echo "2. 📄 Logs PHP : /var/log/php/error.log\n";
    echo "3. 📧 Logs MailHog si configuré : http://localhost:8025\n";
    echo "4. 🔍 Cherchez ces messages :\n";
    echo "   - 'PROCESS WAITLIST REPLACEMENT DEBUG'\n";
    echo "   - 'NOTIFY WAITLIST TO BOOKING DEBUG'\n";
    echo "   - 'Préparation email client...'\n";
    echo "   - 'Envoi email client...'\n";
    echo "   - '✅ Email client envoyé avec succès'\n";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
?>
