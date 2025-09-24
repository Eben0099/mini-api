<?php
// Script pour vérifier les compétences des stylists

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

    // Vérifier les compétences des stylists
    $stmt = $pdo->query("
        SELECT s.id as stylist_id, u.first_name, u.last_name,
               GROUP_CONCAT(svc.name SEPARATOR ', ') as skills
        FROM stylist s
        JOIN user u ON s.user_id = u.id
        LEFT JOIN stylist_service ss ON s.id = ss.stylist_id
        LEFT JOIN service svc ON ss.service_id = svc.id
        GROUP BY s.id, u.first_name, u.last_name
    ");
    $stylists = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== COMPÉTENCES DES STYLISTS ===\n\n";

    foreach ($stylists as $stylist) {
        echo "Stylist: {$stylist['first_name']} {$stylist['last_name']} (ID: {$stylist['stylist_id']})\n";

        if (empty($stylist['skills'])) {
            echo "  ❌ Aucune compétence assignée !\n";

            // Assigner automatiquement toutes les compétences disponibles
            $serviceStmt = $pdo->query("SELECT id, name FROM service WHERE salon_id = (SELECT salon_id FROM stylist WHERE id = {$stylist['stylist_id']})");
            $services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($services)) {
                echo "  🔧 Assignation automatique de toutes les compétences...\n";
                foreach ($services as $service) {
                    $insertStmt = $pdo->prepare("INSERT IGNORE INTO stylist_service (stylist_id, service_id) VALUES (?, ?)");
                    $insertStmt->execute([$stylist['stylist_id'], $service['id']]);
                    echo "    ✅ {$service['name']}\n";
                }
            }
        } else {
            echo "  ✅ Compétences : {$stylist['skills']}\n";
        }

        echo "\n";
    }

    // Vérifier les services disponibles
    echo "=== SERVICES DISPONIBLES ===\n";
    $stmt = $pdo->query("SELECT id, name, salon_id FROM service");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Nombre de services : " . count($services) . "\n";

    foreach ($services as $service) {
        echo "- {$service['name']} (ID: {$service['id']})\n";
    }

} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
}
?>
