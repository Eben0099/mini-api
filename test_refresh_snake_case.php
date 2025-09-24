<?php

// Test de la route refresh avec snake_case
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8090/api/auth/refresh');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['refresh_token' => 'test_token']));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Test avec refresh_token (snake_case):\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

curl_close($ch);

?>
