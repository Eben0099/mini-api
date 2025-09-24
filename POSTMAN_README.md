# 🧪 Collection Postman - API d'Authentification Mini API

## 📦 Fichiers générés

- `Mini_API_Auth_Postman_Collection.json` - Collection Postman complète
- `Mini_API_Postman_Environment.postman_environment.json` - Variables d'environnement

## 🚀 Import dans Postman

### Étape 1 : Importer la collection
1. Ouvrir Postman
2. Cliquer sur "Import" (en haut à gauche)
3. Sélectionner "File"
4. Importer `Mini_API_Auth_Postman_Collection.json`

### Étape 2 : Importer l'environnement
1. Dans Postman, cliquer sur "Environments" (à gauche)
2. Cliquer sur "Import"
3. Importer `Mini_API_Postman_Environment.postman_environment.json`
4. Sélectionner l'environnement "Mini API - Environment"

## 🔧 Configuration

### Variables d'environnement
- `base_url` : http://localhost:8090 (URL de votre API)
- `test_email` : Email de test (modifiez selon vos besoins)
- `test_password` : Mot de passe de test
- `admin_email` : Email administrateur
- `admin_password` : Mot de passe administrateur

## 📋 Workflows de test

### 🔄 Workflow complet : Inscription → Vérification → Connexion

1. **Inscription Client**
   ```
   POST /auth/register
   ```
   - Crée un compte utilisateur
   - Un email de vérification est envoyé

2. **Récupérer le token de vérification**
   ```
   📧 Ouvrir le fichier email dans var/spool/
   🔍 Chercher le lien de vérification
   📝 Copier le token et le coller dans la variable "verification_token"
   ```

3. **Vérifier l'email**
   ```
   POST /auth/verify-email
   ```
   - Utilise le token récupéré

4. **Se connecter**
   ```
   POST /auth/login
   ```
   - Retourne le token JWT automatiquement stocké

5. **Accéder au profil**
   ```
   GET /auth/me
   ```
   - Vérifie que l'authentification fonctionne

### 🔑 Workflow : Gestion des tokens

1. **Connexion normale**
   ```
   POST /auth/login
   ```

2. **Rafraîchir le token**
   ```
   POST /auth/refresh
   ```
   - Génère automatiquement un nouveau token

3. **Vérifier l'accès**
   ```
   GET /auth/me
   ```

### 🔒 Workflow : Réinitialisation de mot de passe

1. **Demander la réinitialisation**
   ```
   POST /auth/forgot-password
   ```

2. **Récupérer le token de réinitialisation**
   ```
   📧 Ouvrir l'email dans var/spool/
   🔍 Chercher le lien de réinitialisation
   📝 Extraire le token de l'URL
   ```

3. **Réinitialiser le mot de passe**
   ```
   POST /auth/reset-password
   ```

4. **Se connecter avec le nouveau mot de passe**
   ```
   POST /auth/login
   ```

## 📧 Gestion des emails (Filesystem)

### Localisation des emails
```
var/spool/
```

### Format des emails
Chaque email est sauvegardé dans un fichier séparé avec :
- Headers SMTP
- Corps HTML de l'email
- Liens de vérification/réinitialisation

### Comment récupérer les tokens

#### Token de vérification d'email
```html
<!-- Chercher dans l'email -->
<a href="http://localhost:8090/auth/verify-email?token=ABC123...">Vérifier Email</a>

<!-- Extraire le token : ABC123... -->
```

#### Token de réinitialisation MDP
```html
<!-- Chercher dans l'email -->
<a href="http://localhost:8090/auth/reset-password?token=XYZ789...">Reset Password</a>

<!-- Extraire le token : XYZ789... -->
```

## 🧪 Tests automatisés

### Scripts Postman inclus

#### Stockage automatique des tokens
- **Connexion** : Stocke automatiquement `auth_token` et `refresh_token`
- **Refresh** : Met à jour automatiquement `auth_token`

#### Logging des réponses
- ✅ **200-299** : Succès (vert)
- ❌ **400-599** : Erreur (rouge avec détails)

## 📊 Endpoints couverts

### Utilisateur Standard
- ✅ POST `/auth/register` - Inscription
- ✅ POST `/auth/login` - Connexion
- ✅ POST `/auth/refresh` - Rafraîchissement token
- ✅ POST `/auth/verify-email` - Vérification email
- ✅ POST `/auth/resend-verification` - Renvoi email
- ✅ POST `/auth/forgot-password` - Demande réinit MDP
- ✅ POST `/auth/reset-password` - Réinit MDP
- ✅ GET `/auth/me` - Profil utilisateur

### Administration
- ✅ POST `/auth/admin/register` - Créer admin
- ✅ POST `/auth/admin/login` - Connexion admin
- ✅ POST `/auth/admin/social-login` - Connexion Firebase

## 🔍 Debugging

### Console Postman
- Ouvrir la console : `View → Show Postman Console`
- Voir tous les logs et erreurs
- Inspecter les requêtes/réponses détaillées

### Variables de collection
- `pm.collectionVariables.get('auth_token')` - Token actuel
- `pm.collectionVariables.set('verification_token', 'token')` - Définir manuellement

## 🚨 Points d'attention

### Sécurité des tokens
- ⚠️ **Ne partagez jamais** les tokens JWT en production
- ⚠️ **Les tokens de vérification** expirent après 24h
- ⚠️ **Les tokens de réinitialisation** expirent après 1h

### Emails filesystem
- 📁 **Vérifiez le dossier** `var/spool/` après chaque envoi
- 🗑️ **Videz régulièrement** : `rm -rf var/spool/*`
- 🔄 **Pour la production** : configurez un vrai serveur SMTP

### Gestion des erreurs
- **400** : Données invalides → Vérifiez le JSON
- **401** : Non autorisé → Vérifiez le token
- **422** : Validation → Vérifiez les champs requis
- **500** : Erreur serveur → Vérifiez les logs Symfony

## 🎯 Checklist de test

- [ ] Collection importée dans Postman
- [ ] Environnement configuré
- [ ] Variables définies (URLs, emails)
- [ ] API démarrée (Docker)
- [ ] Migrations exécutées
- [ ] Inscription réussie
- [ ] Email de vérification récupéré
- [ ] Vérification email réussie
- [ ] Connexion réussie
- [ ] Token JWT généré
- [ ] Accès au profil protégé
- [ ] Rafraîchissement token fonctionnel

---

## 🎉 Prêt à tester !

Importez la collection, configurez l'environnement, et commencez à tester votre API d'authentification ! 🚀
