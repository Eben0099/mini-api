<?php

// Script de test pour la fonctionnalité de refresh token

$baseUrl = 'http://localhost:8090';

echo "=== Test de la fonctionnalité Refresh Token ===\n\n";

// Étape 1: Connexion pour obtenir les tokens
echo "1. Connexion pour obtenir les tokens...\n";

$loginData = [
    'email' => 'admin@example.com',
    'password' => 'admin123'
];

$loginResponse = makeRequest('POST', $baseUrl . '/api/auth/login', $loginData);
$loginResult = json_decode($loginResponse, true);

if (!isset($loginResult['token']) || !isset($loginResult['refreshToken'])) {
    echo "❌ Échec de la connexion ou pas de refresh token\n";
    echo "Réponse: " . $loginResponse . "\n";
    exit(1);
}

$token = $loginResult['token'];
$refreshToken = $loginResult['refreshToken'];

echo "✅ Connexion réussie\n";
echo "Token: " . substr($token, 0, 30) . "...\n";
echo "Refresh Token: " . substr($refreshToken, 0, 30) . "...\n\n";

// Étape 2: Test de /api/auth/me avec le token
echo "2. Test de /api/auth/me avec le token...\n";

$meResponse = makeRequest('GET', $baseUrl . '/api/auth/me', null, $token);
$meResult = json_decode($meResponse, true);

if (isset($meResult['email'])) {
    echo "✅ /api/auth/me fonctionne avec le token\n";
    echo "Utilisateur: " . $meResult['email'] . "\n\n";
} else {
    echo "❌ /api/auth/me ne fonctionne pas\n";
    echo "Réponse: " . $meResponse . "\n\n";
}

// Étape 3: Test du refresh token
echo "3. Test du refresh token...\n";

$refreshData = [
    'refreshToken' => $refreshToken
];

$refreshResponse = makeRequest('POST', $baseUrl . '/api/auth/refresh', $refreshData);
$refreshResult = json_decode($refreshResponse, true);

if (isset($refreshResult['token']) && isset($refreshResult['refreshToken'])) {
    echo "✅ Refresh token fonctionne!\n";
    echo "Nouveau token: " . substr($refreshResult['token'], 0, 30) . "...\n";
    echo "Nouveau refresh token: " . substr($refreshResult['refreshToken'], 0, 30) . "...\n\n";

    // Étape 4: Vérifier que le nouveau token fonctionne
    echo "4. Test du nouveau token avec /api/auth/me...\n";
    $newToken = $refreshResult['token'];
    $meResponse2 = makeRequest('GET', $baseUrl . '/api/auth/me', null, $newToken);
    $meResult2 = json_decode($meResponse2, true);

    if (isset($meResult2['email'])) {
        echo "✅ Le nouveau token fonctionne parfaitement!\n";
    } else {
        echo "❌ Le nouveau token ne fonctionne pas\n";
        echo "Réponse: " . $meResponse2 . "\n";
    }

} else {
    echo "❌ Refresh token échoue\n";
    echo "Réponse: " . $refreshResponse . "\n";
}

echo "\n=== Test terminé ===\n";

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
