@echo off
echo 🚀 Test rapide de l'API Mini avec mailer filesystem
echo ==================================================

REM Nettoyer les anciens emails
echo 🧹 Nettoyage des emails...
if exist "var\spool" (
    rmdir /s /q var\spool
)
mkdir var\spool 2>nul

echo.

REM Tester l'endpoint de base
echo 1. Test endpoint de base...
curl -s -o nul -w "%%{http_code}" http://localhost:8090/ > temp_response.txt
set /p response=<temp_response.txt
del temp_response.txt

if "%response%"=="200" (
    echo ✅ API répond (HTTP 200)
) else (
    echo ❌ API ne répond pas (HTTP %response%)
    echo    Vérifiez que Docker est démarré: docker-compose up -d
    pause
    exit /b 1
)

echo.

REM Tester l'inscription
echo 2. Test inscription utilisateur...
curl -s -X POST http://localhost:8090/auth/register ^
  -H "Content-Type: application/json" ^
  -d "{""email"":""test@example.com"",""password"":""password123"",""firstName"":""Test"",""lastName"":""User"",""accountType"":""client""}" > temp_response.json

curl -s -o nul -w "%%{http_code}" -X POST http://localhost:8090/auth/register ^
  -H "Content-Type: application/json" ^
  -d "{""email"":""test@example.com"",""password"":""password123"",""firstName"":""Test"",""lastName"":""User"",""accountType"":""client""}" > temp_http.txt

set /p http_code=<temp_http.txt
del temp_http.txt

if "%http_code%"=="201" (
    echo ✅ Inscription réussie (HTTP 201)
) else (
    echo ❌ Inscription échouée (HTTP %http_code%)
    type temp_response.json
    del temp_response.json
    pause
    exit /b 1
)

del temp_response.json

echo.

REM Vérifier l'email
echo 3. Vérification de l'email...
if exist "var\spool\*" (
    dir /b var\spool\ | find /c "::" > temp_count.txt
    set /p email_count=<temp_count.txt
    del temp_count.txt
    echo ✅ %email_count% email(s) créé(s) dans var\spool\

    REM Chercher le token de vérification
    findstr /r "verify-email?token=" var\spool\* > temp_token.txt 2>nul
    if exist temp_token.txt (
        for /f "tokens=*" %%a in (temp_token.txt) do (
            echo 🔑 Token trouvé dans l'email
            echo    Utilisez ce token pour: POST /auth/verify-email
        )
    ) else (
        echo ⚠️ Token de vérification non trouvé dans l'email
    )
    if exist temp_token.txt del temp_token.txt
) else (
    echo ❌ Aucun email trouvé dans var\spool\
    echo    Le mailer filesystem ne fonctionne pas correctement
    pause
    exit /b 1
)

echo.
echo 🎉 Test réussi ! L'API et le mailer fonctionnent correctement.
echo.
echo 📋 Prochaines étapes :
echo    1. Importez la collection Postman
echo    2. Récupérez le token dans var\spool\ pour vérifier l'email
echo    3. Testez la connexion et les autres endpoints
echo.
echo 📚 Documentation: MAIL_SETUP_README.md et POSTMAN_README.md
pause
