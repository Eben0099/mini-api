<?php

// Script de test pour vérifier l'accès aux routes API

$baseUrl = 'http://localhost:8090'; // Serveur sur port 8090

echo "=== Test d'accès aux routes API ===\n\n";

// Routes qui doivent être accessibles SANS token
$publicRoutes = [
    'POST /api/auth/register' => '/api/auth/register',
    'POST /api/auth/login' => '/api/auth/login',
    'GET /api/auth/verify-email' => '/api/auth/verify-email',
    'POST /api/auth/resend-verification' => '/api/auth/resend-verification',
    'POST /api/admin/register' => '/api/admin/register',
    'POST /api/admin/login' => '/api/admin/login',
    'POST /api/admin/verify-otp' => '/api/admin/verify-otp',
    'POST /api/auth/forgot-password' => '/api/auth/forgot-password',
    'POST /api/auth/reset-password' => '/api/auth/reset-password',
    'POST /api/admin/social-login' => '/api/admin/social-login',
];

// Route qui doit nécessiter un token
$protectedRoute = 'GET /api/auth/me';

// Test des routes publiques
echo "1. Test des routes PUBLIQUES (sans token) :\n";
echo str_repeat("-", 50) . "\n";

foreach ($publicRoutes as $description => $route) {
    list($method, $endpoint) = explode(' ', $description, 2);
    $response = makeRequest($method, $baseUrl . $route);

    // Pour les tests, on s'attend à une erreur de validation ou une réponse normale
    // mais PAS à une erreur 401 "Not authenticated"
    $result = json_decode($response, true);

    if (isset($result['error']) && $result['error'] === 'Not authenticated') {
        echo "❌ $description - ACCÈS REFUSÉ (nécessite token)\n";
    } else {
        echo "✅ $description - Accessible sans token\n";
    }
}

echo "\n2. Test de la route PROTÉGÉE (avec/sans token) :\n";
echo str_repeat("-", 50) . "\n";

// Test sans token (doit échouer)
$responseNoToken = makeRequest('GET', $baseUrl . $protectedRoute);
$resultNoToken = json_decode($responseNoToken, true);

if (isset($resultNoToken['error']) && $resultNoToken['error'] === 'Not authenticated') {
    echo "✅ $protectedRoute - Correctement protégé (sans token)\n";
} else {
    echo "❌ $protectedRoute - Devrait nécessiter un token\n";
}

// Test avec token (si on arrive à se connecter)
echo "\n3. Test complet avec authentification :\n";
echo str_repeat("-", 50) . "\n";

$loginData = [
    'email' => 'admin@example.com',
    'password' => 'admin123'
];

$loginResponse = makeRequest('POST', $baseUrl . '/api/auth/login', $loginData);
$loginResult = json_decode($loginResponse, true);

if (isset($loginResult['token'])) {
    $token = $loginResult['token'];
    echo "✅ Connexion réussie, token obtenu\n";

    // Test de la route protégée avec token
    $responseWithToken = makeRequest('GET', $baseUrl . $protectedRoute, null, $token);
    $resultWithToken = json_decode($responseWithToken, true);

    if (isset($resultWithToken['email'])) {
        echo "✅ $protectedRoute - Accessible avec token\n";
    } else {
        echo "❌ $protectedRoute - Problème avec le token\n";
    }
} else {
    echo "❌ Impossible de se connecter pour tester avec token\n";
    echo "Réponse login: " . $loginResponse . "\n";
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

echo "\n=== Test terminé ===\n";

?>
