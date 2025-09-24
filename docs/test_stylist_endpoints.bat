@echo off
echo === Test des endpoints stylist ===
echo.

echo Étape 1: Connexion en tant que stylist...
curl -X POST http://localhost:8090/api/auth/login -H "Content-Type: application/json" -d "{\"email\":\"stylist@example.com\",\"password\":\"password123\"}" > temp_token.json

echo.
echo Token JWT obtenu:
type temp_token.json
echo.

echo Étape 2: Extraction du token...
for /f "tokens=2 delims=:," %%a in ('type temp_token.json ^| findstr "token"') do set TOKEN=%%a
set TOKEN=%TOKEN:"=%
set TOKEN=%TOKEN: =%

echo Token extrait: %TOKEN%
echo.

echo Étape 3: Test de l'endpoint GET stylists/{id}/media...
echo (Remplacez {id} par l'ID du stylist obtenu lors de la création des données)
echo Exemple: curl -H "Authorization: Bearer %TOKEN%" http://localhost:8090/api/v1/stylists/1/media
echo.

pause
del temp_token.json
