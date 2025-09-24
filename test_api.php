<?php

echo "🧪 Test de l'API d'authentification" . PHP_EOL;
echo str_repeat("=", 50) . PHP_EOL;

// Test 1: Endpoint de base
echo "1. Test de l'endpoint de base (/)" . PHP_EOL;
$ch = curl_init('http://localhost:8090/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Endpoint de base fonctionne (HTTP $httpCode)" . PHP_EOL;
    $data = json_decode($response, true);
    if ($data && isset($data['message'])) {
        echo "   Message: " . $data['message'] . PHP_EOL;
    }
} else {
    echo "❌ Endpoint de base ne fonctionne pas (HTTP $httpCode)" . PHP_EOL;
}

echo PHP_EOL;

// Test 2: Endpoint d'inscription (devrait retourner erreur de validation)
echo "2. Test de l'endpoint d'inscription (/auth/register)" . PHP_EOL;
$ch = curl_init('http://localhost:8090/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => 'invalid']));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 0 && $httpCode < 500) {
    echo "✅ API répond sans erreur 500 (HTTP $httpCode)" . PHP_EOL;
    if ($httpCode === 400) {
        echo "   ✓ Erreur de validation attendue" . PHP_EOL;
    }
} else {
    echo "❌ Erreur serveur (HTTP $httpCode)" . PHP_EOL;
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['error'])) {
            echo "   Erreur: " . $data['error'] . PHP_EOL;
        }
    }
}

echo PHP_EOL;
echo "🎯 Résumé du test :" . PHP_EOL;
echo "- Si les deux tests passent, l'API fonctionne correctement" . PHP_EOL;
echo "- Si vous voyez une erreur 500, vérifiez les logs Symfony" . PHP_EOL;
echo "- Les emails seront sauvegardés dans var/spool/" . PHP_EOL;
