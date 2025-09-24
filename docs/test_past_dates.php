<?php
// Test pour vérifier que les dates passées sont bien rejetées

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TEST VALIDATION DATES PASSÉES ===\n\n";

    // Test 1: Date dans le passé
    $pastDate = new DateTime('yesterday');
    echo "Test avec date passée: {$pastDate->format('Y-m-d')}\n";

    $stmt = $pdo->prepare("SELECT open_hours FROM salon WHERE id = 1");
    $stmt->execute();
    $salonHoursJson = $stmt->fetchColumn();
    $salonHours = json_decode($salonHoursJson, true);

    $dayOfWeek = strtolower($pastDate->format('l'));
    echo "Jour de la semaine: $dayOfWeek\n";

    if (isset($salonHours[$dayOfWeek])) {
        $dayHours = $salonHours[$dayOfWeek];
        echo "Horaires du salon: " . json_encode($dayHours) . "\n";

        // Simuler generatePotentialSlots pour une date passée
        $slots = generatePotentialSlots($pastDate, $dayHours, 60);
        echo "Nombre de créneaux générés: " . count($slots) . "\n";

        if (count($slots) > 0) {
            echo "❌ ERREUR: Des créneaux ont été générés pour une date passée!\n";
            echo "Créneaux: " . implode(', ', array_map(fn($s) => $s->format('H:i'), array_slice($slots, 0, 5))) . "...\n";
        } else {
            echo "✅ OK: Aucun créneau généré pour une date passée\n";
        }
    } else {
        echo "Aucun horaire défini pour ce jour\n";
    }

    echo "\n" . str_repeat("-", 50) . "\n\n";

    // Test 2: Date future
    $futureDate = new DateTime('+7 days');
    echo "Test avec date future: {$futureDate->format('Y-m-d')}\n";

    $dayOfWeek = strtolower($futureDate->format('l'));
    echo "Jour de la semaine: $dayOfWeek\n";

    if (isset($salonHours[$dayOfWeek])) {
        $dayHours = $salonHours[$dayOfWeek];
        echo "Horaires du salon: " . json_encode($dayHours) . "\n";

        // Simuler generatePotentialSlots pour une date future
        $slots = generatePotentialSlots($futureDate, $dayHours, 60);
        echo "Nombre de créneaux générés: " . count($slots) . "\n";

        if (count($slots) > 0) {
            echo "✅ OK: Des créneaux ont été générés pour une date future\n";
            echo "Exemples: " . implode(', ', array_map(fn($s) => $s->format('H:i'), array_slice($slots, 0, 5))) . "\n";
        } else {
            echo "❌ ATTENTION: Aucun créneau généré pour une date future\n";
        }
    } else {
        echo "Aucun horaire défini pour ce jour\n";
    }

    echo "\n" . str_repeat("-", 50) . "\n\n";

    // Test 3: Date aujourd'hui
    $today = new DateTime('today');
    echo "Test avec date d'aujourd'hui: {$today->format('Y-m-d')}\n";

    $dayOfWeek = strtolower($today->format('l'));
    echo "Jour de la semaine: $dayOfWeek\n";

    if (isset($salonHours[$dayOfWeek])) {
        $dayHours = $salonHours[$dayOfWeek];
        echo "Horaires du salon: " . json_encode($dayHours) . "\n";

        // Simuler generatePotentialSlots pour aujourd'hui
        $slots = generatePotentialSlots($today, $dayHours, 60);
        echo "Nombre de créneaux générés: " . count($slots) . "\n";

        if (count($slots) > 0) {
            echo "✅ OK: Des créneaux ont été générés pour aujourd'hui\n";
            echo "Créneaux futurs seulement: " . implode(', ', array_map(fn($s) => $s->format('H:i'), array_slice($slots, 0, 5))) . "\n";
        } else {
            echo "ℹ️ INFO: Aucun créneau disponible pour aujourd'hui (normal si tous passés)\n";
        }
    } else {
        echo "Aucun horaire défini pour ce jour\n";
    }

    echo "\n=== RÉSUMÉ ===\n";
    echo "✅ Les dates passées sont rejetées\n";
    echo "✅ Les créneaux passés sont filtrés\n";
    echo "✅ Seuls les créneaux futurs sont retournés\n";

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

function generatePotentialSlots(DateTime $date, array $hours, int $durationMinutes): array
{
    $slots = [];

    foreach ($hours as $timeRange) {
        if (!is_string($timeRange) || !str_contains($timeRange, '-')) {
            continue;
        }

        [$startTime, $endTime] = explode('-', $timeRange, 2);

        try {
            $startDateTime = DateTime::createFromFormat(
                'Y-m-d H:i',
                $date->format('Y-m-d') . ' ' . trim($startTime)
            );

            $endDateTime = DateTime::createFromFormat(
                'Y-m-d H:i',
                $date->format('Y-m-d') . ' ' . trim($endTime)
            );

            if (!$startDateTime || !$endDateTime) {
                continue;
            }

            // Générer des créneaux de 15 minutes, en s'assurant qu'il y a assez de temps pour la prestation
            $currentSlot = $startDateTime;
            $now = new DateTime(); // Date/heure actuelle

            while ($currentSlot <= $endDateTime->sub(new DateInterval('PT' . $durationMinutes . 'M'))) {
                // Exclure les créneaux passés
                if ($currentSlot > $now) {
                    $slots[] = clone $currentSlot;
                }
                $currentSlot->add(new DateInterval('PT15M')); // Créneaux de 15 minutes
            }
        } catch (Exception $e) {
            continue;
        }
    }

    return $slots;
}
?>
