<?php
// Debug final pour comprendre exactement le problème

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== DIAGNOSTIC FINAL ===\n\n";

    echo "🔍 L'utilisateur teste les disponibilités avec:\n";
    echo "GET /api/v1/salons/1/availability?serviceId=1&date=2025-01-15&duration=60\n\n";

    echo "📝 Puis essaie de créer une réservation avec:\n";
    echo "POST /api/v1/bookings\n";
    echo "{\n";
    echo "  \"salonId\": 1,\n";
    echo "  \"stylistId\": 7,\n";
    echo "  \"serviceId\": 1,\n";
    echo "  \"startAt\": \"2025-01-15 10:00:00\"\n";
    echo "}\n\n";

    // Informations sur le service
    $stmt = $pdo->query("SELECT name, duration_minutes FROM service WHERE id = 1");
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "📋 Informations sur le service:\n";
    echo "- Nom: {$service['name']}\n";
    echo "- Durée: {$service['duration_minutes']} minutes\n\n";

    echo "⚠️  PROBLÈME IDENTIFIÉ:\n";
    echo "L'utilisateur teste les disponibilités avec duration=60 minutes\n";
    echo "Mais le service réel dure {$service['duration_minutes']} minutes\n";
    echo "Donc la réservation utilise {$service['duration_minutes']} minutes, pas 60!\n\n";

    // Calcul des créneaux
    $startDateTime = new DateTime('2025-01-15 10:00:00');

    // Créneau demandé pour les disponibilités (60 min)
    $end60min = clone $startDateTime;
    $end60min->add(new DateInterval('PT60M'));

    // Créneau réel pour la réservation (durée du service)
    $endService = clone $startDateTime;
    $endService->add(new DateInterval('PT' . $service['duration_minutes'] . 'M'));

    echo "⏰ Comparaison des créneaux:\n";
    echo "Créneau testé en disponibilités: {$startDateTime->format('H:i')} - {$end60min->format('H:i')} (60 min)\n";
    echo "Créneau réel de réservation: {$startDateTime->format('H:i')} - {$endService->format('H:i')} ({$service['duration_minutes']} min)\n\n";

    // Vérifier les horaires du salon
    $stmt = $pdo->prepare("SELECT open_hours FROM salon WHERE id = ?");
    $stmt->execute([1]);
    $salonHoursJson = $stmt->fetchColumn();
    $salonHours = json_decode($salonHoursJson, true);

    $dayOfWeek = strtolower($startDateTime->format('l'));
    echo "📅 Jour: $dayOfWeek\n";
    echo "🏢 Horaires salon: " . json_encode($salonHours[$dayOfWeek]) . "\n\n";

    // Tester si le créneau de 60 min est disponible
    echo "🧪 Test créneau 60 minutes (comme dans disponibilités):\n";
    $is60minAvailable = isTimeInHours($startDateTime, $end60min, $salonHours[$dayOfWeek]);
    echo "- Dans horaires: " . ($is60minAvailable ? "✅ OUI" : "❌ NON") . "\n";

    $conflicts60 = checkConflicts($pdo, 7, $startDateTime, $end60min);
    echo "- Conflits: " . ($conflicts60 ? "❌ OUI" : "✅ NON") . "\n";

    // Tester si le créneau de 75 min est disponible
    echo "\n🧪 Test créneau 75 minutes (comme dans réservation):\n";
    $is75minAvailable = isTimeInHours($startDateTime, $endService, $salonHours[$dayOfWeek]);
    echo "- Dans horaires: " . ($is75minAvailable ? "✅ OUI" : "❌ NON") . "\n";

    $conflicts75 = checkConflicts($pdo, 7, $startDateTime, $endService);
    echo "- Conflits: " . ($conflicts75 ? "❌ OUI" : "✅ NON") . "\n";

    echo "\n🎯 CONCLUSION:\n";
    if ($is60minAvailable && !$conflicts60) {
        echo "✅ Les disponibilités montrent le créneau comme disponible (60 min)\n";
    } else {
        echo "❌ Les disponibilités ne montrent pas le créneau (60 min)\n";
    }

    if ($is75minAvailable && !$conflicts75) {
        echo "✅ La réservation devrait réussir (75 min)\n";
    } else {
        echo "❌ La réservation va échouer parce que le créneau de 75 min n'est pas disponible\n";
        if (!$is75minAvailable) {
            echo "   - Raison: Le créneau sort des horaires d'ouverture\n";
        }
        if ($conflicts75) {
            echo "   - Raison: Il y a un conflit avec une réservation existante\n";
        }
    }

    echo "\n💡 SOLUTION:\n";
    echo "1. Tester les disponibilités avec la vraie durée du service: duration={$service['duration_minutes']}\n";
    echo "2. Ou ajuster l'heure de début pour que le créneau tienne dans les horaires\n";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

function isTimeInHours(DateTime $startAt, DateTime $endAt, array $hours): bool
{
    foreach ($hours as $timeRange) {
        if (!is_string($timeRange) || !str_contains($timeRange, '-')) {
            continue;
        }

        [$startTime, $endTime] = explode('-', $timeRange, 2);

        $rangeStart = DateTime::createFromFormat('Y-m-d H:i', $startAt->format('Y-m-d') . ' ' . trim($startTime));
        $rangeEnd = DateTime::createFromFormat('Y-m-d H:i', $startAt->format('Y-m-d') . ' ' . trim($endTime));

        if ($startAt >= $rangeStart && $endAt <= $rangeEnd) {
            return true;
        }
    }

    return false;
}

function checkConflicts(PDO $pdo, int $stylistId, DateTime $startAt, DateTime $endAt): bool
{
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
        $endAt->format('Y-m-d H:i:s'),
        $startAt->format('Y-m-d H:i:s'),
        $startAt->format('Y-m-d H:i:s'),
        $endAt->format('Y-m-d H:i:s'),
        $startAt->format('Y-m-d H:i:s'),
        $endAt->format('Y-m-d H:i:s')
    ]);

    return $stmt->fetchColumn() > 0;
}
?>
