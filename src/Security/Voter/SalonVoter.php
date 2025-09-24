<?php

namespace App\Security\Voter;

use App\Entity\Salon;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class SalonVoter extends Voter
{
    public const EDIT = 'SALON_EDIT';
    public const DELETE = 'SALON_DELETE';
    public const VIEW = 'SALON_VIEW';
    public const MANAGE = 'SALON_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW, self::MANAGE])
            && $subject instanceof Salon;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Salon $salon */
        $salon = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($salon, $user),
            self::EDIT => $this->canEdit($salon, $user),
            self::DELETE => $this->canDelete($salon, $user),
            self::MANAGE => $this->canManage($salon, $user),
            default => false,
        };
    }

    private function canView(Salon $salon, UserInterface $user): bool
    {
        // Tout le monde peut voir les salons actifs
        if ($salon->isActive()) {
            return true;
        }

        // Les propriétaires et administrateurs peuvent voir les salons inactifs
        return $this->isOwnerOrAdmin($salon, $user);
    }

    private function canEdit(Salon $salon, UserInterface $user): bool
    {
        return $this->isOwnerOrAdmin($salon, $user);
    }

    private function canDelete(Salon $salon, UserInterface $user): bool
    {
        // Seuls les administrateurs peuvent supprimer les salons
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canManage(Salon $salon, UserInterface $user): bool
    {
        // Gestion complète (stylistes, services, disponibilités)
        return $this->isOwnerOrAdmin($salon, $user);
    }

    private function isOwnerOrAdmin(Salon $salon, UserInterface $user): bool
    {
        // Si l'utilisateur est admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Vérifier si l'utilisateur est propriétaire du salon
        if ($user instanceof User && $salon->getOwner() === $user) {
            return true;
        }

        return false;
    }
}
