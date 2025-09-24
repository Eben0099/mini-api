<?php

namespace App\Controller\Api;

use App\DTO\Request\ServiceCreateDto;
use App\DTO\Request\ServiceUpdateDto;
use App\DTO\Response\ServiceResponseDto;
use App\Entity\Salon;
use App\Entity\Service;
use App\Repository\ServiceRepository;
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

#[OA\Tag(name: "Services", description: "Gestion des services de coiffure")]
#[Route('/api/v1')]
class ServiceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ServiceRepository $serviceRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('/salons/{salonId}/services', name: 'api_services_create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/v1/salons/{salonId}/services",
        summary: "Créer un service",
        description: "Permet au propriétaire de créer un nouveau service pour son salon",
        tags: ["Services"],
        security: [["Bearer" => []]],
        parameters: [
            new OA\Parameter(
                name: "salonId",
                in: "path",
                required: true,
                description: "ID du salon",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de création du service",
            content: new OA\JsonContent(ref: "#/components/schemas/ServiceCreateDto")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Service créé avec succès",
                content: new OA\JsonContent(ref: "#/components/schemas/ServiceResponseDto")
            ),
            new OA\Response(
                response: 400,
                description: "Données invalides",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "errors", type: "string", example: "Validation failed")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès refusé - Seuls les propriétaires peuvent créer des services"),
            new OA\Response(response: 404, description: "Salon non trouvé")
        ]
    )]
    public function create(Request $request, int $salonId): JsonResponse
    {
        // Vérification manuelle du rôle ROLE_OWNER
        if (!$this->isGranted('ROLE_OWNER')) {
            return $this->json(['error' => 'Accès refusé - Seuls les propriétaires peuvent créer des services'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer le salon via le repository
        $salon = $this->salonRepository->find($salonId);

        if (!$salon) {
            return $this->json(['error' => 'Salon not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur est le propriétaire du salon
        if ($salon->getOwner() !== $this->getUser()) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $createDto = $this->serializer->deserialize(
            $request->getContent(),
            ServiceCreateDto::class,
            'json'
        );

        $errors = $this->validator->validate($createDto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $service = new Service();
        $service->setName($createDto->name);
        $service->setDescription($createDto->description);
        $service->setDurationMinutes($createDto->durationMinutes);
        $service->setPriceCents($createDto->priceCents);
        $service->setIsActive($createDto->isActive ?? true);
        $service->setSalon($salon);

        $this->entityManager->persist($service);
        $this->entityManager->flush();

        $responseDto = new ServiceResponseDto();
        $responseDto->id = $service->getId();
        $responseDto->name = $service->getName();
        $responseDto->description = $service->getDescription();
        $responseDto->durationMinutes = $service->getDurationMinutes();
        $responseDto->priceCents = $service->getPriceCents();
        $responseDto->priceEuros = $service->getPriceEuros();
        $responseDto->isActive = $service->isActive();
        $responseDto->createdAt = $service->getCreatedAt();
        $responseDto->updatedAt = $service->getUpdatedAt();

        return $this->json($responseDto, Response::HTTP_CREATED);
    }

    #[Route('/services/{id}', name: 'api_service_update', methods: ['PATCH'])]
    #[OA\Patch(
        path: "/api/v1/services/{id}",
        summary: "Modifier un service",
        description: "Permet au propriétaire de modifier un service de son salon",
        tags: ["Services"],
        security: [["Bearer" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du service",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de modification du service",
            content: new OA\JsonContent(ref: "#/components/schemas/ServiceUpdateDto")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Service modifié avec succès",
                content: new OA\JsonContent(ref: "#/components/schemas/ServiceResponseDto")
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès refusé"),
            new OA\Response(response: 404, description: "Service non trouvé")
        ]
    )]
    public function update(Request $request, Service $service): JsonResponse
    {
        // Vérification manuelle du rôle ROLE_OWNER
        if (!$this->isGranted('ROLE_OWNER')) {
            return $this->json(['error' => 'Accès refusé - Seuls les propriétaires peuvent modifier des services'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que l'utilisateur peut gérer le salon de ce service
        if (!$this->isGranted('SALON_MANAGE', $service->getSalon())) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $updateDto = $this->serializer->deserialize(
            $request->getContent(),
            ServiceUpdateDto::class,
            'json'
        );

        $errors = $this->validator->validate($updateDto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        if ($updateDto->name !== null) {
            $service->setName($updateDto->name);
        }
        if ($updateDto->description !== null) {
            $service->setDescription($updateDto->description);
        }
        if ($updateDto->durationMinutes !== null) {
            $service->setDurationMinutes($updateDto->durationMinutes);
        }
        if ($updateDto->priceCents !== null) {
            $service->setPriceCents($updateDto->priceCents);
        }
        if ($updateDto->isActive !== null) {
            $service->setIsActive($updateDto->isActive);
        }

        $this->entityManager->flush();

        $responseDto = new ServiceResponseDto();
        $responseDto->id = $service->getId();
        $responseDto->name = $service->getName();
        $responseDto->description = $service->getDescription();
        $responseDto->durationMinutes = $service->getDurationMinutes();
        $responseDto->priceCents = $service->getPriceCents();
        $responseDto->priceEuros = $service->getPriceEuros();
        $responseDto->isActive = $service->isActive();
        $responseDto->createdAt = $service->getCreatedAt();
        $responseDto->updatedAt = $service->getUpdatedAt();

        return $this->json($responseDto);
    }

    #[Route('/services/{id}', name: 'api_service_delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/v1/services/{id}",
        summary: "Supprimer un service",
        description: "Permet au propriétaire de supprimer un service de son salon",
        tags: ["Services"],
        security: [["Bearer" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID du service",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Service supprimé avec succès",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Service supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès refusé"),
            new OA\Response(response: 404, description: "Service non trouvé")
        ]
    )]
    public function delete(Service $service): JsonResponse
    {
        // Vérification manuelle du rôle ROLE_OWNER
        if (!$this->isGranted('ROLE_OWNER')) {
            return $this->json(['error' => 'Accès refusé - Seuls les propriétaires peuvent supprimer des services'], Response::HTTP_FORBIDDEN);
        }

        // Vérifier que l'utilisateur peut gérer le salon de ce service
        if (!$this->isGranted('SALON_MANAGE', $service->getSalon())) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($service);
        $this->entityManager->flush();

        return $this->json(['message' => 'Service supprimé avec succès']);
    }
}
