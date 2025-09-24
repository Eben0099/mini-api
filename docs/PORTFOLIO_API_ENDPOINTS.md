# API Portfolio Stylists - Endpoints Développés

## 🎯 Vue d'ensemble

Système complet d'upload sécurisé pour les portfolios des stylists avec validation avancée, stockage organisé et contrôle d'accès strict.

## 📋 Endpoints Implémentés

### 1. Upload de médias portfolio
```
POST /api/v1/stylists/{id}/media
```

**Authentification requise :** ✅ JWT Token (propriétaire du stylist uniquement)

**Content-Type :** `multipart/form-data`

**Paramètres :**
- `files` : Fichier(s) image(s) à uploader (champ multiple supporté)
- `files[]` : Format alternatif pour multiples fichiers

**Validation stricte :**
- ✅ Images uniquement (JPG, PNG, WebP)
- ✅ Taille max : 5MB par fichier
- ✅ Vérification magic bytes (sécurité avancée)
- ✅ Limite portfolio : 20 images max par stylist
- ✅ Rate limiting : 10 uploads/heure par stylist

**Stockage organisé :**
```
/uploads/stylists/{stylist_id}/{year}/{month}/uuid.ext
```

**Thumbnails générés automatiquement :**
- `small` : 150x150px
- `medium` : 300x300px

**Réponse succès (201) :**
```json
{
  "message": "2 fichier(s) uploadé(s) avec succès",
  "media": [
    {
      "id": 123,
      "originalName": "photo-portrait.jpg",
      "sizeBytes": 2048576,
      "mimeType": "image/jpeg",
      "path": "/uploads/stylists/456/2024/01/uuid-123.jpg",
      "url": "/uploads/stylists/456/2024/01/uuid-123.jpg",
      "createdAt": "2024-01-15T10:30:00Z",
      "stylistId": 456,
      "thumbnails": {
        "small": "/uploads/stylists/456/2024/01/uuid-123_small.jpg",
        "medium": "/uploads/stylists/456/2024/01/uuid-123_medium.jpg"
      }
    }
  ]
}
```

---

### 2. Lister les médias du portfolio
```
GET /api/v1/stylists/{id}/media
```

**Authentification requise :** ✅ JWT Token (public pour consultation)

**Réponse succès (200) :**
```json
{
  "stylist_id": 456,
  "total": 5,
  "media": [
    {
      "id": 123,
      "originalName": "photo-1.jpg",
      "sizeBytes": 1048576,
      "mimeType": "image/jpeg",
      "path": "/uploads/stylists/456/2024/01/uuid-123.jpg",
      "url": "/uploads/stylists/456/2024/01/uuid-123.jpg",
      "createdAt": "2024-01-15T10:30:00Z",
      "stylistId": 456,
      "thumbnails": {
        "small": "/uploads/stylists/456/2024/01/uuid-123_small.jpg",
        "medium": "/uploads/stylists/456/2024/01/uuid-123_medium.jpg"
      }
    }
  ]
}
```

---

### 3. Supprimer un média portfolio
```
DELETE /api/v1/stylists/{stylistId}/media/{mediaId}
```

**Authentification requise :** ✅ JWT Token (propriétaire du stylist uniquement)

**Réponse succès (200) :**
```json
{
  "message": "Média supprimé avec succès"
}
```

---

## 🔒 Sécurité Implémentée

### Validation des fichiers
- **Type MIME** : Vérification stricte (images uniquement)
- **Magic bytes** : Validation des signatures binaires
- **Taille** : Maximum 5MB par fichier
- **Contenu EXIF** : Sanitization automatique des métadonnées

### Contrôle d'accès
- **Propriétaire uniquement** : Un stylist ne peut gérer que ses propres médias
- **Voters Symfony** : Utilisation du système de sécurité avancé
- **Permissions granulaires** :
  - `STYLIST_EDIT` : Modification/suppression de ses médias
  - `STYLIST_VIEW` : Consultation des portfolios publics

### Rate Limiting
- **Limite** : 10 uploads par heure par stylist
- **Implémentation** : Cache Symfony (FilesystemAdapter)
- **Fenêtre** : Réinitialisation automatique chaque heure

### Stockage sécurisé
- **Noms UUID** : Évite les collisions et l'énumération
- **Organisation temporelle** : `/stylists/{id}/{year}/{month}/`
- **Permissions fichiers** : 0666 (lecture/écriture sécurisée)

---

## 🛠️ Architecture Technique

### Services développés
- **`FileUploadService`** : Upload générique avec validation avancée
- **`PortfolioUploadService`** : Logique métier spécialisée portfolios

### Sécurité
- **`MediaVoter`** : Contrôle d'accès basé sur les rôles et propriétaires
- **Validation EXIF** : Suppression automatique des métadonnées sensibles
- **Rollback automatique** : En cas d'erreur pendant l'upload multiple

### Optimisation
- **Thumbnails automatiques** : Génération à la volée (150px, 300px)
- **Cache rate limiting** : Performance et protection contre les abus
- **Compression intelligente** : Qualité adaptée selon la taille cible

---

## 📊 Limites et contraintes

| Aspect | Limite | Justification |
|--------|--------|---------------|
| **Taille fichier** | 5MB | Performance et sécurité |
| **Types autorisés** | JPG, PNG, WebP | Standards web optimaux |
| **Portfolio max** | 20 images | Équilibre UX/performance |
| **Rate limit** | 10/h | Protection contre les abus |
| **Thumbnails** | 150px, 300px | Tailles standards responsive |

---

## 🚀 Utilisation

### Upload simple
```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -F "files=@photo.jpg" \
  http://localhost:8090/api/v1/stylists/123/media
```

### Upload multiple
```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -F "files=@photo1.jpg" \
  -F "files=@photo2.jpg" \
  http://localhost:8090/api/v1/stylists/123/media
```

### Consultation portfolio
```bash
curl -H "Authorization: Bearer {token}" \
  http://localhost:8090/api/v1/stylists/123/media
```

---

## ✅ Tests et validation

Le système a été conçu pour être :
- **Sécurisé** : Validation multi-niveaux et contrôles d'accès stricts
- **Robuste** : Gestion d'erreur complète et rollback automatique
- **Performant** : Thumbnails automatiques et cache intelligent
- **Évolutif** : Architecture modulaire et extensible

Tous les endpoints sont maintenant opérationnels et prêts pour l'intégration frontend ! 🎉
