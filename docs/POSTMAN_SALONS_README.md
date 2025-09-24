# 🏢 Collection Postman - Gestion Salons

Collection Postman complète pour tester l'API de gestion des salons de coiffure.

## 📋 Prérequis

1. **API démarrée** : Assurez-vous que l'API Symfony fonctionne sur `http://localhost:8090`
2. **Base de données** : Les migrations doivent être exécutées
3. **Postman** : Importez la collection et l'environnement

## 📥 Import dans Postman

### Collection
- Fichier : `Mini_API_Salons_Postman_Collection.json`

### Environnement
- Fichier : `Mini_API_Postman_Environment.postman_environment.json`

## 🔧 Variables d'environnement

| Variable | Description | Valeur par défaut |
|----------|-------------|-------------------|
| `base_url` | URL de base de l'API | `http://localhost:8090` |
| `auth_token` | Token JWT du propriétaire | *(généré automatiquement)* |
| `salon_id` | ID du salon créé | *(généré automatiquement)* |
| `service_id` | ID du service créé | *(généré automatiquement)* |
| `stylist_id` | ID du stylist associé | *(généré automatiquement)* |
| `owner_email` | Email du propriétaire | `owner@example.com` |
| `owner_password` | Mot de passe propriétaire | `password123` |

## 🚀 Workflow de test complet

### 1. Authentification (Prérequis)
```bash
# Exécuter dans l'ordre :
1. "Créer compte propriétaire salon"
2. "Créer compte client"
3. "Connexion propriétaire" (stocke le token)
4. "Connexion client" (stocke le token client)
```

### 2. Gestion du Salon
```bash
# En tant que propriétaire :
1. "🏗️ Créer salon (OWNER seulement)" (stocke salon_id)
2. "👁️ Détail salon (public)" (vérification)
3. "✏️ Modifier salon (OWNER/ADMIN)" (modification)
```

### 3. Gestion des Services
```bash
# En tant que propriétaire :
1. "➕ Ajouter service au salon" (stocke service_id)
2. "➕ Ajouter service homme"
3. "➕ Ajouter coloration"
4. "✏️ Modifier service"
5. "🚫 Désactiver service"
6. "🗑️ Supprimer service"
```

### 4. Gestion des Stylists
```bash
# En tant que propriétaire :
1. "➕ Associer stylist au salon" (stocke stylist_id)
2. "➕ Associer stylist 2"
3. "🗑️ Retirer stylist du salon"
```

### 5. Tests Client (Vue publique)
```bash
# En tant que client (pas d'authentification) :
1. "📋 Liste salons (public)"
2. "🔍 Filtrer salons par ville"
3. "🔍 Rechercher salons par nom"
4. "👥 Vérifier salon depuis client (vue publique)"
```

## 📚 Endpoints testés

### Salons
- `GET /api/v1/salons` - Liste publique avec filtres
- `GET /api/v1/salons/{id}` - Détail salon public
- `POST /api/v1/salons` - Création (OWNER)
- `PATCH /api/v1/salons/{id}` - Modification (OWNER/ADMIN)
- `DELETE /api/v1/salons/{id}` - Suppression (OWNER/ADMIN)

### Services
- `POST /api/v1/salons/{id}/services` - Ajouter service
- `PATCH /api/v1/services/{id}` - Modifier service
- `DELETE /api/v1/services/{id}` - Supprimer service

### Stylists
- `POST /api/v1/salons/{id}/stylists` - Associer stylist
- `DELETE /api/v1/stylists/{id}` - Retirer stylist

## 🔒 Autorisations

### Public (pas d'authentification)
- Consultation des salons
- Recherche et filtrage

### Authentifié OWNER
- Création de salon
- Gestion de ses propres salons
- Ajout/modification services
- Association/retrait stylists

### Authentifié ADMIN
- Toutes les permissions OWNER
- Gestion de tous les salons
- Suppression de salons

## 📝 Données de test

### Salon exemple
```json
{
  "name": "Salon Marie Dubois",
  "address": "15 Rue de la Paix, 75002 Paris",
  "city": "Paris",
  "lat": 48.8566,
  "lng": 2.3522,
  "openHours": {
    "monday": "09:00-19:00",
    "tuesday": "09:00-19:00",
    "wednesday": "09:00-19:00",
    "thursday": "09:00-19:00",
    "friday": "09:00-19:00",
    "saturday": "08:00-18:00",
    "sunday": "Fermé"
  }
}
```

### Service exemple
```json
{
  "name": "Coupe femme",
  "description": "Coupe de cheveux pour femme avec shampoing",
  "durationMinutes": 60,
  "priceCents": 4500,
  "isActive": true
}
```

### Stylist exemple
```json
{
  "userId": 1,
  "languages": ["fr", "en", "es"],
  "skillIds": [1, 2]
}
```

## 🐛 Dépannage

### Erreur 401 Unauthorized
- Vérifiez que vous êtes connecté et que le token est stocké
- Le token expire après un certain temps

### Erreur 403 Forbidden
- Vérifiez que vous utilisez le bon compte (OWNER pour son salon)
- Certains endpoints nécessitent d'être propriétaire du salon

### Erreur 404 Not Found
- Vérifiez que les IDs stockés sont corrects
- Les variables `salon_id`, `service_id`, `stylist_id` doivent être définies

### Erreur de validation
- Vérifiez le format des données JSON
- Respectez les contraintes (durée 5-480 min, prix > 0, etc.)

## 📊 Tests automatisés

La collection inclut des scripts de test qui :
- Stockent automatiquement les tokens après connexion
- Sauvegardent les IDs créés (salon, service, stylist)
- Vérifient les codes de réponse
- Affichent des logs détaillés

## 🎯 Scénarios métier couverts

1. **Propriétaire crée son salon** et le configure
2. **Client recherche des salons** par ville ou nom
3. **Client consulte les détails** d'un salon avec services et stylists
4. **Gestion complète des prestations** par le propriétaire
5. **Association des coiffeurs** avec leurs compétences
6. **Filtrage et recherche** pour les clients

---

**Collection créée pour l'API Mini - Gestion Salons** 🏢💇‍♀️
