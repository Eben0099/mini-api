# 🔧 Fix du problème "The mailer DSN is invalid"

## ❌ Problème identifié

L'erreur `The mailer DSN is invalid (500 Internal Server Error)` indique que la configuration du mailer n'est pas correcte.

## ✅ Solution appliquée

### 1. Configuration du mailer corrigée

Le fichier `config/packages/mailer.yaml` a été configuré pour utiliser filesystem :

```yaml
framework:
    mailer:
        dsn: 'filesystem://'
```

### 2. Répertoire d'emails créé

Le dossier `var/spool/` a été créé pour stocker les emails :
```
var/spool/
```

## 🧪 Tests à effectuer

### Test 1 : Vérifier que l'API fonctionne
```bash
# Tester l'endpoint de base
curl http://localhost:8090/

# Tester l'inscription (devrait retourner une erreur de validation)
curl -X POST http://localhost:8090/auth/register \
  -H "Content-Type: application/json" \
  -d '{"invalid": "data"}'
```

### Test 2 : Inscription complète
```bash
# 1. Inscription d'un utilisateur
curl -X POST http://localhost:8090/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "firstName": "Test",
    "lastName": "User",
    "accountType": "client"
  }'

# 2. Vérifier que l'email a été créé
ls -la var/spool/

# 3. Lire le contenu de l'email
cat var/spool/*
```

## 🔍 Dépannage avancé

### Si l'erreur persiste :

#### 1. Vérifier les logs Symfony
```bash
# Dans le container Docker
docker-compose logs php

# Ou directement dans les logs
tail -f var/log/dev.log
```

#### 2. Tester la configuration manuellement
```bash
php bin/console config:dump framework mailer
```

#### 3. Vérifier les permissions
```bash
# S'assurer que le dossier var/spool/ est accessible
ls -la var/spool/
chmod 755 var/spool/
```

### Solutions alternatives :

#### Option A : Utiliser un vrai serveur SMTP (pour la production)
```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        dsn: 'smtp://user:pass@smtp.gmail.com:587'
```

#### Option B : Désactiver complètement les emails (développement)
```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        dsn: 'null://'
```

## 📧 Comment récupérer les emails

### Avec filesystem, les emails sont sauvegardés localement :

```bash
# Lister tous les emails
ls -la var/spool/

# Lire le dernier email
cat var/spool/$(ls -t var/spool/ | head -1)

# Nettoyer les emails de test
rm -rf var/spool/*
```

### Format des emails :
Chaque email contient :
- Les headers SMTP
- Le contenu HTML
- Les liens de vérification/réinitialisation

### Extraire les tokens :
```bash
# Chercher les liens dans l'email
grep "verify-email?token=" var/spool/*
grep "reset-password?token=" var/spool/*
```

## ✅ Checklist de validation

- [ ] API démarre sans erreur 500
- [ ] Inscription utilisateur fonctionne
- [ ] Email créé dans `var/spool/`
- [ ] Contenu de l'email lisible
- [ ] Liens de vérification présents
- [ ] Vérification d'email fonctionne
- [ ] Connexion JWT fonctionne

## 🎯 Prochaines étapes

Une fois que le mailer fonctionne :

1. **Tester l'inscription complète** avec Postman
2. **Vérifier la vérification d'email**
3. **Tester la réinitialisation de mot de passe**
4. **Configurer un vrai SMTP** pour la production

---

## 🚀 Test rapide

Exécutez cette commande pour tester :

```bash
# Nettoyer les anciens emails
rm -rf var/spool/*

# Tester l'inscription
curl -X POST http://localhost:8090/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "firstName": "Test",
    "lastName": "User",
    "accountType": "client"
  }'

# Vérifier l'email
echo "📧 Email créé :"
ls var/spool/
echo "🔗 Contenu :"
cat var/spool/* | grep -o 'verify-email?token=[^"]*'
```

**Si tout fonctionne, vous devriez voir le token de vérification ! 🎉**
