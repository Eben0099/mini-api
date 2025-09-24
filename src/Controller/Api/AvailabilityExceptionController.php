<?php

namespace App\Controller\Api;

use App\DTO\Request\AvailabilityExceptionCreateDto;
use App\Entity\AvailabilityException;
use App\Entity\Salon;
use App\Entity\Stylist;
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

#[OA\Tag(name: "Availability Exceptions", description: "Gestion des exceptions de disponibilité")]
#[Route('/api/v1/availability-exceptions')]
class AvailabilityExceptionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'api_availability_exceptions_create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/v1/availability-exceptions",
        summary: "Créer une exception de disponibilité",
        description: "Permet au propriétaire de créer une exception de disponibilité (fermeture exceptionnelle, congés, etc.)",
        tags: ["Availability Exceptions"],
        security: [["Bearer" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de création d'exception de disponibilité",
            content: new OA\JsonContent(ref: "#/components/schemas/AvailabilityExceptionCreateDto")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Exception créée avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Exception de disponibilité créée avec succès")
                    ]
                )
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
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès refusé"),
            new OA\Response(response: 404, description: "Salon ou coiffeur non trouvé")
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        // Vérification manuelle du rôle ROLE_OWNER
        if (!$this->isGranted('ROLE_OWNER')) {
            return $this->json(['error' => 'Accès refusé - Seuls les propriétaires peuvent créer des exceptions de disponibilité'], Response::HTTP_FORBIDDEN);
        }
        $createDto = $this->serializer->deserialize(
            $request->getContent(),
            AvailabilityExceptionCreateDto::class,
            'json'
        );

        $errors = $this->validator->validate($createDto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $salon = null;
        $stylist = null;

        // Récupérer et vérifier les entités
        if ($createDto->salonId) {
            $salon = $this->entityManager->getRepository(Salon::class)->find($createDto->salonId);
            if (!$salon) {
                return $this->json(['error' => 'Salon non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier que l'utilisateur peut gérer ce salon
            if (!$this->isGranted('SALON_MANAGE', $salon)) {
                return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
            }
        }

        if ($createDto->stylistId) {
            $stylist = $this->entityManager->getRepository(Stylist::class)->find($createDto->stylistId);
            if (!$stylist) {
                return $this->json(['error' => 'Coiffeur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier que l'utilisateur peut gérer le salon de ce coiffeur
            if (!$this->isGranted('SALON_MANAGE', $stylist->getSalon())) {
                return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
            }
        }

        // Créer l'exception
        $exception = new AvailabilityException();
        $exception->setDate($createDto->getDateAsDateTime());
        $exception->setClosed($createDto->closed);
        $exception->setReason($createDto->reason);

        if ($salon) {
            $exception->setSalon($salon);
        }

        if ($stylist) {
            $exception->setStylist($stylist);
        }

        $this->entityManager->persist($exception);
        $this->entityManager->flush();

        return $this->json([
            'id' => $exception->getId(),
            'message' => 'Exception d\'ouverture créée avec succès'
        ], Response::HTTP_CREATED);
    }
}
