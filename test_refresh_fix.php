<?php

// Test de la route refresh après correction
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8090/api/auth/refresh');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['refresh_token' => 'test_token']));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Test après correction getUser() -> getUsername():\n";
echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

curl_close($ch);

?>
