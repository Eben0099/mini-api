<?php
// Script pour marquer les migrations comme exécutées dans doctrine_migration_versions

// Configuration de la base de données (utiliser les mêmes valeurs que dans docker-compose.yml)
$host = 'localhost';
$port = '4310'; // Port MySQL local
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

$migrations = [
    'DoctrineMigrations\\Version20250925090000', // Migration pour open_hours
    'DoctrineMigrations\\Version20250925100000', // Migration pour les indexes
];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connexion à la base de données réussie.\n";

    // Vérifier si la table doctrine_migration_versions existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'doctrine_migration_versions'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        echo "La table doctrine_migration_versions n'existe pas. Création...\n";
        $pdo->exec("CREATE TABLE doctrine_migration_versions (
            version VARCHAR(191) NOT NULL,
            executed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (version)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    // Marquer chaque migration comme exécutée
    foreach ($migrations as $migration) {
        // Vérifier si la migration est déjà marquée
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM doctrine_migration_versions WHERE version = ?");
        $stmt->execute([$migration]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            echo "Migration déjà marquée comme exécutée : $migration\n";
        } else {
            // Marquer comme exécutée
            $stmt = $pdo->prepare("INSERT INTO doctrine_migration_versions (version, executed_at) VALUES (?, NOW())");
            $stmt->execute([$migration]);
            echo "Migration marquée comme exécutée : $migration\n";
        }
    }

    echo "Toutes les migrations ont été marquées comme exécutées.\n";

} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
}
?>
