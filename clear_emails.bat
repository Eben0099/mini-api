@echo off
echo 🧹 Nettoyage des emails de test...

REM Vérifier si le dossier existe
if exist "var\spool" (
    REM Compter les emails avant suppression
    dir /b var\spool\ | find /c "::" > temp_count.txt
    set /p email_count=<temp_count.txt
    del temp_count.txt
    echo 📧 %email_count% email(s) trouvé(s)

    REM Supprimer tous les emails
    del /q var\spool\*
    echo ✅ Emails supprimés
) else (
    echo 📁 Dossier var\spool inexistant (pas d'emails à supprimer)
)

echo.
echo 🎯 Prochain test prêt !
pause
