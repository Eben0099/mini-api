<?php
// Script pour vérifier et corriger les rôles des utilisateurs

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

    // Récupérer tous les utilisateurs
    $stmt = $pdo->query("SELECT id, email, roles FROM user");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Nombre d'utilisateurs trouvés : " . count($users) . "\n";

    foreach ($users as $user) {
        $roles = json_decode($user['roles'], true);
        $originalRoles = $roles;
        $modified = false;

        echo "Utilisateur {$user['email']} (ID: {$user['id']}) : ";

        // Si c'est un owner (vérifier par email ou logique métier)
        if (str_contains(strtolower($user['email']), 'owner') ||
            str_contains(strtolower($user['email']), 'propriétaire') ||
            str_contains(strtolower($user['email']), 'salon')) {

            if (!in_array('ROLE_OWNER', $roles)) {
                $roles[] = 'ROLE_OWNER';
                $modified = true;
                echo "Ajout ROLE_OWNER, ";
            }
        } else {
            // Pour les autres utilisateurs, ajouter ROLE_CLIENT
            if (!in_array('ROLE_CLIENT', $roles)) {
                $roles[] = 'ROLE_CLIENT';
                $modified = true;
                echo "Ajout ROLE_CLIENT, ";
            }
        }

        // Supprimer les doublons
        $roles = array_unique($roles);

        if ($modified) {
            // Mettre à jour la base de données
            $stmt = $pdo->prepare("UPDATE user SET roles = ? WHERE id = ?");
            $stmt->execute([json_encode($roles), $user['id']]);
            echo "Mis à jour !\n";
        } else {
            echo "OK\n";
        }
    }

    echo "\nVérification des rôles terminée.\n";

} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
}
?>
