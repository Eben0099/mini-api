<?php

namespace App\Security\Voter;

use App\Entity\Booking;
use App\Entity\Salon;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

final class BookingVoter extends Voter
{
    public const EDIT = 'BOOKING_EDIT';
    public const DELETE = 'BOOKING_DELETE';
    public const VIEW = 'BOOKING_VIEW';
    public const CANCEL = 'BOOKING_CANCEL';
    public const MANAGE = 'BOOKING_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE, self::VIEW, self::CANCEL, self::MANAGE])
            && $subject instanceof Booking;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Booking $booking */
        $booking = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($booking, $user),
            self::EDIT => $this->canEdit($booking, $user),
            self::DELETE => $this->canDelete($booking, $user),
            self::CANCEL => $this->canCancel($booking, $user),
            self::MANAGE => $this->canManage($booking, $user),
            default => false,
        };
    }

    private function canView(Booking $booking, UserInterface $user): bool
    {
        // L'utilisateur peut voir ses propres réservations
        if ($booking->getClient() && $user->getUserIdentifier() === $booking->getClient()->getUserIdentifier()) {
            return true;
        }

        // Le propriétaire du salon peut voir les réservations
        if ($booking->getSalon() && $this->isSalonOwner($booking->getSalon(), $user)) {
            return true;
        }

        // Les administrateurs peuvent voir toutes les réservations
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canEdit(Booking $booking, UserInterface $user): bool
    {
        // L'utilisateur peut éditer ses propres réservations (sauf confirmées)
        if ($this->isOwnBooking($booking, $user) && !$this->isConfirmed($booking)) {
            return true;
        }

        return $this->canManage($booking, $user);
    }

    private function canDelete(Booking $booking, UserInterface $user): bool
    {
        return $this->canManage($booking, $user);
    }

    private function canCancel(Booking $booking, UserInterface $user): bool
    {
        // L'utilisateur peut annuler ses propres réservations
        if ($this->isOwnBooking($booking, $user)) {
            return true;
        }

        // Le propriétaire du salon peut annuler les réservations
        if ($booking->getSalon() && $this->isSalonOwner($booking->getSalon(), $user)) {
            return true;
        }

        return $this->canManage($booking, $user);
    }

    private function canManage(Booking $booking, UserInterface $user): bool
    {
        // Gestion complète des réservations (salon + admin)
        if ($booking->getSalon() && $this->isSalonOwner($booking->getSalon(), $user)) {
            return true;
        }

        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function isOwnBooking(Booking $booking, UserInterface $user): bool
    {
        return $booking->getClient() && $user->getUserIdentifier() === $booking->getClient()->getUserIdentifier();
    }

    private function isSalonOwner(Salon $salon, UserInterface $user): bool
    {
        if ($user instanceof User && $salon->getOwner() === $user) {
            return true;
        }

        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function isConfirmed(Booking $booking): bool
    {
        return $booking->getStatus() === Booking::STATUS_CONFIRMED;
    }
}
