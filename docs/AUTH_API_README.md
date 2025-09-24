# API d'Authentification - Documentation complète

## Vue d'ensemble

L'API d'authentification de Mini API fournit un système complet de gestion des comptes utilisateurs avec authentification JWT, vérification d'email, réinitialisation de mot de passe et gestion des rôles.

## Rôles utilisateur

Le système supporte 4 types de rôles :

- **ROLE_CLIENT** : Client standard (rôle par défaut)
- **ROLE_OWNER** : Propriétaire de salon
- **ROLE_STYLIST** : Coiffeur/styliste
- **ROLE_ADMIN** : Administrateur système

## Endpoints API

### 1. Inscription utilisateur
**POST** `/auth/register`

**Corps de la requête :**
```json
{
  "email": "user@example.com",
  "password": "securePassword123",
  "firstName": "John",
  "lastName": "Doe",
  "accountType": "client" // "client" ou "owner"
}
```

**Réponses :**
- **201** : Utilisateur créé avec succès (nécessite vérification email)
- **400** : Erreur de validation
- **422** : Données invalides

### 2. Connexion
**POST** `/auth/login`

**Corps de la requête :**
```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

**Réponses :**
- **200** : Connexion réussie avec tokens JWT
- **401** : Identifiants invalides

### 3. Rafraîchissement du token
**POST** `/auth/refresh`

**Corps de la requête :**
```json
{
  "refresh_token": "your_refresh_token_here"
}
```

**Réponses :**
- **200** : Nouveau token d'accès généré
- **401** : Token invalide

### 4. Vérification d'email
**POST** `/auth/verify-email`

**Corps de la requête :**
```json
{
  "token": "verification_token_from_email"
}
```

**Réponses :**
- **200** : Email vérifié avec succès
- **400** : Token invalide ou expiré

### 5. Renvoi d'email de vérification
**POST** `/auth/resend-verification`

**Corps de la requête :**
```json
{
  "email": "user@example.com"
}
```

**Réponses :**
- **200** : Email envoyé (si l'adresse existe)

### 6. Demande de réinitialisation de mot de passe
**POST** `/auth/forgot-password`

**Corps de la requête :**
```json
{
  "email": "user@example.com"
}
```

**Réponses :**
- **200** : Email envoyé (si l'adresse existe)

### 7. Réinitialisation de mot de passe
**POST** `/auth/reset-password`

**Corps de la requête :**
```json
{
  "token": "reset_token_from_email",
  "password": "newSecurePassword123",
  "passwordConfirm": "newSecurePassword123"
}
```

**Réponses :**
- **200** : Mot de passe réinitialisé
- **400** : Token invalide ou expiré

### 8. Profil utilisateur connecté
**GET** `/auth/me`

**Headers requis :**
```
Authorization: Bearer your_jwt_token
```

**Réponses :**
- **200** : Informations du profil utilisateur
- **401** : Non authentifié

### 9. Création d'administrateur (réservé aux admins)
**POST** `/auth/admin/register`

**Headers requis :**
```
Authorization: Bearer admin_jwt_token
```

**Corps de la requête :**
```json
{
  "email": "admin@example.com",
  "password": "adminPassword123",
  "firstName": "Admin",
  "lastName": "User"
}
```

### 10. Connexion administrateur
**POST** `/auth/admin/login`

**Corps de la requête :**
```json
{
  "email": "admin@example.com",
  "password": "adminPassword123"
}
```

### 11. Connexion sociale Firebase (Admin)
**POST** `/auth/admin/social-login`

**Corps de la requête :**
```json
{
  "idToken": "firebase_id_token"
}
```

## Structure des données

### DTOs de requête

#### UserCreateDto
```php
class UserCreateDto {
    public string $email;
    public string $password;
    public string $firstName;
    public string $lastName;
    public string $accountType; // "client" | "owner"
}
```

#### LoginRequestDto
```php
class LoginRequestDto {
    public string $email;
    public string $password;
}
```

#### PasswordForgotRequestDto
```php
class PasswordForgotRequestDto {
    public string $email;
}
```

#### PasswordResetRequestDto
```php
class PasswordResetRequestDto {
    public string $token;
    public string $password;
    public string $passwordConfirm;
}
```

### DTOs de réponse

#### AuthResponseDto
```php
class AuthResponseDto {
    public string $message;
    public UserResponseDto $user;
    public ?string $token;
    public ?string $refreshToken;
    public bool $requiresVerification;
}
```

#### UserResponseDto
```php
class UserResponseDto {
    public int $id;
    public string $email;
    public string $firstName;
    public string $lastName;
    public string $fullName;
    public ?string $phone;
    public array $roles;
    public string $createdAt;
    public bool $isVerified;
}
```

## Services

### AuthService
Gère l'inscription, la connexion, la vérification d'email et la gestion des administrateurs.

### PasswordResetService
Gère la réinitialisation des mots de passe via email.

### OAuthService
Gère les connexions sociales (Firebase) pour les administrateurs.

## Templates d'email

### Vérification d'email
- Template : `templates/emails/verification.html.twig`
- Sujet : "Vérification de votre adresse email"
- Lien d'expiration : 24 heures

### Réinitialisation de mot de passe
- Template : `templates/emails/password_reset.html.twig`
- Sujet : "Réinitialisation de votre mot de passe"
- Lien d'expiration : 1 heure

## Sécurité

### Validation des données
- Validation côté serveur avec Symfony Validator
- Sanitisation des entrées
- Protection contre les injections

### Authentification JWT
- Access token avec expiration courte
- Refresh token pour renouvellement automatique
- Stockage sécurisé des tokens côté client

### Vérification d'email
- Tokens de vérification uniques et temporaires
- Expiration automatique des tokens
- Protection contre les tentatives répétées

### Réinitialisation de mot de passe
- Tokens de réinitialisation sécurisés
- Expiration rapide (1 heure)
- Validation des nouveaux mots de passe

## Migration de base de données

Une migration Doctrine a été créée pour ajouter les champs nécessaires :

```sql
ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE user ADD verification_token VARCHAR(255) DEFAULT NULL;
ALTER TABLE user ADD reset_token VARCHAR(255) DEFAULT NULL;
ALTER TABLE user ADD reset_token_expires_at DATETIME DEFAULT NULL;
```

## Configuration

### Variables d'environnement
```env
# Mailer
MAILER_DSN=smtp://user:pass@smtp.example.com:587

