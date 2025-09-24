# Security Voters

Ce dossier contient tous les Voters de sécurité pour contrôler l'accès aux ressources de l'API.

## Voters disponibles

### UserVoter
Gère les permissions pour les utilisateurs :
- `USER_VIEW` : Voir un profil utilisateur
- `USER_EDIT` : Modifier un profil utilisateur
- `USER_DELETE` : Supprimer un utilisateur

**Règles :**
- Les utilisateurs peuvent voir et éditer leur propre profil
- Les administrateurs ont accès à tous les profils
- Seuls les administrateurs peuvent supprimer des utilisateurs

### SalonVoter
Gère les permissions pour les salons :
- `SALON_VIEW` : Voir un salon
- `SALON_EDIT` : Modifier un salon
- `SALON_DELETE` : Supprimer un salon
- `SALON_MANAGE` : Gérer les stylistes, services et disponibilités

**Règles :**
- Tout le monde peut voir les salons actifs
- Les propriétaires et administrateurs peuvent gérer leurs salons
- Seuls les administrateurs peuvent supprimer des salons

### BookingVoter
Gère les permissions pour les réservations :
- `BOOKING_VIEW` : Voir une réservation
- `BOOKING_EDIT` : Modifier une réservation
- `BOOKING_DELETE` : Supprimer une réservation
- `BOOKING_CANCEL` : Annuler une réservation
- `BOOKING_MANAGE` : Gestion complète des réservations

**Règles :**
- Les utilisateurs peuvent gérer leurs propres réservations
- Les propriétaires de salons peuvent gérer les réservations de leur salon
- Les administrateurs ont accès à toutes les réservations

### ReviewVoter
Gère les permissions pour les avis :
- `REVIEW_VIEW` : Voir les avis
- `REVIEW_CREATE` : Créer un avis
- `REVIEW_EDIT` : Modifier un avis
- `REVIEW_DELETE` : Supprimer un avis
- `REVIEW_MANAGE` : Gestion complète des avis

**Règles :**
- Tout le monde peut voir et créer des avis
- Les auteurs peuvent modifier/supprimer leurs avis
- Les propriétaires de salons peuvent modérer les avis
- Les administrateurs ont tous les droits

### MediaVoter
Gère les permissions pour les médias :
- `MEDIA_VIEW` : Voir les médias
- `MEDIA_UPLOAD` : Uploader des médias
- `MEDIA_EDIT` : Modifier un média
- `MEDIA_DELETE` : Supprimer un média
- `MEDIA_MANAGE` : Gestion complète des médias

**Règles :**
- Tout le monde peut voir les médias publics
- Les utilisateurs peuvent gérer leurs propres médias
- Les propriétaires de salons peuvent gérer les médias de leur salon
- Les administrateurs ont tous les droits

## Utilisation dans les contrôleurs

```php
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Dans un contrôleur
#[IsGranted('USER_EDIT', subject: 'user')]
public function editUser(User $user): Response
{
    // Cette méthode ne sera accessible que si l'utilisateur a le droit d'éditer cet utilisateur
}

#[IsGranted('SALON_VIEW', subject: 'salon')]
public function viewSalon(Salon $salon): Response
{
    // Accessible si l'utilisateur peut voir ce salon
}

#[IsGranted('BOOKING_CANCEL', subject: 'booking')]
public function cancelBooking(Booking $booking): Response
{
    // Accessible si l'utilisateur peut annuler cette réservation
}
```

## Notes importantes

1. **TODO** : Les méthodes `isSalonOwner()` dans les voters doivent être implémentées selon votre logique métier
2. **TODO** : Ajouter une entité `SalonOwner` ou une relation pour lier les utilisateurs aux salons qu'ils possèdent
3. Les voters utilisent les rôles définis dans `User::getRoles()`
4. Les permissions sont vérifiées automatiquement par Symfony Security
