<?php
// Script de test pour vérifier la validation des réservations

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TEST DE VALIDATION DE RÉSERVATION ===\n\n";

    // Paramètres de test
    $salonId = 1;
    $stylistId = 7;
    $serviceId = 1;
    $startAt = '2025-01-15 10:00:00'; // Mercredi 15 janvier 2025, 10h00

    echo "Paramètres de test :\n";
    echo "- Salon ID : $salonId\n";
    echo "- Stylist ID : $stylistId\n";
    echo "- Service ID : $serviceId\n";
    echo "- Date/heure : $startAt\n\n";

    // 1. Vérifier que le stylist travaille dans le salon
    $stmt = $pdo->prepare("SELECT salon_id FROM stylist WHERE id = ?");
    $stmt->execute([$stylistId]);
    $stylistSalon = $stmt->fetchColumn();

    if ($stylistSalon != $salonId) {
        echo "❌ ERREUR : Le coiffeur ne travaille pas dans ce salon\n";
        exit(1);
    }
    echo "✅ Coiffeur travaille dans le salon\n";

    // 2. Vérifier que le stylist a la compétence pour le service
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM stylist_service WHERE stylist_id = ? AND service_id = ?");
    $stmt->execute([$stylistId, $serviceId]);
    $hasSkill = $stmt->fetchColumn() > 0;

    if (!$hasSkill) {
        echo "❌ ERREUR : Le coiffeur n'a pas la compétence pour ce service\n";
        exit(1);
    }
    echo "✅ Coiffeur a la compétence pour ce service\n";

    // 3. Récupérer les informations du service
    $stmt = $pdo->prepare("SELECT name, duration_minutes FROM service WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "Service : {$service['name']} (durée: {$service['duration_minutes']} min)\n";

    // Calculer endAt
    $startDateTime = new DateTime($startAt);
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval('PT' . $service['duration_minutes'] . 'M'));

    echo "Créneau demandé : {$startDateTime->format('H:i')} - {$endDateTime->format('H:i')}\n";

    // 4. Vérifier les horaires d'ouverture
    $dayOfWeek = strtolower($startDateTime->format('l')); // 'wednesday'
    echo "Jour de la semaine : $dayOfWeek\n";

    $stmt = $pdo->prepare("SELECT open_hours FROM salon WHERE id = ?");
    $stmt->execute([$salonId]);
    $salonHoursJson = $stmt->fetchColumn();
    $salonHours = json_decode($salonHoursJson, true);

    echo "Horaires du salon pour $dayOfWeek : ";
    if (isset($salonHours[$dayOfWeek])) {
        $dayHours = $salonHours[$dayOfWeek];
        echo json_encode($dayHours) . "\n";

        // Vérifier que le créneau demandé est dans les horaires
        $isInHours = false;
        foreach ($dayHours as $timeRange) {
            if (!is_string($timeRange) || !str_contains($timeRange, '-')) {
                continue;
            }

            [$startTime, $endTime] = explode('-', $timeRange, 2);

            $rangeStart = DateTime::createFromFormat('Y-m-d H:i', $startDateTime->format('Y-m-d') . ' ' . trim($startTime));
            $rangeEnd = DateTime::createFromFormat('Y-m-d H:i', $startDateTime->format('Y-m-d') . ' ' . trim($endTime));

            if ($startDateTime >= $rangeStart && $endDateTime <= $rangeEnd) {
                $isInHours = true;
                break;
            }
        }

        if (!$isInHours) {
            echo "❌ ERREUR : Le créneau demandé n'est pas dans les horaires d'ouverture\n";
            exit(1);
        }
        echo "✅ Créneau dans les horaires d'ouverture\n";

    } else {
        echo "❌ ERREUR : Aucun horaire défini pour ce jour\n";
        exit(1);
    }

    // 5. Vérifier les exceptions d'ouverture
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as exceptions_count
        FROM availability_exception ae
        WHERE ae.date = ?
        AND ae.closed = true
        AND (ae.salon_id = ? OR ae.stylist_id = ?)
    ");
    $stmt->execute([
        $startDateTime->format('Y-m-d'),
        $salonId,
        $stylistId
    ]);
    $exceptionsCount = $stmt->fetchColumn();

    if ($exceptionsCount > 0) {
        echo "❌ ERREUR : Il y a une exception d'ouverture (fermeture)\n";
        exit(1);
    }
    echo "✅ Pas d'exception d'ouverture\n";

    // 6. Vérifier les conflits de réservation
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as conflicting_bookings
        FROM booking
        WHERE stylist_id = ?
        AND status IN ('PENDING', 'CONFIRMED')
        AND (
            (start_at < ? AND end_at > ?) OR
            (start_at < ? AND end_at > ?) OR
            (start_at >= ? AND end_at <= ?)
        )
    ");
    $stmt->execute([
        $stylistId,
        $endDateTime->format('Y-m-d H:i:s'),  // slot_end
        $startDateTime->format('Y-m-d H:i:s'), // slot_start
        $startDateTime->format('Y-m-d H:i:s'), // slot_start
        $endDateTime->format('Y-m-d H:i:s'),  // slot_end
        $startDateTime->format('Y-m-d H:i:s'), // slot_start
        $endDateTime->format('Y-m-d H:i:s')   // slot_end
    ]);

    $conflictingBookings = $stmt->fetchColumn();

    if ($conflictingBookings > 0) {
        echo "❌ ERREUR : Il y a un conflit avec une réservation existante\n";
        exit(1);
    }
    echo "✅ Pas de conflit avec les réservations existantes\n";

    echo "\n🎉 TOUTES LES VALIDATIONS SONT RÉUSSIES !\n";
    echo "La réservation devrait pouvoir être créée.\n";

} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
}
?>
