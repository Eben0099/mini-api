<?php

// Test pour déboguer l'erreur 500 dans refresh token
echo "=== Débogage de l'erreur 500 dans refresh token ===\n\n";

// Test 1: Avec un token invalide (devrait retourner 401)
echo "Test 1: Token invalide\n";
$ch1 = curl_init();
curl_setopt($ch1, CURLOPT_URL, 'http://localhost:8090/api/auth/refresh');
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_POST, true);
curl_setopt($ch1, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch1, CURLOPT_POSTFIELDS, json_encode(['refresh_token' => 'invalid_token_123']));

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode1\n";
echo "Response: $response1\n\n";

curl_close($ch1);

// Test 2: Avec un token vide (devrait retourner 400)
echo "Test 2: Token vide\n";
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, 'http://localhost:8090/api/auth/refresh');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode(['refresh_token' => '']));

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode2\n";
echo "Response: $response2\n\n";

curl_close($ch2);

// Test 3: JSON mal formé (devrait retourner 400)
echo "Test 3: JSON mal formé\n";
$ch3 = curl_init();
curl_setopt($ch3, CURLOPT_URL, 'http://localhost:8090/api/auth/refresh');
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_POST, true);
curl_setopt($ch3, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch3, CURLOPT_POSTFIELDS, '{"refresh_token": "test", invalid json}');

$response3 = curl_exec($ch3);
$httpCode3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode3\n";
echo "Response: $response3\n\n";

curl_close($ch3);

echo "=== Logs Symfony (vérifiez les logs du container) ===\n";

?>
