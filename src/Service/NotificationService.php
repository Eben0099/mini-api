<?php

namespace App\Service;

use App\Entity\Salon;
use App\Entity\Stylist;
use App\Entity\WaitlistEntry;
use App\Repository\WaitlistEntryRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Twig\Environment;

class NotificationService
{
    public function __construct(
        private WaitlistEntryRepository $waitlistEntryRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private Environment $twig,
    ) {}

    /**
     * Notifie les clients en liste d'attente lorsqu'un créneau se libère
     */
    public function notifyWaitlistOnSlotAvailable(
        Salon $salon,
        Stylist $stylist,
        \DateTimeImmutable $slotStart,
        \DateTimeImmutable $slotEnd
    ): void {
        // Trouver les entrées en liste d'attente pour ce salon/service dans la période souhaitée
        $waitlistEntries = $this->waitlistEntryRepository->findBySalonAndDateRange(
            $salon->getId(),
            $slotStart,
            $slotEnd
        );

        foreach ($waitlistEntries as $entry) {
            // Vérifier si le créneau correspond à la période souhaitée du client
            if ($this->slotMatchesWaitlistEntry($slotStart, $slotEnd, $entry)) {
                $this->sendWaitlistNotification($entry, $stylist, $slotStart, $slotEnd);
            }
        }
    }

    /**
     * Vérifie si un créneau correspond à la période souhaitée d'une entrée en liste d'attente
     */
    private function slotMatchesWaitlistEntry(
        \DateTimeImmutable $slotStart,
        \DateTimeImmutable $slotEnd,
        WaitlistEntry $entry
    ): bool {
        $desiredStart = $entry->getDesiredStartRangeStart();
        $desiredEnd = $entry->getDesiredStartRangeEnd();

        // Le créneau doit commencer dans la période souhaitée
        return $slotStart >= $desiredStart && $slotStart <= $desiredEnd;
    }

    /**
     * Envoie une notification simulée à un client en liste d'attente
     */
    private function sendWaitlistNotification(
        WaitlistEntry $entry,
        Stylist $stylist,
        \DateTimeImmutable $slotStart,
        \DateTimeImmutable $slotEnd
    ): void {
        $client = $entry->getClient();
        $salon = $entry->getSalon();
        $service = $entry->getService();

        $subject = "Créneau disponible - {$salon->getName()}";
        $body = sprintf(
            "Bonjour %s %s,\n\n" .
            "Un créneau s'est libéré pour le service '%s' au salon '%s'.\n\n" .
            "Détails :\n" .
            "- Coiffeur : %s %s\n" .
            "- Date et heure : %s\n" .
            "- Durée : %d minutes\n\n" .
            "Vous pouvez réserver ce créneau directement via l'application.\n\n" .
            "Cordialement,\n" .
            "L'équipe %s",
            $client->getFirstName(),
            $client->getLastName(),
            $service->getName(),
            $salon->getName(),
            $stylist->getUser()->getFirstName(),
            $stylist->getUser()->getLastName(),
            $slotStart->format('d/m/Y H:i'),
            $entry->getDurationMinutes(),
            $salon->getName()
        );

        // Simulation : log + email console
        $this->logger->info('Notification liste d\'attente', [
            'client_id' => $client->getId(),
            'client_email' => $client->getEmail(),
            'salon' => $salon->getName(),
            'stylist' => $stylist->getUser()->getFirstName() . ' ' . $stylist->getUser()->getLastName(),
            'slot_start' => $slotStart->format('Y-m-d H:i:s'),
            'slot_end' => $slotEnd->format('Y-m-d H:i:s'),
            'service' => $service->getName(),
        ]);

        // Email simulé (console output)
        $email = (new Email())
            ->from('noreply@salon-api.com')
            ->to($client->getEmail())
            ->subject($subject)
            ->text($body);

        // Au lieu d'envoyer réellement, on log le contenu
        $this->logger->info('Email simulé envoyé', [
            'to' => $client->getEmail(),
            'subject' => $subject,
            'body' => $body,
        ]);

        // Affichage console pour debug
        echo "\n=== EMAIL SIMULÉ ===\n";
        echo "À: {$client->getEmail()}\n";
        echo "Sujet: {$subject}\n";
        echo "Corps:\n{$body}\n";
        echo "===================\n\n";
    }

