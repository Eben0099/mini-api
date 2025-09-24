<?php
// Test rapide de la méthode findExceptionsForDate

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== TEST DES EXCEPTIONS ===\n\n";

    // Créer une date de test
    $testDate = new DateTime('2025-01-01');

    echo "Test pour la date : {$testDate->format('Y-m-d')}\n";
    echo "Salon ID : 1\n";
    echo "Stylist ID : 7\n\n";

    // Tester la requête SQL équivalente à findExceptionsForDate
    $stmt = $pdo->prepare("
        SELECT ae.id, ae.date, ae.closed, ae.reason,
               ae.salon_id, ae.stylist_id
        FROM availability_exception ae
        WHERE ae.date = ?
        AND (ae.salon_id = ? OR ae.stylist_id IN (
            SELECT s.id FROM stylist s WHERE s.salon_id = ?
        ))
    ");

    $stmt->execute([
        $testDate->format('Y-m-d'),
        1, // salon_id
        1  // salon_id pour la sous-requête
    ]);

    $exceptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Nombre d'exceptions trouvées : " . count($exceptions) . "\n";

    if (count($exceptions) > 0) {
        foreach ($exceptions as $exception) {
            echo "- ID: {$exception['id']}, Date: {$exception['date']}, Closed: " . ($exception['closed'] ? 'Oui' : 'Non');
            if ($exception['reason']) {
                echo ", Raison: {$exception['reason']}";
            }
            echo "\n";
        }
    } else {
        echo "✅ Aucune exception trouvée (c'est normal si aucune n'a été créée)\n";
    }

    // Tester avec un stylist spécifique
    echo "\n=== Test avec stylist spécifique ===\n";

    $stmt2 = $pdo->prepare("
        SELECT ae.id, ae.date, ae.closed, ae.reason,
               ae.salon_id, ae.stylist_id
        FROM availability_exception ae
        WHERE ae.date = ?
        AND ae.stylist_id = ?
    ");

    $stmt2->execute([
        $testDate->format('Y-m-d'),
        7 // stylist_id
    ]);

    $stylistExceptions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo "Exceptions pour le stylist 7 : " . count($stylistExceptions) . "\n";

    echo "\n✅ Test terminé avec succès !";

} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
}
?>
