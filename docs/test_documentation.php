<?php

// Script de test pour vérifier la documentation API Nelmio

echo "=== Test de la documentation API Nelmio ===\n\n";

$baseUrl = 'http://localhost:8090';

// Test de l'endpoint JSON de la documentation
echo "1. Test de l'endpoint JSON (/api/doc.json) :\n";
echo str_repeat("-", 50) . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/doc.json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

curl_close($ch);

echo "URL: {$baseUrl}/api/doc.json\n";
echo "Code HTTP: {$httpCode}\n";
echo "Content-Type: {$contentType}\n";

if ($httpCode === 200) {
    echo "✅ Endpoint JSON accessible\n";

    // Vérifier si c'est du JSON valide
    $jsonData = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ Réponse JSON valide\n";

        // Vérifier la structure de base
        if (isset($jsonData['openapi']) && isset($jsonData['info'])) {
            echo "✅ Structure OpenAPI détectée\n";
            echo "Version OpenAPI: {$jsonData['openapi']}\n";
            echo "Titre: " . (isset($jsonData['info']['title']) ? $jsonData['info']['title'] : 'N/A') . "\n";
            echo "Version API: " . (isset($jsonData['info']['version']) ? $jsonData['info']['version'] : 'N/A') . "\n";
        } else {
            echo "❌ Structure OpenAPI non détectée\n";
        }

        // Compter les chemins
        if (isset($jsonData['paths'])) {
            $pathCount = count($jsonData['paths']);
            echo "Nombre de chemins documentés: {$pathCount}\n";
        }

    } else {
        echo "❌ Réponse JSON invalide: " . json_last_error_msg() . "\n";
        echo "Aperçu de la réponse: " . substr($response, 0, 200) . "...\n";
    }

} else {
    echo "❌ Endpoint JSON non accessible\n";
    echo "Réponse: " . substr($response, 0, 200) . "\n";
}

echo "\n";

// Test de l'interface Swagger UI
echo "2. Test de l'interface Swagger UI (/api/doc) :\n";
echo str_repeat("-", 50) . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/doc');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

curl_close($ch);

echo "URL: {$baseUrl}/api/doc\n";
echo "Code HTTP: {$httpCode}\n";
echo "Content-Type: {$contentType}\n";

if ($httpCode === 200) {
    echo "✅ Interface Swagger UI accessible\n";

    // Vérifier si c'est du HTML avec Swagger
    if (strpos($response, 'swagger') !== false || strpos($response, 'Swagger') !== false) {
        echo "✅ Contenu Swagger détecté dans la réponse\n";
    } else {
        echo "⚠️ Contenu Swagger non détecté (mais page accessible)\n";
    }

} else {
    echo "❌ Interface Swagger UI non accessible\n";
    echo "Réponse: " . substr($response, 0, 200) . "\n";
}

echo "\n=== Test terminé ===\n";
