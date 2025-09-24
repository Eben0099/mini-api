<?php

namespace App\Controller\Api;

use App\DTO\Request\BookingCreateDto;
use App\DTO\Response\BookingResponseDto;
use App\Entity\Booking;
use App\Entity\Salon;
use App\Entity\Service;
use App\Entity\Stylist;
use App\Repository\BookingRepository;
use App\Service\AvailabilityService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[OA\Tag(name: "Bookings", description: "Gestion des réservations de salons de coiffure")]
#[Route('/api/v1/bookings')]
class BookingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookingRepository $bookingRepository,
        private AvailabilityService $availabilityService,
        private NotificationService $notificationService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'api_bookings_create', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT')]
    #[OA\Post(
        path: "/api/v1/bookings",
        summary: "Créer une nouvelle réservation",
        description: "Permet à un client de créer une nouvelle réservation dans un salon",
        tags: ["Bookings"],
        security: [["Bearer" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de création de réservation",
            content: new OA\JsonContent(ref: "#/components/schemas/BookingCreateDto")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Réservation créée avec succès",
                content: new OA\JsonContent(ref: "#/components/schemas/BookingResponseDto")
            ),
            new OA\Response(
                response: 400,
                description: "Données invalides",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "errors", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: "Salon, coiffeur ou service non trouvé",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "Créneau non disponible",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès refusé")
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $createDto = $this->serializer->deserialize(
            $request->getContent(),
            BookingCreateDto::class,
            'json'
        );

        $errors = $this->validator->validate($createDto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer les entités
        $salon = $this->entityManager->getRepository(Salon::class)->find($createDto->salonId);
        if (!$salon) {
            return $this->json(['error' => 'Salon non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $stylist = $this->entityManager->getRepository(Stylist::class)->find($createDto->stylistId);
        if (!$stylist) {
            return $this->json(['error' => 'Coiffeur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $service = $this->entityManager->getRepository(Service::class)->find($createDto->serviceId);
        if (!$service) {
            return $this->json(['error' => 'Service non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $startAt = $createDto->getStartAtAsDateTime();
        $endAt = $startAt->add(new \DateInterval('PT' . $service->getDurationMinutes() . 'M'));

        // Vérifier que la date n'est pas dans le passé
        $now = new \DateTimeImmutable();
        if ($startAt <= $now) {
            return $this->json(['error' => 'Impossible de réserver un rendez-vous dans le passé'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifications métier
        if ($stylist->getSalon() !== $salon) {
            return $this->json(['error' => 'Ce coiffeur ne travaille pas dans ce salon'], Response::HTTP_BAD_REQUEST);
        }

        if (!$stylist->getSkills()->contains($service)) {
            return $this->json(['error' => 'Ce coiffeur ne propose pas ce service'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->availabilityService->canCreateBooking($salon, $stylist, $service, $startAt)) {
            return $this->json(['error' => 'Créneau non disponible'], Response::HTTP_CONFLICT);
        }

        // Créer la réservation
        $booking = new Booking();
        $booking->setSalon($salon);
        $booking->setStylist($stylist);
        $booking->setService($service);
        $booking->setClient($this->getUser());
        $booking->setStartAt($startAt);
        $booking->setEndAt($endAt);
        $booking->setStatus(Booking::STATUS_CONFIRMED); // Auto-confirmé si créneau disponible

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        // Envoyer les emails de confirmation
        $this->notificationService->notifyBookingConfirmed($booking);

        $responseDto = BookingResponseDto::fromEntity($booking);

        return $this->json($responseDto, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_bookings_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/v1/bookings/{id}",
        summary: "Annuler une réservation",
        description: "Permet d'annuler une réservation existante",
        tags: ["Bookings"],
        security: [["Bearer" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la réservation",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Réservation annulée avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Réservation annulée avec succès")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Annulation impossible (moins de 2h avant)",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès refusé"),
            new OA\Response(response: 404, description: "Réservation non trouvée")
        ]
    )]
    public function delete(Booking $booking): JsonResponse
    {
        $user = $this->getUser();

        // Vérifier les permissions avec Voter
        if (!$this->isGranted('BOOKING_CANCEL', $booking)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        // Règles métier d'annulation
        if ($booking->getClient() === $user) {
            // Client : seulement > 2h avant
            if (!$booking->canBeCancelledByClient()) {
                return $this->json(['error' => 'Annulation impossible : moins de 2h avant le rendez-vous'], Response::HTTP_BAD_REQUEST);
            }
        }
        // Owner/Admin : peut forcer l'annulation sans restriction

        $booking->setStatus(Booking::STATUS_CANCELLED);
        $this->entityManager->flush();

        // Envoyer les emails d'annulation
        $reason = $booking->getClient() === $user ? 'Annulation par le client' : 'Annulation par le salon';
        $this->notificationService->notifyBookingCancelled($booking, $reason);

        // Vérifier la liste d'attente et attribuer automatiquement le créneau
        $this->processWaitlistReplacement($booking);

        return $this->json(['message' => 'Réservation annulée avec succès']);
    }

    /**
     * Traite le remplacement automatique par la liste d'attente
     */
    private function processWaitlistReplacement(Booking $cancelledBooking): void
    {
        // Log dans un fichier pour debug
        $logFile = __DIR__ . '/../../../var/log/waitlist_process.log';
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logMessage = sprintf(
            "[%s] === PROCESS WAITLIST REPLACEMENT ===\n" .
            "Booking annulé ID: %d\n",
            date('Y-m-d H:i:s'),
            $cancelledBooking->getId()
        );
        file_put_contents($logFile, $logMessage, FILE_APPEND);

        $salon = $cancelledBooking->getSalon();
        $service = $cancelledBooking->getService();
        $startAt = $cancelledBooking->getStartAt();
        $endAt = $cancelledBooking->getEndAt();

        $logMessage = sprintf(
            "Salon: %s (ID: %d)\n" .
            "Service: %s (ID: %d)\n" .
            "Créneau: %s - %s\n",
            $salon->getName(),
            $salon->getId(),
            $service->getName(),
            $service->getId(),
            $startAt->format('Y-m-d H:i'),
            $endAt->format('Y-m-d H:i')
        );
        file_put_contents($logFile, $logMessage, FILE_APPEND);

        // Chercher les personnes en liste d'attente pour ce créneau
        $waitlistEntries = $this->entityManager
            ->getRepository(\App\Entity\WaitlistEntry::class)
            ->findEntriesForTimeSlot(
                $salon->getId(),
                $service->getId(),
                $startAt,
                $endAt
            );

        $count = count($waitlistEntries);
        file_put_contents($logFile, "Nombre d'entrées trouvées en liste d'attente: $count\n", FILE_APPEND);

        if (empty($waitlistEntries)) {
            // Pas de personne en liste d'attente
            file_put_contents($logFile, "ℹ️ Aucune entrée en liste d'attente trouvée\n\n", FILE_APPEND);
            return;
        }

        // Prendre la première personne (celle inscrite en premier)
        $waitlistEntry = $waitlistEntries[0];
        $newClient = $waitlistEntry->getClient();

        $logMessage = sprintf(
            "👤 Attribution à client: %s %s (ID: %d)\n",
            $newClient->getFirstName(),
            $newClient->getLastName(),
            $newClient->getId()
        );
        file_put_contents($logFile, $logMessage, FILE_APPEND);

        try {
            // Vérifier que le client peut toujours prendre ce créneau
            file_put_contents($logFile, "🔍 Vérification de disponibilité...\n", FILE_APPEND);
            if (!$this->availabilityService->canCreateBooking($salon, $cancelledBooking->getStylist(), $service, $startAt)) {
                // Le créneau n'est plus disponible, supprimer l'entrée de la liste d'attente
                file_put_contents($logFile, "❌ Créneau plus disponible, suppression de l'entrée liste d'attente\n\n", FILE_APPEND);
                $this->entityManager->remove($waitlistEntry);
                $this->entityManager->flush();
                return;
            }
            file_put_contents($logFile, "✅ Créneau disponible\n", FILE_APPEND);

            // Créer la nouvelle réservation
            file_put_contents($logFile, "📝 Création de la nouvelle réservation...\n", FILE_APPEND);
            $newBooking = new Booking();
            $newBooking->setSalon($salon);
            $newBooking->setStylist($cancelledBooking->getStylist());
            $newBooking->setService($service);
            $newBooking->setClient($newClient);
            $newBooking->setStartAt($startAt);
            $newBooking->setEndAt($endAt);
            $newBooking->setStatus(Booking::STATUS_CONFIRMED);

            $this->entityManager->persist($newBooking);
            $this->entityManager->remove($waitlistEntry);
            $this->entityManager->flush();

            $logMessage = sprintf(
                "✅ Nouvelle réservation créée (ID: %d)\n" .
                "✅ Entrée liste d'attente supprimée (ID: %d)\n",
                $newBooking->getId(),
                $waitlistEntry->getId()
            );
            file_put_contents($logFile, $logMessage, FILE_APPEND);

            // Envoyer les emails de confirmation pour la nouvelle réservation
            file_put_contents($logFile, "📧 Envoi des emails de notification...\n", FILE_APPEND);
            $this->notificationService->notifyWaitlistToBooking($newBooking, $waitlistEntry);
            file_put_contents($logFile, "✅ Processus de liste d'attente terminé avec succès\n\n", FILE_APPEND);

        } catch (\Exception $e) {
            // En cas d'erreur, loguer mais ne pas interrompre le processus d'annulation
            $errorMessage = sprintf(
                "❌ Erreur lors du remplacement par liste d'attente: %s\n\n",
                $e->getMessage()
            );
            file_put_contents($logFile, $errorMessage, FILE_APPEND);
        }
    }

    #[Route('/my', name: 'api_bookings_my', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    #[OA\Get(
        path: "/api/v1/bookings/my",
        summary: "Mes réservations",
        description: "Récupère toutes les réservations du client connecté",
        tags: ["Bookings"],
        security: [["Bearer" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des réservations",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/BookingResponseDto")
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function myBookings(): JsonResponse
    {
        $bookings = $this->bookingRepository->findByClientId($this->getUser()->getId());

        $bookingDtos = array_map(function (Booking $booking) {
            return BookingResponseDto::fromEntity($booking);
        }, $bookings);

        return $this->json($bookingDtos);
    }

    #[Route('/upcoming', name: 'api_bookings_upcoming', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    #[OA\Get(
        path: "/api/v1/bookings/upcoming",
        summary: "Mes réservations à venir",
        description: "Récupère les réservations futures du client connecté",
        tags: ["Bookings"],
        security: [["Bearer" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des réservations à venir",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/BookingResponseDto")
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function upcomingBookings(): JsonResponse
    {
        $bookings = $this->bookingRepository->findUpcomingBookings($this->getUser()->getId());

        $bookingDtos = array_map(function (Booking $booking) {
            return BookingResponseDto::fromEntity($booking);
        }, $bookings);

        return $this->json($bookingDtos);
    }
}
