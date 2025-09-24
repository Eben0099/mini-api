#!/bin/bash

echo "🧹 Nettoyage des emails de test..."

# Vérifier si le dossier existe
if [ -d "var/spool" ]; then
    # Compter les emails avant suppression
    email_count=$(ls -1 var/spool/ | wc -l)
    echo "📧 $email_count email(s) trouvé(s)"

    # Supprimer tous les emails
    rm -rf var/spool/*
    echo "✅ Emails supprimés"
else
    echo "📁 Dossier var/spool inexistant (pas d'emails à supprimer)"
fi

echo ""
echo "🎯 Prochain test prêt !"
