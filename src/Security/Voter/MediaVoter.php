<?php

namespace App\Security\Voter;

use App\Entity\Media;
use App\Entity\Salon;
use App\Entity\Stylist;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class MediaVoter extends Voter
{
    public const EDIT = 'MEDIA_EDIT';
    public const DELETE = 'MEDIA_DELETE';
    public const VIEW = 'MEDIA_VIEW';
    public const UPLOAD = 'MEDIA_UPLOAD';
    public const MANAGE = 'MEDIA_MANAGE';

    // Permissions pour les stylists
    public const STYLIST_EDIT = 'STYLIST_EDIT';
    public const STYLIST_VIEW = 'STYLIST_VIEW';
    public const STYLIST_MANAGE = 'STYLIST_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportedAttributes = [
            self::EDIT, self::DELETE, self::VIEW, self::UPLOAD, self::MANAGE,
            self::STYLIST_EDIT, self::STYLIST_VIEW, self::STYLIST_MANAGE
        ];

        $supportedSubjects = $subject instanceof Media || $subject instanceof Stylist || $subject === null;

        return in_array($attribute, $supportedAttributes) && $supportedSubjects;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($user),
            self::UPLOAD => $this->canUpload($user),
            self::EDIT => $subject instanceof Media ? $this->canEdit($subject, $user) : false,
            self::DELETE => $subject instanceof Media ? $this->canDelete($subject, $user) : false,
            self::MANAGE => $this->canManage($user),

            // Permissions stylist
            self::STYLIST_VIEW => $subject instanceof Stylist ? $this->canViewStylist($subject, $user) : false,
            self::STYLIST_EDIT => $subject instanceof Stylist ? $this->canEditStylist($subject, $user) : false,
            self::STYLIST_MANAGE => $this->canManageStylist($user),

            default => false,
        };
    }

    private function canView(UserInterface $user): bool
    {
        // Tout le monde peut voir les médias publics
        return true;
    }

    private function canUpload(UserInterface $user): bool
    {
        // Les utilisateurs authentifiés peuvent uploader des médias
        return true;
    }

    private function canEdit(Media $media, UserInterface $user): bool
    {
        // L'utilisateur peut éditer ses propres médias
        if ($media->getUser() && $user->getUserIdentifier() === $media->getUser()->getUserIdentifier()) {
            return true;
        }

        // Le propriétaire du salon peut éditer les médias du salon
        if ($media->getSalon() && $this->isSalonOwner($media->getSalon(), $user)) {
            return true;
        }

        // Le propriétaire du stylist peut éditer les médias du stylist
        if ($media->getStylist() && $this->isStylistOwner($media->getStylist(), $user)) {
            return true;
        }

        return $this->canManage($user);
    }

    private function canDelete(Media $media, UserInterface $user): bool
    {
        // L'utilisateur peut supprimer ses propres médias
        if ($media->getUser() && $user->getUserIdentifier() === $media->getUser()->getUserIdentifier()) {
            return true;
        }

        // Le propriétaire du salon peut supprimer les médias du salon
        if ($media->getSalon() && $this->isSalonOwner($media->getSalon(), $user)) {
            return true;
        }

        // Le propriétaire du stylist peut supprimer les médias du stylist
        if ($media->getStylist() && $this->isStylistOwner($media->getStylist(), $user)) {
            return true;
        }

        return $this->canManage($user);
    }

    private function canManage(UserInterface $user): bool
    {
        // Gestion complète des médias
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canViewStylist(Stylist $stylist, UserInterface $user): bool
    {
        // Tout le monde peut voir les portfolios publics des stylists
        return true;
    }

    private function canEditStylist(Stylist $stylist, UserInterface $user): bool
    {
        // L'utilisateur peut éditer son propre stylist
        if ($this->isStylistOwner($stylist, $user)) {
            return true;
        }

        return $this->canManageStylist($user);
    }

    private function canManageStylist(UserInterface $user): bool
    {
        // Gestion complète des stylists (admins)
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function isSalonOwner(Salon $salon, UserInterface $user): bool
    {
        // TODO: Implémenter la logique pour vérifier si l'utilisateur est propriétaire du salon
        // $user->getSalons()->contains($salon) ou autre logique métier
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function isStylistOwner(Stylist $stylist, UserInterface $user): bool
    {
        // Vérifier si l'utilisateur est propriétaire du stylist
        // Le stylist est lié à un utilisateur via la relation
        return $stylist->getUser() && $stylist->getUser()->getId() === $user->getId();
    }
}
