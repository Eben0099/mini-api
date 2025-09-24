<?php
// Vérifier l'authentification de l'utilisateur

// Configuration de la base de données
$host = 'localhost';
$port = '4310';
$dbname = 'mini_api';
$username = 'symfony';
$password = 'symfony';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== VÉRIFICATION AUTHENTIFICATION ===\n\n";

    // Vérifier les utilisateurs
    $stmt = $pdo->query("SELECT id, email, roles, is_verified FROM user ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Utilisateurs dans la base:\n";
    foreach ($users as $user) {
        $roles = json_decode($user['roles'], true);
        $roleString = implode(', ', $roles);
        $verified = $user['is_verified'] ? '✅ Vérifié' : '❌ Non vérifié';

        echo "- ID {$user['id']}: {$user['email']} | Rôles: {$roleString} | {$verified}\n";
    }

    echo "\n🔍 L'utilisateur 'client@example.com' utilisé dans Postman:\n";

    $stmt = $pdo->prepare("SELECT id, email, roles, is_verified FROM user WHERE email = ?");
    $stmt->execute(['client@example.com']);
    $clientUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($clientUser) {
        $roles = json_decode($clientUser['roles'], true);
        echo "- ID: {$clientUser['id']}\n";
        echo "- Email: {$clientUser['email']}\n";
        echo "- Rôles: " . implode(', ', $roles) . "\n";
        echo "- Vérifié: " . ($clientUser['is_verified'] ? '✅ OUI' : '❌ NON') . "\n";
        echo "- A ROLE_CLIENT: " . (in_array('ROLE_CLIENT', $roles) ? '✅ OUI' : '❌ NON') . "\n";

        if (!in_array('ROLE_CLIENT', $roles)) {
            echo "\n❌ PROBLÈME: L'utilisateur n'a pas le rôle ROLE_CLIENT !\n";
            echo "SOLUTION: Ajouter ROLE_CLIENT au compte client\n";
        }

        if (!$clientUser['is_verified']) {
            echo "\n❌ PROBLÈME: L'utilisateur n'est pas vérifié !\n";
            echo "SOLUTION: Vérifier l'email ou marquer comme vérifié en base\n";
        }

        if (!in_array('ROLE_CLIENT', $roles) || !$clientUser['is_verified']) {
            echo "\n🔧 CORRECTION AUTOMATIQUE:\n";

            if (!in_array('ROLE_CLIENT', $roles)) {
                $roles[] = 'ROLE_CLIENT';
                $stmt = $pdo->prepare("UPDATE user SET roles = ? WHERE id = ?");
                $stmt->execute([json_encode($roles), $clientUser['id']]);
                echo "✅ Ajout du rôle ROLE_CLIENT\n";
            }

            if (!$clientUser['is_verified']) {
                $stmt = $pdo->prepare("UPDATE user SET is_verified = 1 WHERE id = ?");
                $stmt->execute([$clientUser['id']]);
                echo "✅ Marquage comme vérifié\n";
            }

            echo "\n🎉 L'utilisateur est maintenant prêt pour les réservations !\n";
        } else {
            echo "\n✅ L'utilisateur est correctement configuré pour les réservations\n";
        }

    } else {
        echo "❌ L'utilisateur 'client@example.com' n'existe pas !\n";
        echo "SOLUTION: Créer le compte client avec Postman\n";
    }

    // Vérifier aussi l'utilisateur propriétaire
    echo "\n🔍 Vérification de l'utilisateur propriétaire:\n";

    $stmt = $pdo->prepare("SELECT id, email, roles, is_verified FROM user WHERE email = ?");
    $stmt->execute(['owner@example.com']);
    $ownerUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($ownerUser) {
        $roles = json_decode($ownerUser['roles'], true);
        echo "- A ROLE_OWNER: " . (in_array('ROLE_OWNER', $roles) ? '✅ OUI' : '❌ NON') . "\n";
        echo "- Vérifié: " . ($ownerUser['is_verified'] ? '✅ OUI' : '❌ NON') . "\n";
    } else {
        echo "❌ L'utilisateur 'owner@example.com' n'existe pas !\n";
    }

} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
?>
