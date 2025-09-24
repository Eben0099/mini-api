<?php

namespace App\Security\Voter;

use App\Entity\Review;
use App\Entity\Salon;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class ReviewVoter extends Voter
{
    public const EDIT = 'REVIEW_EDIT';
    public const DELETE = 'REVIEW_DELETE';
    public const VIEW = 'REVIEW_VIEW';
    public const CREATE = 'REVIEW_CREATE';
    public const MANAGE = 'REVIEW_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW, self::CREATE, self::MANAGE])
            && ($subject instanceof Review || $subject === null);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($user),
            self::CREATE => $this->canCreate($user),
            self::EDIT => $subject instanceof Review ? $this->canEdit($subject, $user) : false,
            self::DELETE => $subject instanceof Review ? $this->canDelete($subject, $user) : false,
            self::MANAGE => $this->canManage($user),
            default => false,
        };
    }

    private function canView(UserInterface $user): bool
    {
        // Tout le monde peut voir les avis
        return true;
    }

    private function canCreate(UserInterface $user): bool
    {
        // Les utilisateurs authentifiés peuvent créer des avis
        return true;
    }

    private function canEdit(Review $review, UserInterface $user): bool
    {
        // L'auteur peut éditer son avis
        if ($review->getUser() && $user->getUserIdentifier() === $review->getUser()->getUserIdentifier()) {
            return true;
        }

        return $this->canManage($user);
    }

    private function canDelete(Review $review, UserInterface $user): bool
    {
        // L'auteur peut supprimer son avis
        if ($review->getUser() && $user->getUserIdentifier() === $review->getUser()->getUserIdentifier()) {
            return true;
        }

        // Le propriétaire du salon peut supprimer les avis
        if ($review->getSalon() && $this->isSalonOwner($review->getSalon(), $user)) {
            return true;
        }

        return $this->canManage($user);
    }

    private function canManage(UserInterface $user): bool
    {
        // Gestion complète des avis
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function isSalonOwner(Salon $salon, UserInterface $user): bool
    {
        // TODO: Implémenter la logique pour vérifier si l'utilisateur est propriétaire du salon
        // $user->getSalons()->contains($salon) ou autre logique métier
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
