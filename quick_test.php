<?php

// Test rapide de la route /api/auth/me
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8090/api/auth/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

curl_close($ch);

?>
