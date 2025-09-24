<?php

// Test simple de la route refresh token
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8090/api/auth/refresh');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['refreshToken' => 'test_token']));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

curl_close($ch);

?>
