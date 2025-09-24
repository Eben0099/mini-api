# 📸 Guide Postman - Upload Portfolio Stylists

## 🎯 Problème résolu

L'erreur **"Aucun fichier fourni"** était due à une mauvaise gestion des fichiers form-data dans le contrôleur. Le code a été corrigé pour :

- ✅ Récupérer correctement tous les fichiers uploadés
- ✅ Valider que ce sont des images (JPG, PNG, WebP)
- ✅ Limiter à 10 fichiers maximum par upload
- ✅ Fournir des messages d'erreur détaillés avec debug

---

## 🚀 Configuration Postman

### **Étape 1 : Créer les données de test**

```http
POST http://localhost:8090/api/test/create-stylist-data
Content-Type: application/json

# Corps vide - ou {}
```

**Réponse attendue :**
```json
{
  "message": "Données de test créées avec succès",
  "data": {
    "stylist": {
      "id": 1,
      "email": "stylist@example.com",
      "password": "password123"
    }
  }
}
```

---

### **Étape 2 : Se connecter (obtenir JWT token)**

```http
POST http://localhost:8090/api/auth/login
Content-Type: application/json

{
  "email": "stylist@example.com",
  "password": "password123"
}
```

**Réponse :**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "refresh_token": "...",
  "user": {...}
}
```

**⚠️ Important :** Copiez le token pour l'étape suivante !

---

### **Étape 3 : Upload des images portfolio**

```http
POST http://localhost:8090/api/v1/stylists/1/media
Authorization: Bearer {VOTRE_TOKEN_ICI}
Content-Type: multipart/form-data
```

#### **Configuration dans Postman :**

1. **Method :** `POST`
2. **URL :** `http://localhost:8090/api/v1/stylists/1/media`
3. **Headers :**
   - `Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...`
4. **Body :**
   - Sélectionnez `form-data`
   - **KEY :** `files` (important !)
   - **TYPE :** `File`
   - **VALUE :** Sélectionnez votre fichier image (JPG, PNG, ou WebP)

#### **Pour uploader plusieurs fichiers :**

Ajoutez plusieurs lignes avec la même KEY `files` :
- `files` → `image1.jpg`
- `files` → `image2.png`
- `files` → `image3.webp`

---

## ✅ **Réponse réussie attendue**

```json
{
  "message": "2 fichier(s) uploadé(s) avec succès",
  "media": [
    {
      "id": 1,
      "originalName": "photo-profil.jpg",
      "sizeBytes": 2048576,
      "mimeType": "image/jpeg",
      "path": "/uploads/stylists/1/2024/01/uuid-123.jpg",
      "url": "/uploads/stylists/1/2024/01/uuid-123.jpg",
      "createdAt": "2024-01-15T10:30:00Z",
      "stylistId": 1,
      "thumbnails": {
        "small": "/uploads/stylists/1/2024/01/uuid-123_small.jpg",
        "medium": "/uploads/stylists/1/2024/01/uuid-123_medium.jpg"
      }
    }
  ]
}
```

---

## 🔍 **Dépannage**

### **Erreur "Aucun fichier fourni"**

**Causes possibles :**
- ❌ Mauvaise KEY dans form-data (doit être `files`)
- ❌ Fichier non sélectionné
- ❌ Type de fichier non supporté (doit être JPG, PNG, WebP)

**Solution :**
- ✅ Vérifiez que la KEY est exactement `files`
- ✅ Sélectionnez un fichier image valide
- ✅ Utilisez `form-data` (pas `raw` ou `binary`)

### **Erreur "Aucun fichier image valide fourni"**

**Debug info incluse :**
```json
{
  "error": "Aucun fichier image valide fourni. Formats acceptés: JPG, PNG, WebP",
  "debug": {
    "total_files_received": 1,
    "files_details": [
      {
        "name": "document.pdf",
        "mime_type": "application/pdf",
        "is_image": false
      }
    ]
  }
}
```

---

## 📋 **Endpoints disponibles**

### **1. Upload**
```http
POST /api/v1/stylists/{id}/media
```

### **2. Lister les médias**
```http
GET /api/v1/stylists/{id}/media
```

### **3. Supprimer un média**
```http
DELETE /api/v1/stylists/{stylistId}/media/{mediaId}
```

---

## 🛠️ **Test rapide avec cURL**

```bash
# 1. Créer les données de test
curl -X POST http://localhost:8090/api/test/create-stylist-data

# 2. Se connecter
TOKEN=$(curl -X POST http://localhost:8090/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"stylist@example.com","password":"password123"}' \
  | jq -r '.token')

# 3. Uploader une image
curl -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -F "files=@/path/to/your/image.jpg" \
  http://localhost:8090/api/v1/stylists/1/media
```

---

## 🎯 **Résumé**

**Pour que ça fonctionne dans Postman :**
1. ✅ Utilisez `form-data` (pas raw/binary)
2. ✅ KEY exactement `files` (au pluriel)
3. ✅ Sélectionnez des fichiers JPG/PNG/WebP
4. ✅ Ajoutez `Authorization: Bearer {token}`
5. ✅ URL : `/api/v1/stylists/{id}/media`

**L'erreur "Aucun fichier fourni" est maintenant résolue !** 🚀
