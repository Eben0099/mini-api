#!/bin/bash

echo "🚀 Test rapide de l'API Mini avec mailer filesystem"
echo "=================================================="

# Nettoyer les anciens emails
echo "🧹 Nettoyage des emails..."
rm -rf var/spool/*
mkdir -p var/spool

echo ""

# Tester l'endpoint de base
echo "1. Test endpoint de base..."
response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8090/)
if [ "$response" -eq 200 ]; then
    echo "✅ API répond (HTTP 200)"
else
    echo "❌ API ne répond pas (HTTP $response)"
    echo "   Vérifiez que Docker est démarré: docker-compose up -d"
    exit 1
fi

echo ""

# Tester l'inscription
echo "2. Test inscription utilisateur..."
response=$(curl -s -X POST http://localhost:8090/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "firstName": "Test",
    "lastName": "User",
    "accountType": "client"
  }')

http_code=$(curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost:8090/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "firstName": "Test",
    "lastName": "User",
    "accountType": "client"
  }')

if [ "$http_code" -eq 201 ]; then
    echo "✅ Inscription réussie (HTTP 201)"
else
    echo "❌ Inscription échouée (HTTP $http_code)"
    echo "   Réponse: $response"
    exit 1
fi

echo ""

# Vérifier l'email
echo "3. Vérification de l'email..."
if [ -d "var/spool" ] && [ "$(ls -A var/spool 2>/dev/null)" ]; then
    email_count=$(ls -1 var/spool/ | wc -l)
    echo "✅ $email_count email(s) créé(s) dans var/spool/"

    # Afficher le token de vérification
    token=$(grep -o 'verify-email?token=[^"]*' var/spool/* | head -1 | sed 's/.*token=//')
    if [ ! -z "$token" ]; then
        echo "🔑 Token de vérification trouvé: $token"
        echo "   Utilisez ce token pour: POST /auth/verify-email"
    fi
else
    echo "❌ Aucun email trouvé dans var/spool/"
    echo "   Le mailer filesystem ne fonctionne pas correctement"
    exit 1
fi

echo ""
echo "🎉 Test réussi ! L'API et le mailer fonctionnent correctement."
echo ""
echo "📋 Prochaines étapes :"
echo "   1. Importez la collection Postman"
echo "   2. Utilisez le token ci-dessus pour vérifier l'email"
echo "   3. Testez la connexion et les autres endpoints"
echo ""
echo "📚 Documentation: MAIL_SETUP_README.md et POSTMAN_README.md"
