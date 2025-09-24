# Configuration Email + Migrations - Guide de démarrage

## 📧 Configuration Email (Filesystem)

Les emails sont maintenant configurés pour utiliser le **filesystem** au lieu de SMTP. Cela signifie que :

- ✅ **Pas besoin de serveur SMTP**
- ✅ **Les emails sont sauvegardés localement**
- ✅ **Parfait pour les tests et développement**
- ✅ **Facile à inspecter le contenu des emails**

### Localisation des emails
Les emails sont sauvegardés dans : `var/spool/`

Chaque email envoyé sera stocké dans un fichier séparé dans ce dossier.

## 🗄️ Exécution des migrations

### Option 1 : Script automatique (Linux/Mac)
```bash
./run_migrations.sh
```

### Option 2 : Script Windows
```batch
run_migrations.bat
```

### Option 3 : Commande manuelle
```bash
# Depuis la racine du projet
php bin/console doctrine:migrations:migrate --no-interaction
```

## ✅ Vérification de l'installation

Après avoir exécuté les migrations, vérifiez que :

1. **La base de données est mise à jour :**
   ```bash
   php bin/console doctrine:schema:validate
   ```

2. **Les services sont chargés :**
   ```bash
   php bin/console debug:container --tag=security.voter
   ```

3. **L'application démarre :**
   - Démarrer Docker : `docker-compose up -d`
   - Vérifier : http://localhost:8090

## 🧪 Test de l'API d'authentification

### 1. Inscription d'un utilisateur
```bash
curl -X POST http://localhost:8090/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "firstName": "Test",
    "lastName": "User",
    "accountType": "client"
  }'
```

**Réponse attendue :**
```json
{
  "message": "User registered successfully",
  "user": {
    "id": 1,
    "email": "test@example.com",
    "firstName": "Test",
    "lastName": "User",
    "roles": ["ROLE_CLIENT"],
    "isVerified": false
  },
  "requiresVerification": true
}
```

### 2. Vérifier l'email envoyé
Les emails sont sauvegardés dans `var/spool/`. Ouvrez un des fichiers pour voir le contenu de l'email de vérification.

### 3. Vérification d'email
Utilisez le token de vérification trouvé dans l'email :
```bash
curl -X POST http://localhost:8090/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "token": "votre_token_de_verification"
  }'
```

### 4. Connexion
```bash
curl -X POST http://localhost:8090/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Réponse avec tokens JWT :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "test@example.com",
    "firstName": "Test",
    "lastName": "User",
    "roles": ["ROLE_CLIENT"],
    "isVerified": true
  }
}
```

### 5. Accès au profil (avec token)
```bash
curl -X GET http://localhost:8090/auth/me \
  -H "Authorization: Bearer votre_token_jwt"
```

## 🔧 Dépannage

### Erreur "Migration not found"
Si vous avez une erreur de migration :
```bash
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:migrate
```

### Erreur "Table already exists"
Si une migration a déjà été appliquée :
```bash
php bin/console doctrine:migrations:status
```

### Erreur de mailer
Si vous voulez utiliser SMTP plus tard, modifiez `config/packages/mailer.yaml` :
```yaml
framework:
    mailer:
        dsn: 'smtp://user:pass@smtp.example.com:587'
```

### Vider les emails de test
```bash
rm -rf var/spool/*
```

## 📋 Checklist de validation

- [ ] Migrations exécutées avec succès
- [ ] Application démarre sans erreur
- [ ] Inscription utilisateur fonctionne
- [ ] Email de vérification généré (dans `var/spool/`)
- [ ] Vérification d'email fonctionne
- [ ] Connexion retourne des tokens JWT
- [ ] Accès au profil protégé fonctionne

## 🎯 Prochaines étapes

Une fois que tout fonctionne :

1. **Créer les contrôleurs API** pour salons, réservations, etc.
2. **Implémenter les Voters** pour la sécurité
3. **Ajouter les tests unitaires**
4. **Configurer un vrai serveur SMTP** pour la production

---

**✨ Votre API d'authentification est maintenant prête pour les tests !**