# JWT
JWT_SECRET_KEY=your_secret_key_here
JWT_PUBLIC_KEY=your_public_key_here
JWT_PASSPHRASE=your_passphrase_here

# Application URL (pour les liens d'email)
APP_URL=https://yourapp.com
```

### Configuration des services
Les services sont automatiquement configurés via `config/services.yaml` avec les tags appropriés.

## Tests

### Tests unitaires
- Tests des DTOs
- Tests des services
- Tests de validation

### Tests d'intégration
- Tests des endpoints API
- Tests des workflows complets
- Tests d'email

## Exemples d'utilisation

### Inscription d'un client
```bash
curl -X POST http://localhost:8090/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "client@example.com",
    "password": "securePassword123",
    "firstName": "John",
    "lastName": "Doe",
    "accountType": "client"
  }'
```

### Connexion
```bash
curl -X POST http://localhost:8090/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "client@example.com",
    "password": "securePassword123"
  }'
```

### Accès à une ressource protégée
```bash
curl -X GET http://localhost:8090/auth/me \
  -H "Authorization: Bearer your_jwt_token_here"
```

## Gestion des erreurs

L'API retourne des erreurs structurées :

```json
{
  "error": "Description de l'erreur",
  "code": 400
}
```

Codes d'erreur courants :
- **400** : Données invalides ou requête malformée
- **401** : Non authentifié ou token invalide
- **403** : Accès refusé
- **404** : Ressource non trouvée
- **422** : Erreur de validation
- **500** : Erreur serveur interne

## Support et maintenance

### Logging
- Logs d'erreur pour le debugging
- Logs d'audit pour la sécurité
- Rotation automatique des logs

### Monitoring
- Métriques de performance
- Taux d'échec des authentifications
- Statistiques d'utilisation

Ce système d'authentification fournit une base solide et sécurisée pour votre application Mini API.