    /**
     * Notifie un client de confirmation de réservation
     */
    public function notifyBookingConfirmed(\App\Entity\Booking $booking): void
    {
        $client = $booking->getClient();
        $salon = $booking->getSalon();
        $stylist = $booking->getStylist();
        $service = $booking->getService();

        try {
            // Email au client
            $clientEmail = (new TemplatedEmail())
                ->from('noreply@salon-api.com')
                ->to($client->getEmail())
                ->subject("Confirmation de réservation - {$salon->getName()}")
                ->htmlTemplate('emails/booking_confirmed_client.html.twig')
                ->context([
                    'client' => $client,
                    'salon' => $salon,
                    'stylist' => $stylist,
                    'service' => $service,
                    'booking' => $booking,
                    'startAt' => $booking->getStartAt(),
                    'endAt' => $booking->getEndAt(),
                ]);

            $this->mailer->send($clientEmail);

            // Email au styliste
            $stylistEmail = (new TemplatedEmail())
                ->from('noreply@salon-api.com')
                ->to($stylist->getUser()->getEmail())
                ->subject("Nouvelle réservation - {$salon->getName()}")
                ->htmlTemplate('emails/booking_confirmed_stylist.html.twig')
                ->context([
                    'client' => $client,
                    'salon' => $salon,
                    'stylist' => $stylist,
                    'service' => $service,
                    'booking' => $booking,
                    'startAt' => $booking->getStartAt(),
                    'endAt' => $booking->getEndAt(),
                ]);

            $this->mailer->send($stylistEmail);

            $this->logger->info('Emails de confirmation de réservation envoyés', [
                'booking_id' => $booking->getId(),
                'client_email' => $client->getEmail(),
                'stylist_email' => $stylist->getUser()->getEmail(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi des emails de confirmation', [
                'booking_id' => $booking->getId(),
                'error' => $e->getMessage(),
            ]);

            // Fallback : afficher dans la console
            echo "\n=== ERREUR ENVOI EMAIL ===\n";
            echo "Erreur : {$e->getMessage()}\n";
            echo "Emails non envoyés pour la réservation {$booking->getId()}\n";
            echo "=========================\n\n";
        }
    }

    /**
     * Notifie un client et styliste d'annulation de réservation
     */
    public function notifyBookingCancelled(\App\Entity\Booking $booking, string $reason = null): void
    {
        $client = $booking->getClient();
        $salon = $booking->getSalon();
        $stylist = $booking->getStylist();
        $service = $booking->getService();

        try {
            // Email au client
            $clientEmail = (new TemplatedEmail())
                ->from('noreply@salon-api.com')
                ->to($client->getEmail())
                ->subject("Annulation de réservation - {$salon->getName()}")
                ->htmlTemplate('emails/booking_cancelled_client.html.twig')
                ->context([
                    'client' => $client,
                    'salon' => $salon,
                    'stylist' => $stylist,
                    'service' => $service,
                    'booking' => $booking,
                    'reason' => $reason,
                    'startAt' => $booking->getStartAt(),
                    'endAt' => $booking->getEndAt(),
                ]);

            $this->mailer->send($clientEmail);

            // Email au styliste
            $stylistEmail = (new TemplatedEmail())
                ->from('noreply@salon-api.com')
                ->to($stylist->getUser()->getEmail())
                ->subject("Annulation de réservation - {$salon->getName()}")
                ->htmlTemplate('emails/booking_cancelled_stylist.html.twig')
                ->context([
                    'client' => $client,
                    'salon' => $salon,
                    'stylist' => $stylist,
                    'service' => $service,
                    'booking' => $booking,
                    'reason' => $reason,
                    'startAt' => $booking->getStartAt(),
                    'endAt' => $booking->getEndAt(),
                ]);

            $this->mailer->send($stylistEmail);

            $this->logger->info('Emails d\'annulation de réservation envoyés', [
                'booking_id' => $booking->getId(),
                'client_email' => $client->getEmail(),
                'stylist_email' => $stylist->getUser()->getEmail(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi des emails d\'annulation', [
                'booking_id' => $booking->getId(),
                'error' => $e->getMessage(),
            ]);

            // Fallback : afficher dans la console
            echo "\n=== ERREUR ENVOI EMAIL ===\n";
            echo "Erreur : {$e->getMessage()}\n";
            echo "Emails non envoyés pour l'annulation de la réservation {$booking->getId()}\n";
            echo "=========================\n\n";
        }
    }

    /**
     * Notifie un client qui passe de la liste d'attente à une réservation confirmée
     */
    public function notifyWaitlistToBooking(\App\Entity\Booking $booking, \App\Entity\WaitlistEntry $waitlistEntry): void
    {
        $client = $booking->getClient();
        $salon = $booking->getSalon();
        $stylist = $booking->getStylist();
        $service = $booking->getService();

        // Log détaillé dans un fichier pour debug
        $logFile = __DIR__ . '/../../var/log/waitlist_emails.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logMessage = sprintf(
            "[%s] === NOTIFY WAITLIST TO BOOKING ===\n" .
            "Client: %s (%s)\n" .
            "Stylist: %s (%s)\n" .
            "Salon: %s\n" .
            "Service: %s\n" .
            "Booking ID: %d\n" .
            "Waitlist ID: %d\n" .
            "StartAt: %s\n\n",
            date('Y-m-d H:i:s'),
            $client->getEmail(),
            $client->getFirstName() . ' ' . $client->getLastName(),
            $stylist->getUser()->getEmail(),
            $stylist->getUser()->getFirstName() . ' ' . $stylist->getUser()->getLastName(),
            $salon->getName(),
            $service->getName(),
            $booking->getId(),
            $waitlistEntry->getId(),
            $booking->getStartAt()->format('Y-m-d H:i:s')
        );

        file_put_contents($logFile, $logMessage, FILE_APPEND);

        try {
            file_put_contents($logFile, "[{$this->getCurrentTime()}] Préparation email client...\n", FILE_APPEND);

            // Email au client
            $clientEmail = (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                ->from('noreply@salonapp.com')
                ->to($client->getEmail())
                ->subject('🎉 Bonne nouvelle ! Votre créneau s\'est libéré')
                ->htmlTemplate('emails/waitlist_to_booking_client.html.twig')
                ->context([
                    'client' => $client,
                    'salon' => $salon,
                    'stylist' => $stylist,
                    'service' => $service,
                    'booking' => $booking,
                    'waitlistEntry' => $waitlistEntry,
                ]);

            file_put_contents($logFile, "[{$this->getCurrentTime()}] Envoi email client à {$client->getEmail()}...\n", FILE_APPEND);
            $this->mailer->send($clientEmail);
            file_put_contents($logFile, "[{$this->getCurrentTime()}] ✅ Email client envoyé avec succès\n", FILE_APPEND);

            file_put_contents($logFile, "[{$this->getCurrentTime()}] Préparation email styliste...\n", FILE_APPEND);

            // Email au styliste
            $stylistEmail = (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                ->from('noreply@salonapp.com')
                ->to($stylist->getUser()->getEmail())
                ->subject('Nouvelle réservation depuis la liste d\'attente')
                ->htmlTemplate('emails/waitlist_to_booking_stylist.html.twig')
                ->context([
                    'client' => $client,
                    'salon' => $salon,
                    'stylist' => $stylist,
                    'service' => $service,
                    'booking' => $booking,
                    'waitlistEntry' => $waitlistEntry,
                ]);

            file_put_contents($logFile, "[{$this->getCurrentTime()}] Envoi email styliste à {$stylist->getUser()->getEmail()}...\n", FILE_APPEND);
            $this->mailer->send($stylistEmail);
            file_put_contents($logFile, "[{$this->getCurrentTime()}] ✅ Email styliste envoyé avec succès\n", FILE_APPEND);

            $this->logger->info('Emails de passage liste d\'attente → réservation envoyés', [
                'booking_id' => $booking->getId(),
                'waitlist_entry_id' => $waitlistEntry->getId(),
                'client_email' => $client->getEmail(),
                'stylist_email' => $stylist->getUser()->getEmail(),
            ]);

        } catch (\Exception $e) {
            $errorMessage = "[{$this->getCurrentTime()}] ❌ ERREUR lors de l'envoi d'email : " . $e->getMessage() . "\n";
            file_put_contents($logFile, $errorMessage, FILE_APPEND);

            $this->logger->error('Erreur lors de l\'envoi des emails liste d\'attente → réservation', [
                'booking_id' => $booking->getId(),
                'waitlist_entry_id' => $waitlistEntry->getId(),
                'error' => $e->getMessage(),
            ]);

            // Fallback : afficher dans la console
            echo "\n=== ERREUR ENVOI EMAIL ===\n";
            echo "Erreur : {$e->getMessage()}\n";
            echo "Emails non envoyés pour le passage liste d'attente → réservation {$booking->getId()}\n";
            echo "Vérifiez le fichier de log : var/log/waitlist_emails.log\n";
            echo "=========================\n\n";
        }
    }

    private function getCurrentTime(): string
    {
        return date('Y-m-d H:i:s');
    }
}
