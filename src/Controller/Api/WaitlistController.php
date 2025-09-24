<?php

namespace App\Controller\Api;

use App\DTO\Request\WaitlistEntryCreateDto;
use App\Entity\Salon;
use App\Entity\Service;
use App\Entity\WaitlistEntry;
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

#[OA\Tag(name: "Waitlist", description: "Gestion de la liste d'attente")]
#[Route('/api/v1/waitlist')]
class WaitlistController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'api_waitlist_create', methods: ['POST'])]
    #[IsGranted('ROLE_CLIENT')]
    #[OA\Post(
        path: "/api/v1/waitlist",
        summary: "S'inscrire à la liste d'attente",
        description: "Permet à un client de s'inscrire à la liste d'attente pour un créneau indisponible",
        tags: ["Waitlist"],
        security: [["Bearer" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données d'inscription à la liste d'attente",
            content: new OA\JsonContent(ref: "#/components/schemas/WaitlistEntryCreateDto")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Inscription à la liste d'attente réussie",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Inscription à la liste d'attente réussie")
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Données invalides ou déjà inscrit",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 404, description: "Salon ou service non trouvé")
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $createDto = $this->serializer->deserialize(
            $request->getContent(),
            WaitlistEntryCreateDto::class,
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

        $service = $this->entityManager->getRepository(Service::class)->find($createDto->serviceId);
        if (!$service) {
            return $this->json(['error' => 'Service non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le service appartient au salon
        if ($service->getSalon() !== $salon) {
            return $this->json(['error' => 'Ce service n\'appartient pas à ce salon'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que l'utilisateur n'est pas déjà dans la liste d'attente pour cette période
        $existingEntry = $this->entityManager->getRepository(WaitlistEntry::class)->findOneBy([
            'salon' => $salon,
            'service' => $service,
            'client' => $this->getUser(),
            'desiredStartRangeStart' => $createDto->getDesiredStartRangeStartAsDateTime(),
            'desiredStartRangeEnd' => $createDto->getDesiredStartRangeEndAsDateTime(),
        ]);

        if ($existingEntry) {
            return $this->json(['error' => 'Vous êtes déjà dans la liste d\'attente pour cette période'], Response::HTTP_CONFLICT);
        }

        // Créer l'entrée dans la liste d'attente
        $waitlistEntry = new WaitlistEntry();
        $waitlistEntry->setSalon($salon);
        $waitlistEntry->setService($service);
        $waitlistEntry->setClient($this->getUser());
        $waitlistEntry->setDesiredStartRangeStart($createDto->getDesiredStartRangeStartAsDateTime());
        $waitlistEntry->setDesiredStartRangeEnd($createDto->getDesiredStartRangeEndAsDateTime());

        $this->entityManager->persist($waitlistEntry);
        $this->entityManager->flush();

        return $this->json([
            'id' => $waitlistEntry->getId(),
            'message' => 'Inscription à la liste d\'attente réussie'
        ], Response::HTTP_CREATED);
    }
}
