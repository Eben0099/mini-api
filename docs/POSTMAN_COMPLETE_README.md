# 🧪 Collection Postman Complète - API Salon de Coiffure

## 📋 Vue d'ensemble

Cette collection Postman couvre **toutes les fonctionnalités** développées pour l'API de gestion des salons de coiffure :

### ✅ Fonctionnalités incluses :

#### 🔐 **Authentification**
- Inscription propriétaire salon (ROLE_OWNER)
- Inscription client (ROLE_CLIENT)
- Connexion avec tokens JWT

#### 🏢 **CRUD Salons**
- Création salon (OWNER uniquement)
- Liste salons publique avec filtres (ville, nom)
- Détail salon avec services & stylists
- Modification salon (OWNER/ADMIN)
- Mise à jour horaires (OWNER uniquement)
- Suppression salon (OWNER/ADMIN)

#### 💇‍♀️ **Gestion Services**
- Ajout service au salon (OWNER)
- Modification service (OWNER)
- Désactivation/activation service
- Suppression service (OWNER)

#### 👥 **Gestion Stylists**
- Association utilisateur → stylist (OWNER)
- Définition compétences & langues
- Retrait stylist du salon (OWNER)

#### 📅 **Exceptions d'ouverture**
- Fermeture salon (congés, vacances)
- Absence stylist (maladie, congés)

#### 📅 **Disponibilités & Réservations**
- Consultation créneaux disponibles
- Réservation par client
- Gestion réservations personnelles
- Annulation (règles métier)

#### 📝 **Liste d'attente**
- Inscription automatique si créneau complet
- Notification simulée lors de libération

---

## 🚀 Guide d'utilisation

### 1. **Prérequis**
- API démarrée sur `http://localhost:8090`
- Base de données MySQL configurée
- Migrations Doctrine exécutées

### 2. **Configuration Postman**
1. Importer `Mini_API_Complete_Postman_Collection.json`
2. Importer l'environnement `Mini_API_Salons_Postman_Environment.postman_environment.json`
3. Variables configurées automatiquement :
   - `base_url` : `http://localhost:8090`
   - `auth_token` : Token propriétaire (auto-stocké)
   - `client_auth_token` : Token client (auto-stocké)
   - `salon_id`, `service_id`, `stylist_id`, etc. (auto-stockés)

### 3. **Workflow de test recommandé**

#### Phase 1 : Authentification 🔐
```
1. Créer compte propriétaire salon
2. Créer compte client
3. Connexion propriétaire → Token stocké
4. Connexion client → Token stocké
```

#### Phase 2 : Configuration salon 🏢
```
5. Créer salon (OWNER) → salon_id stocké
6. Vérifier salon public (sans auth)
7. Modifier horaires salon (OWNER)
```

#### Phase 3 : Services & Stylists 💇‍♀️👥
```
8. Ajouter services (OWNER) → service_id stocké
9. Associer stylists (OWNER) → stylist_id stocké
10. Vérifier détail salon complet (public)
```

#### Phase 4 : Exceptions 📅
```
11. Ajouter fermeture exceptionnelle salon
12. Ajouter absence stylist
```

#### Phase 5 : Réservations 📅
```
13. Consulter disponibilités (public)
14. Créer réservation (CLIENT) → booking_id stocké
15. Vérifier réservations personnelles (CLIENT)
16. Vérifier créneau réservé indisponible
```

#### Phase 6 : Liste d'attente 📝
```
17. S'inscrire liste d'attente (CLIENT)
18. Annuler réservation → Notification simulée
```

---

## 🎯 Endpoints testés

| Endpoint | Méthode | Auth | Description |
|----------|---------|------|-------------|
| `/api/auth/register` | POST | ❌ | Inscription |
| `/api/auth/login` | POST | ❌ | Connexion |
| `/api/v1/salons` | GET | ❌ | Liste salons |
| `/api/v1/salons` | POST | OWNER | Créer salon |
| `/api/v1/salons/{id}` | GET | ❌ | Détail salon |
| `/api/v1/salons/{id}` | PATCH | OWNER | Modifier salon |
| `/api/v1/salons/{id}/hours` | PUT | OWNER | Modifier horaires |
| `/api/v1/salons/{id}/services` | POST | OWNER | Ajouter service |
| `/api/v1/services/{id}` | PATCH | OWNER | Modifier service |
| `/api/v1/services/{id}` | DELETE | OWNER | Supprimer service |
| `/api/v1/salons/{id}/stylists` | POST | OWNER | Associer stylist |
| `/api/v1/stylists/{id}` | DELETE | OWNER | Retirer stylist |
| `/api/v1/availability-exceptions` | POST | OWNER | Ajouter exception |
| `/api/v1/salons/{id}/availability` | GET | ❌ | Disponibilités |
| `/api/v1/bookings` | POST | CLIENT | Créer réservation |
| `/api/v1/bookings/my` | GET | CLIENT | Mes réservations |
| `/api/v1/bookings/upcoming` | GET | CLIENT | Réservations futures |
| `/api/v1/bookings/{id}` | DELETE | CLIENT/OWNER | Annuler |
| `/api/v1/waitlist` | POST | CLIENT | Liste d'attente |

---

## 🔧 Variables automatiques

Postman stocke automatiquement les IDs dans les variables de collection :

- `auth_token` : Token propriétaire
- `client_auth_token` : Token client
- `salon_id` : ID du salon créé
- `service_id` : ID du service créé
- `stylist_id` : ID du stylist associé
- `booking_id` : ID de la réservation créée
- `waitlist_id` : ID inscription liste d'attente

---

## ⚠️ Points d'attention

### Sécurité
- **OWNER** uniquement pour création/modification salon
- **CLIENT** uniquement pour réservations publiques
- **Vérifications automatiques** des permissions

### Validation métier
- **Horaires** : Format JSON `{"monday": ["09:00-18:00"]}`
- **Durées** : 5-480 minutes
- **Prix** : > 0 cents
- **Réservations** : Vérification disponibilité temps réel

### Notifications simulées
- Logs console pour les emails
- Simulation complète des workflows

---

## 🎉 Prêt à tester !

Lancez les requêtes dans l'ordre des dossiers Postman pour un workflow complet. Toutes les fonctionnalités CRUD + Disponibilités + Réservations sont couvertes !
