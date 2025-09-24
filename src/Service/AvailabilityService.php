<?php

namespace App\Service;

use App\Entity\AvailabilityException;
use App\Entity\Booking;
use App\Entity\Salon;
use App\Entity\Service;
use App\Entity\Stylist;
use App\Repository\AvailabilityExceptionRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;

class AvailabilityService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingRepository $bookingRepository,
        private AvailabilityExceptionRepository $availabilityExceptionRepository,
    ) {}

    /**
     * Calcule les créneaux disponibles pour un service dans un salon à une date donnée
     *
     * @param Salon $salon
     * @param Service $service
     * @param \DateTimeImmutable $date
     * @param int $durationMinutes
     * @param Stylist|null $specificStylist Si null, retourne tous les stylists disponibles
     * @return array Array where key is stylist ID and value contains stylist and available slots
     */
    public function getAvailableSlots(
        Salon $salon,
        Service $service,
        \DateTimeImmutable $date,
        int $durationMinutes,
        ?Stylist $specificStylist = null
    ): array {
        // Vérifier que la date n'est pas dans le passé
        $today = new \DateTimeImmutable('today');
        if ($date < $today) {
            return []; // Pas de créneaux pour les dates passées
        }

        $stylists = $specificStylist ? [$specificStylist] : $salon->getStylists()->toArray();

        $result = [];

        foreach ($stylists as $stylist) {
            // Vérifier si le stylist a la compétence pour ce service
            if (!$stylist->getSkills()->contains($service)) {
                continue;
            }

            $availableSlots = $this->calculateAvailableSlotsForStylist(
                $salon,
                $stylist,
                $service,
                $date,
                $durationMinutes
            );

            if (!empty($availableSlots)) {
                $result[$stylist->getId()] = [
                    'stylist' => $stylist,
                    'slots' => $availableSlots,
                ];
            }
        }

        return $result;
    }

    /**
     * Calcule les créneaux disponibles pour un stylist spécifique
     */
    private function calculateAvailableSlotsForStylist(
        Salon $salon,
        Stylist $stylist,
        Service $service,
        \DateTimeImmutable $date,
        int $durationMinutes
    ): array {
        // 1. Vérifier si le salon est ouvert ce jour-là
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, wednesday, etc.
        $salonHours = $salon->getOpenHours();

        if (!isset($salonHours[$dayOfWeek]) || empty($salonHours[$dayOfWeek])) {
            return []; // Salon fermé ce jour
        }

        // 2. Vérifier les exceptions (fermetures/congés)
        if ($this->hasAvailabilityException($salon, $stylist, $date)) {
            return []; // Exception trouvée
        }

        // 3. Déterminer les horaires applicables (stylist spécifiques ou salon)
        $stylistHours = $stylist->getOpenHours();
        if ($stylistHours && isset($stylistHours[$dayOfWeek]) && !empty($stylistHours[$dayOfWeek])) {
            $applicableHours = $stylistHours[$dayOfWeek];
        } else {
            $applicableHours = $salonHours[$dayOfWeek];
        }

        // 4. Générer tous les créneaux potentiels de 15 minutes
        $potentialSlots = $this->generatePotentialSlots($date, $applicableHours, $durationMinutes);

        // 5. Filtrer les créneaux qui ne sont pas disponibles (réservations existantes)
        $availableSlots = [];
        foreach ($potentialSlots as $slotStart) {
            $slotEnd = $slotStart->add(new \DateInterval('PT' . $durationMinutes . 'M'));

            if ($this->isSlotAvailable($stylist, $slotStart, $slotEnd)) {
                $availableSlots[] = $slotStart->format('H:i');
            }
        }

        return $availableSlots;
    }

    /**
     * Vérifie s'il y a une exception d'ouverture pour cette date
     */
    private function hasAvailabilityException(Salon $salon, Stylist $stylist, \DateTimeImmutable $date): bool
    {
        $exceptions = $this->availabilityExceptionRepository->findExceptionsForDate(
            $date,
            $salon->getId(),
            $stylist->getId()
        );

        foreach ($exceptions as $exception) {
            if ($exception->isClosed()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Génère tous les créneaux potentiels de 15 minutes dans les horaires d'ouverture
     */
    private function generatePotentialSlots(\DateTimeImmutable $date, array $hours, int $durationMinutes): array
    {
        $slots = [];

        foreach ($hours as $timeRange) {
            if (!is_string($timeRange) || !str_contains($timeRange, '-')) {
                continue; // Skip invalid time ranges
            }

            [$startTime, $endTime] = explode('-', $timeRange, 2);

            try {
                $startDateTime = \DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $date->format('Y-m-d') . ' ' . trim($startTime)
                );

                $endDateTime = \DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $date->format('Y-m-d') . ' ' . trim($endTime)
                );

                if (!$startDateTime || !$endDateTime) {
                    continue; // Skip invalid time formats
                }

                // Générer des créneaux de 15 minutes, en s'assurant qu'il y a assez de temps pour la prestation
                $currentSlot = $startDateTime;
                $now = new \DateTimeImmutable(); // Date/heure actuelle

                while ($currentSlot <= $endDateTime->sub(new \DateInterval('PT' . $durationMinutes . 'M'))) {
                    // Exclure les créneaux passés
                    if ($currentSlot > $now) {
                        $slots[] = $currentSlot;
                    }
                    $currentSlot = $currentSlot->add(new \DateInterval('PT15M')); // Créneaux de 15 minutes
                }
            } catch (\Exception $e) {
                continue; // Skip invalid time ranges
            }
        }

        return $slots;
    }

    /**
     * Vérifie si un créneau spécifique est disponible (pas de chevauchement avec réservations existantes)
     */
    private function isSlotAvailable(Stylist $stylist, \DateTimeImmutable $startAt, \DateTimeImmutable $endAt): bool
    {
        $overlappingBookings = $this->bookingRepository->findOverlappingBookings(
            $stylist,
            $startAt,
            $endAt
        );

        return empty($overlappingBookings);
    }

    /**
     * Vérifie si une réservation peut être créée (logique métier)
     */
    public function canCreateBooking(
        Salon $salon,
        Stylist $stylist,
        Service $service,
        \DateTimeImmutable $startAt
    ): bool {
        $endAt = $startAt->add(new \DateInterval('PT' . $service->getDurationMinutes() . 'M'));

        // Vérifier que la date n'est pas dans le passé
        $now = new \DateTimeImmutable();
        if ($startAt <= $now) {
            error_log("❌ FAIL: Date dans le passé");
            return false;
        }
        error_log("✅ PASS: Date dans le futur");

        // Debug logging
        error_log("=== canCreateBooking DEBUG ===");
        error_log("Salon: {$salon->getName()} (ID: {$salon->getId()})");
        error_log("Stylist: {$stylist->getUser()->getFirstName()} {$stylist->getUser()->getLastName()} (ID: {$stylist->getId()})");
        error_log("Service: {$service->getName()} (ID: {$service->getId()}, Duration: {$service->getDurationMinutes()} min)");
        error_log("Créneau: {$startAt->format('Y-m-d H:i')} - {$endAt->format('Y-m-d H:i')}");

        // Vérifier que le stylist travaille dans ce salon
        if ($stylist->getSalon() !== $salon) {
            error_log("❌ FAIL: Stylist ne travaille pas dans ce salon");
            return false;
        }
        error_log("✅ PASS: Stylist travaille dans le salon");

        // Vérifier que le stylist a cette compétence
        if (!$stylist->getSkills()->contains($service)) {
            error_log("❌ FAIL: Stylist n'a pas la compétence pour ce service");
            return false;
        }
        error_log("✅ PASS: Stylist a la compétence");

        // Vérifier les horaires d'ouverture
        $dayOfWeek = strtolower($startAt->format('l'));
        $salonHours = $salon->getOpenHours();

        $stylistHours = $stylist->getOpenHours();
        if ($stylistHours && isset($stylistHours[$dayOfWeek]) && !empty($stylistHours[$dayOfWeek])) {
            $applicableHours = $stylistHours[$dayOfWeek];
            error_log("ℹ️  Utilise horaires stylist: " . json_encode($applicableHours));
        } else {
            $applicableHours = $salonHours[$dayOfWeek] ?? [];
            error_log("ℹ️  Utilise horaires salon: " . json_encode($applicableHours));
        }

        if (!$this->isTimeInOpeningHours($startAt, $endAt, $applicableHours)) {
            error_log("❌ FAIL: Créneau pas dans les horaires d'ouverture");
            return false;
        }
        error_log("✅ PASS: Créneau dans les horaires d'ouverture");

        // Vérifier les exceptions
        if ($this->hasAvailabilityException($salon, $stylist, $startAt)) {
            error_log("❌ FAIL: Exception d'ouverture trouvée");
            return false;
        }
        error_log("✅ PASS: Pas d'exception d'ouverture");

        // Vérifier les conflits de réservation
        if (!$this->isSlotAvailable($stylist, $startAt, $endAt)) {
            error_log("❌ FAIL: Conflit avec réservation existante");
            return false;
        }
        error_log("✅ PASS: Pas de conflit de réservation");

        error_log("🎉 SUCCESS: Toutes les validations passent");
        return true;
    }

    /**
     * Vérifie si une heure est dans les horaires d'ouverture
     */
    private function isTimeInOpeningHours(\DateTimeImmutable $startAt, \DateTimeImmutable $endAt, array $hours): bool
    {
        foreach ($hours as $timeRange) {
            if (!is_string($timeRange) || !str_contains($timeRange, '-')) {
                continue; // Skip invalid time ranges
            }

            [$startTime, $endTime] = explode('-', $timeRange, 2);

            try {
                $rangeStart = \DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $startAt->format('Y-m-d') . ' ' . trim($startTime)
                );

                $rangeEnd = \DateTimeImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $startAt->format('Y-m-d') . ' ' . trim($endTime)
                );

                if ($rangeStart && $rangeEnd && $startAt >= $rangeStart && $endAt <= $rangeEnd) {
                    return true;
                }
            } catch (\Exception $e) {
                continue; // Skip invalid time ranges
            }
        }

        return false;
    }
}
