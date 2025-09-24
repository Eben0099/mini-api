<?php
// Script pour vérifier les horaires des salons

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connexion à la base de données réussie.\n";

    // Récupérer tous les salons
    $stmt = $pdo->query("SELECT id, name, open_hours FROM salon");
    $salons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Nombre de salons trouvés : " . count($salons) . "\n\n";

    foreach ($salons as $salon) {
        echo "Salon: {$salon['name']} (ID: {$salon['id']})\n";

        $openHours = json_decode($salon['open_hours'], true);

        if (empty($openHours)) {
            echo "  ❌ Horaires vides ! Définition d'horaires par défaut...\n";

            // Définir des horaires par défaut
            $defaultHours = [
                "monday" => ["09:00-12:00", "14:00-18:00"],
                "tuesday" => ["09:00-12:00", "14:00-18:00"],
                "wednesday" => ["09:00-12:00", "14:00-18:00"],
                "thursday" => ["09:00-12:00", "14:00-18:00"],
                "friday" => ["09:00-12:00", "14:00-18:00"],
                "saturday" => ["08:00-17:00"],
                "sunday" => []
            ];

            $stmt = $pdo->prepare("UPDATE salon SET open_hours = ? WHERE id = ?");
            $stmt->execute([json_encode($defaultHours), $salon['id']]);

            echo "  ✅ Horaires par défaut définis\n";
        } else {
            echo "  ✅ Horaires définis : " . json_encode($openHours, JSON_PRETTY_PRINT) . "\n";

            // Vérifier si les horaires sont au bon format
            $validFormat = true;
            foreach ($openHours as $day => $hours) {
                if (!is_array($hours)) {
                    echo "  ❌ Jour '$day' : format invalide (devrait être un array)\n";
                    $validFormat = false;
                } else {
                    foreach ($hours as $timeRange) {
                        if (!is_string($timeRange) || !preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $timeRange)) {
                            echo "  ❌ Plage horaire invalide : '$timeRange' (format attendu : HH:MM-HH:MM)\n";
                            $validFormat = false;
                        }
                    }
                }
            }

            if ($validFormat) {
                echo "  ✅ Format des horaires valide\n";
            }
        }

        echo "\n";
    }

    // Vérifier les horaires des stylists
    echo "=== VÉRIFICATION DES HORAIRES DES STYLISTS ===\n\n";
    $stmt = $pdo->query("SELECT s.id, s.user_id, u.first_name, u.last_name, s.open_hours FROM stylist s JOIN user u ON s.user_id = u.id");
    $stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Nombre de stylists trouvés : " . count($stylists) . "\n\n";

    foreach ($stylists as $stylist) {
        echo "Stylist: {$stylist['first_name']} {$stylist['last_name']} (ID: {$stylist['id']})\n";

        if ($stylist['open_hours'] === null) {
            echo "  ℹ️  Pas d'horaires spécifiques (utilise horaires salon)\n";
        } else {
            $openHours = json_decode($stylist['open_hours'], true);
            echo "  ✅ Horaires spécifiques définis\n";
        }

        echo "\n";
    }

} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
}
?>
