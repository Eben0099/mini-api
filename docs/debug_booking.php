<?php
// Script de debug pour comprendre pourquoi canCreateBooking échoue

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

try {
    echo "=== DEBUG canCreateBooking ===\n\n";

    // Récupérer les entités directement
    $pdo = new PDO("mysql:host=localhost;port=4310;dbname=mini_api", 'symfony', 'symfony');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Paramètres de test identiques à l'API
    $salonId = 1;
    $stylistId = 7;
    $serviceId = 1;
    $startAtString = '2025-01-15 10:00:00';

    echo "Paramètres :\n";
    echo "- Salon ID: $salonId\n";
    echo "- Stylist ID: $stylistId\n";
    echo "- Service ID: $serviceId\n";
    echo "- StartAt: $startAtString\n\n";

    // Simuler la logique de canCreateBooking
    $startAt = new \DateTimeImmutable($startAtString);

    // Récupérer le service pour la durée
    $stmt = $pdo->prepare("SELECT name, duration_minutes FROM service WHERE id = ?");
    $stmt->execute([$serviceId]);
    $serviceData = $stmt->fetch(PDO::FETCH_ASSOC);

    $endAt = $startAt->add(new \DateInterval('PT' . $serviceData['duration_minutes'] . 'M'));

    echo "Service: {$serviceData['name']} ({$serviceData['duration_minutes']} min)\n";
    echo "Créneau: {$startAt->format('H:i')} - {$endAt->format('H:i')}\n\n";

    // Vérifier les horaires d'ouverture (même logique que canCreateBooking)
    $dayOfWeek = strtolower($startAt->format('l'));
    echo "Jour: $dayOfWeek\n";

    // Récupérer horaires salon
    $stmt = $pdo->prepare("SELECT open_hours FROM salon WHERE id = ?");
    $stmt->execute([$salonId]);
    $salonHoursJson = $stmt->fetchColumn();
    $salonHours = json_decode($salonHoursJson, true);

    echo "Horaires salon pour $dayOfWeek: " . json_encode($salonHours[$dayOfWeek] ?? []) . "\n";

    // Récupérer horaires stylist
    $stmt = $pdo->prepare("SELECT open_hours FROM stylist WHERE id = ?");
    $stmt->execute([$stylistId]);
    $stylistHoursJson = $stmt->fetchColumn();
    $stylistHours = $stylistHoursJson ? json_decode($stylistHoursJson, true) : null;

    echo "Horaires stylist: " . ($stylistHours ? json_encode($stylistHours) : 'null') . "\n";

    // Déterminer les horaires applicables
    if ($stylistHours && isset($stylistHours[$dayOfWeek]) && !empty($stylistHours[$dayOfWeek])) {
        $applicableHours = $stylistHours[$dayOfWeek];
        echo "Utilise horaires stylist: " . json_encode($applicableHours) . "\n";
    } else {
        $applicableHours = $salonHours[$dayOfWeek] ?? [];
        echo "Utilise horaires salon: " . json_encode($applicableHours) . "\n";
    }

    // Tester isTimeInOpeningHours
    echo "\nTest isTimeInOpeningHours:\n";
    $isInHours = false;

    foreach ($applicableHours as $timeRange) {
        echo "Test plage: '$timeRange'\n";

        if (!is_string($timeRange) || !str_contains($timeRange, '-')) {
            echo "  ❌ Format invalide\n";
            continue;
        }

        [$startTime, $endTime] = explode('-', $timeRange, 2);

        try {
            $rangeStart = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                $startAt->format('Y-m-d') . ' ' . trim($startTime)
            );

            $rangeEnd = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                $startAt->format('Y-m-d') . ' ' . trim($endTime)
            );

            echo "  Plage horaire: {$rangeStart->format('H:i')} - {$rangeEnd->format('H:i')}\n";
            echo "  Créneau demandé: {$startAt->format('H:i')} - {$endAt->format('H:i')}\n";

            if ($rangeStart && $rangeEnd && $startAt >= $rangeStart && $endAt <= $rangeEnd) {
                $isInHours = true;
                echo "  ✅ Créneau valide pour cette plage\n";
                break;
            } else {
                echo "  ❌ Créneau invalide pour cette plage\n";
            }
        } catch (\Exception $e) {
            echo "  ❌ Erreur parsing: " . $e->getMessage() . "\n";
        }
    }

    if ($isInHours) {
        echo "\n✅ Le créneau est dans les horaires d'ouverture\n";
    } else {
        echo "\n❌ Le créneau n'est PAS dans les horaires d'ouverture\n";
    }

    // Vérifier les exceptions
    echo "\nTest exceptions:\n";
    $stmt = $pdo->prepare("
        SELECT ae.id, ae.closed, ae.reason
        FROM availability_exception ae
        WHERE ae.date = ?
        AND (ae.salon_id = ? OR ae.stylist_id = ?)
    ");
    $stmt->execute([
        $startAt->format('Y-m-d'),
        $salonId,
        $stylistId
    ]);

    $exceptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Exceptions trouvées: " . count($exceptions) . "\n";

    $hasException = false;
    foreach ($exceptions as $exception) {
        if ($exception['closed']) {
            echo "❌ Exception de fermeture trouvée: {$exception['reason']}\n";
            $hasException = true;
        }
    }

    if (!$hasException) {
        echo "✅ Pas d'exception de fermeture\n";
    }

    // Vérifier les conflits de réservation
    echo "\nTest conflits de réservation:\n";
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

    $conflictingBookings = $stmt->fetchColumn();
    echo "Réservations conflictuelles: $conflictingBookings\n";

    if ($conflictingBookings == 0) {
        echo "✅ Pas de conflit de réservation\n";
    } else {
        echo "❌ Conflit avec une réservation existante\n";
    }

    // Conclusion
    echo "\n=== CONCLUSION ===\n";
    if ($isInHours && !$hasException && $conflictingBookings == 0) {
        echo "🎉 TOUTES LES VALIDATIONS PASSENT - La réservation devrait être possible\n";
    } else {
        echo "❌ AU MOINS UNE VALIDATION ÉCHoue\n";
        echo "- Dans horaires: " . ($isInHours ? "OUI" : "NON") . "\n";
        echo "- Exception: " . ($hasException ? "OUI" : "NON") . "\n";
        echo "- Conflit réservation: " . ($conflictingBookings > 0 ? "OUI" : "NON") . "\n";
    }

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    echo "Stack trace : " . $e->getTraceAsString() . "\n";
}
?>
