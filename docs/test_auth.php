<?php

// Script de test pour déboguer l'authentification JWT

$baseUrl = 'http://localhost:8090'; // Serveur sur port 8090

echo "=== Test d'authentification JWT ===\n\n";

// 1. Test de login
echo "1. Tentative de connexion...\n";
$loginData = [
    'email' => 'admin@example.com', // Email admin par défaut
    'password' => 'admin123'        // Mot de passe admin par défaut
];

$loginResponse = makeRequest('POST', $baseUrl . '/api/auth/login', $loginData);
echo "Réponse login: " . $loginResponse . "\n\n";

$loginResult = json_decode($loginResponse, true);

if (!isset($loginResult['token'])) {
    echo "❌ Échec de la connexion. Vérifiez les identifiants.\n";
    exit(1);
}

$token = $loginResult['token'];
echo "✅ Token obtenu: " . substr($token, 0, 50) . "...\n\n";

// 2. Test de l'endpoint /api/auth/me
echo "2. Test de l'endpoint /api/auth/me...\n";
$meResponse = makeRequest('GET', $baseUrl . '/api/auth/me', null, $token);
echo "Réponse /api/auth/me: " . $meResponse . "\n\n";

$meResult = json_decode($meResponse, true);

if (isset($meResult['email'])) {
    echo "✅ Authentification réussie!\n";
    echo "Utilisateur: " . $meResult['email'] . "\n";
} else {
    echo "❌ Échec de l'authentification\n";
    if (isset($meResult['debug'])) {
        echo "Debug info:\n";
        echo "- Header Authorization présent: " . ($meResult['debug']['auth_header_present'] ? 'Oui' : 'Non') . "\n";
        echo "- Header commence par 'Bearer ': " . ($meResult['debug']['auth_header_starts_with_bearer'] ? 'Oui' : 'Non') . "\n";
    }
}

function makeRequest($method, $url, $data = null, $token = null) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Content-Type: application/json'];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return 'Erreur cURL: ' . curl_error($ch);
    }

    curl_close($ch);
    return $response;
}

?>
