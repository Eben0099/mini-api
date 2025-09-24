<?php
// Script simple de test pour vérifier les disponibilités

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TEST DES DISPONIBILITÉS ===\n\n";

    // Récupérer le salon
    $stmt = $pdo->query("SELECT id, name, open_hours FROM salon WHERE id = 1");
    $salon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$salon) {
        echo "❌ Aucun salon trouvé\n";
        exit(1);
    }

    echo "Salon trouvé : {$salon['name']}\n";

    // Récupérer un service
    $stmt = $pdo->query("SELECT id, name, duration_minutes FROM service WHERE id = 1");
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        echo "❌ Aucun service trouvé\n";
        exit(1);
    }

    echo "Service trouvé : {$service['name']} (durée: {$service['duration_minutes']} min)\n\n";

    // Tester les disponibilités pour le 15 janvier 2025 (mercredi)
    $date = new DateTimeImmutable('2025-01-15');
    $duration = 60; // 60 minutes

    echo "Test des disponibilités pour le {$date->format('l d/m/Y')} :\n";
    echo "Service : {$service['name']}\n";
    echo "Durée : {$duration} minutes\n\n";

    // Récupérer les stylists du salon
    $stmt = $pdo->prepare("
        SELECT s.id as stylist_id, u.first_name, u.last_name, s.open_hours as stylist_hours
        FROM stylist s
        JOIN user u ON s.user_id = u.id
        WHERE s.salon_id = ?
    ");
    $stmt->execute([$salon['id']]);
    $stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Nombre de stylists trouvés : " . count($stylists) . "\n\n";

    // Vérifier les horaires du salon
    $openHours = json_decode($salon['open_hours'], true);
    $dayName = strtolower($date->format('l')); // 'wednesday'

    echo "Horaires du salon pour '$dayName' : ";
    if (isset($openHours[$dayName])) {
        $dayHours = $openHours[$dayName];
        echo json_encode($dayHours) . " ✅\n\n";

        // Générer les créneaux potentiels
        $potentialSlots = generatePotentialSlots($date, $dayHours, $duration);
        echo "Nombre de créneaux potentiels générés : " . count($potentialSlots) . "\n";

        if (!empty($potentialSlots)) {
            echo "Exemples de créneaux : ";
            $examples = array_slice($potentialSlots, 0, 5);
            foreach ($examples as $slot) {
                echo $slot->format('H:i') . ', ';
            }
            echo "...\n\n";
        }

        // Pour chaque stylist, vérifier s'il a les compétences
        foreach ($stylists as $stylist) {
            echo "Stylist : {$stylist['first_name']} {$stylist['last_name']}\n";

            // Vérifier si le stylist a la compétence pour ce service
            $skillStmt = $pdo->prepare("
                SELECT COUNT(*) as has_skill
                FROM stylist_service
                WHERE stylist_id = ? AND service_id = ?
            ");
            $skillStmt->execute([$stylist['stylist_id'], $service['id']]);
            $hasSkill = $skillStmt->fetchColumn() > 0;

            if (!$hasSkill) {
                echo "  ❌ Pas compétent pour ce service\n";
                continue;
            }

            echo "  ✅ Compétent pour ce service\n";

            // Vérifier les horaires du stylist
            if ($stylist['stylist_hours'] !== null) {
                $stylistHours = json_decode($stylist['stylist_hours'], true);
                if (isset($stylistHours[$dayName]) && !empty($stylistHours[$dayName])) {
                    echo "  ✅ Horaires spécifiques définis pour ce stylist\n";
                    // Utiliser les horaires du stylist
                    $applicableHours = $stylistHours[$dayName];
                } else {
                    echo "  ℹ️  Utilise horaires salon\n";
                    $applicableHours = $dayHours;
                }
            } else {
                echo "  ℹ️  Utilise horaires salon\n";
                $applicableHours = $dayHours;
            }

            // Générer les créneaux disponibles pour ce stylist
            $availableSlots = generatePotentialSlots($date, $applicableHours, $duration);

            // Filtrer les créneaux déjà réservés
            $availableSlotsFiltered = [];
            foreach ($availableSlots as $slotStart) {
                $slotEnd = $slotStart->add(new DateInterval('PT' . $duration . 'M'));

                // Vérifier s'il y a une réservation qui chevauche
                $bookingStmt = $pdo->prepare("
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
                $bookingStmt->execute([
                    $stylist['stylist_id'],
                    $slotEnd->format('Y-m-d H:i:s'),
                    $slotStart->format('Y-m-d H:i:s'),
                    $slotStart->format('Y-m-d H:i:s'),
                    $slotEnd->format('Y-m-d H:i:s'),
                    $slotStart->format('Y-m-d H:i:s'),
                    $slotEnd->format('Y-m-d H:i:s')
                ]);

                $conflictingBookings = $bookingStmt->fetchColumn();

                if ($conflictingBookings == 0) {
                    $availableSlotsFiltered[] = $slotStart->format('H:i');
                }
            }

            echo "  Créneaux disponibles : ";
            if (empty($availableSlotsFiltered)) {
                echo "AUCUN ❌\n";
            } else {
                echo implode(', ', array_slice($availableSlotsFiltered, 0, 10)) . " ✅\n";
            }

            echo "\n";
        }

    } else {
        echo "❌ AUCUN HORAIRE DÉFINI pour ce jour\n";
    }

} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
}

function generatePotentialSlots(DateTimeImmutable $date, array $hours, int $durationMinutes): array
{
    $slots = [];

    foreach ($hours as $timeRange) {
        if (!is_string($timeRange) || !str_contains($timeRange, '-')) {
            continue;
        }

        [$startTime, $endTime] = explode('-', $timeRange, 2);

        try {
            $startDateTime = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                $date->format('Y-m-d') . ' ' . trim($startTime)
            );

            $endDateTime = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                $date->format('Y-m-d') . ' ' . trim($endTime)
            );

            if (!$startDateTime || !$endDateTime) {
                continue;
            }

            $currentSlot = $startDateTime;
            while ($currentSlot <= $endDateTime->sub(new DateInterval('PT' . $durationMinutes . 'M'))) {
                $slots[] = $currentSlot;
                $currentSlot = $currentSlot->add(new DateInterval('PT15M'));
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return $slots;
}

echo "\n=== FIN DU TEST ===\n";
?>
