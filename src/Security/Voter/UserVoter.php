<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserVoter extends Voter
{
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';
    public const VIEW = 'USER_VIEW';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var User $userSubject */
        $userSubject = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($user, $userSubject),
            self::EDIT => $this->canEdit($user, $userSubject),
            self::DELETE => $this->canDelete($user, $userSubject),
            default => false,
        };
    }

    private function canView(UserInterface $user, User $subject): bool
    {
        // Les utilisateurs peuvent voir leur propre profil
        if ($user->getUserIdentifier() === $subject->getUserIdentifier()) {
            return true;
        }

        // Les administrateurs peuvent voir tous les profils
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canEdit(UserInterface $user, User $subject): bool
    {
        // Les utilisateurs peuvent éditer leur propre profil
        if ($user->getUserIdentifier() === $subject->getUserIdentifier()) {
            return true;
        }

        // Les administrateurs peuvent éditer tous les profils
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(UserInterface $user, User $subject): bool
    {
        // Seuls les administrateurs peuvent supprimer des utilisateurs
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
