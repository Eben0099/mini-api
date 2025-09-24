#!/bin/bash

echo "🚀 Exécution des migrations Doctrine..."

# Aller dans le répertoire du projet
cd "$(dirname "$0")"

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction

echo "✅ Migrations terminées !"
echo ""
echo "📧 Configuration mailer : filesystem (les emails seront sauvegardés dans var/spool/)"
echo ""
echo "🎯 Pour tester l'inscription :"
echo "curl -X POST http://localhost:8090/auth/register \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{"
echo "    \"email\": \"test@example.com\","
echo "    \"password\": \"password123\","
echo "    \"firstName\": \"Test\","
echo "    \"lastName\": \"User\","
echo "    \"accountType\": \"client\""
echo "  }'"
