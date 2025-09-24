<?php

namespace App\Controller\Api;

use App\DTO\Request\AvailabilityRequestDto;
use App\DTO\Request\SalonCreateDto;
use App\DTO\Request\SalonUpdateDto;
use App\DTO\Response\AvailabilityResponseDto;
use App\DTO\Response\SalonListDto;
use App\DTO\Response\SalonResponseDto;
use App\Entity\Salon;
use App\Repository\SalonRepository;
use App\Service\AvailabilityService;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/salons')]
class SalonController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SalonRepository $salonRepository,
        private AvailabilityService $availabilityService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private SlugGenerator $slugGenerator,
    ) {}

    #[Route('', name: 'api_salons_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $city = $request->query->get('city');
        $name = $request->query->get('name');

        $criteria = [];
        if ($city) {
            $criteria['city'] = $city;
        }

        $offset = ($page - 1) * $limit;
        $salons = $this->salonRepository->findBy($criteria, ['createdAt' => 'DESC'], $limit, $offset);

        // Appliquer le filtre par nom si fourni
        if ($name) {
            $salons = array_filter($salons, function (Salon $salon) use ($name) {
                return stripos($salon->getName(), $name) !== false;
            });
        }

        $salonListDtos = array_map(function (Salon $salon) {
            $dto = new SalonListDto();
            $dto->id = $salon->getId();
            $dto->name = $salon->getName();
            $dto->slug = $salon->getSlug();
            $dto->city = $salon->getCity();
            $dto->averageRating = $salon->getAverageRating();
            $dto->servicesCount = $salon->getServices()->count();
            $dto->stylistsCount = $salon->getStylists()->count();
            return $dto;
        }, $salons);

        return $this->json($salonListDtos);
    }

    #[Route('/{id}', name: 'api_salon_detail', methods: ['GET'])]
    public function detail(Salon $salon): JsonResponse
    {
        $dto = new SalonResponseDto();
        $dto->id = $salon->getId();
        $dto->name = $salon->getName();
        $dto->slug = $salon->getSlug();
        $dto->address = $salon->getAddress();
        $dto->city = $salon->getCity();
        $dto->lat = $salon->getLat();
        $dto->lng = $salon->getLng();
        $dto->openHours = $salon->getOpenHours();
        $dto->averageRating = $salon->getAverageRating();
        $dto->createdAt = $salon->getCreatedAt();
        $dto->updatedAt = $salon->getUpdatedAt();

        // Informations sur le propriétaire
        $owner = $salon->getOwner();
        $dto->owner = [
            'id' => $owner->getId(),
            'firstName' => $owner->getFirstName(),
            'lastName' => $owner->getLastName(),
            'email' => $owner->getEmail(),
        ];

        // Services du salon
        $dto->services = $salon->getServices()->map(function ($service) {
            return [
                'id' => $service->getId(),
                'name' => $service->getName(),
                'description' => $service->getDescription(),
                'durationMinutes' => $service->getDurationMinutes(),
                'priceCents' => $service->getPriceCents(),
                'priceEuros' => $service->getPriceEuros(),
                'isActive' => $service->isActive(),
            ];
        })->toArray();

        // Stylists du salon
        $dto->stylists = $salon->getStylists()->map(function ($stylist) {
            return [
                'id' => $stylist->getId(),
                'user' => [
                    'id' => $stylist->getUser()->getId(),
                    'firstName' => $stylist->getUser()->getFirstName(),
                    'lastName' => $stylist->getUser()->getLastName(),
                    'email' => $stylist->getUser()->getEmail(),
                ],
                'languages' => $stylist->getLanguages(),
                'averageRating' => $stylist->getAverageRating(),
            ];
        })->toArray();

        return $this->json($dto);
    }

    #[Route('', name: 'api_salon_create', methods: ['POST'])]
    #[IsGranted('ROLE_OWNER')]
    public function create(Request $request): JsonResponse
    {
        $createDto = $this->serializer->deserialize(
            $request->getContent(),
            SalonCreateDto::class,
            'json'
        );

        $errors = $this->validator->validate($createDto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $salon = new Salon();
        $salon->setName($createDto->name);
        $salon->setAddress($createDto->address);
        $salon->setCity($createDto->city);
        $salon->setLat($createDto->lat);
        $salon->setLng($createDto->lng);
        $salon->setOpenHours($createDto->openHours);
        $salon->setSlug($this->slugGenerator->generateUniqueSlug($createDto->name));
        $salon->setOwner($this->getUser());

        $this->entityManager->persist($salon);
        $this->entityManager->flush();

        return $this->json(['id' => $salon->getId(), 'slug' => $salon->getSlug()], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_salon_update', methods: ['PATCH'])]
    public function update(Request $request, Salon $salon): JsonResponse
    {
        // Vérifier les permissions avec Voter
        if (!$this->isGranted('SALON_EDIT', $salon)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $updateDto = $this->serializer->deserialize(
            $request->getContent(),
            SalonUpdateDto::class,
            'json'
        );

        $errors = $this->validator->validate($updateDto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        if ($updateDto->name !== null) {
            $salon->setName($updateDto->name);
            $salon->setSlug($this->slugGenerator->generateUniqueSlug($updateDto->name));
        }
        if ($updateDto->address !== null) {
            $salon->setAddress($updateDto->address);
        }
        if ($updateDto->city !== null) {
            $salon->setCity($updateDto->city);
        }
        if ($updateDto->lat !== null) {
            $salon->setLat($updateDto->lat);
        }
        if ($updateDto->lng !== null) {
            $salon->setLng($updateDto->lng);
        }
        if ($updateDto->openHours !== null) {
            $salon->setOpenHours($updateDto->openHours);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Salon mis à jour avec succès']);
    }

    #[Route('/{id}/availability', name: 'api_salon_availability', methods: ['GET'])]
    public function getAvailability(Request $request, Salon $salon): JsonResponse
    {
        $serviceId = $request->query->getInt('serviceId');
        $stylistId = $request->query->get('stylistId') ? $request->query->getInt('stylistId') : null;
        $date = $request->query->get('date');
        $duration = $request->query->getInt('duration', 60); // Durée par défaut 60 minutes

        if (!$serviceId || !$date) {
            return $this->json(['error' => 'Paramètres serviceId et date requis'], Response::HTTP_BAD_REQUEST);
        }

        $availabilityRequest = new AvailabilityRequestDto();
        $availabilityRequest->serviceId = $serviceId;
        $availabilityRequest->stylistId = $stylistId;
        $availabilityRequest->date = $date;
        $availabilityRequest->duration = $duration;

        $errors = $this->validator->validate($availabilityRequest);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer le service
        $service = $this->entityManager->getRepository(\App\Entity\Service::class)->find($serviceId);
        if (!$service) {
            return $this->json(['error' => 'Service non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le service appartient au salon
        if ($service->getSalon() !== $salon) {
            return $this->json(['error' => 'Ce service n\'appartient pas à ce salon'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer le stylist si spécifié
        $stylist = null;
        if ($stylistId) {
            $stylist = $this->entityManager->getRepository(\App\Entity\Stylist::class)->find($stylistId);
            if (!$stylist) {
                return $this->json(['error' => 'Coiffeur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier que le stylist travaille dans ce salon
            if ($stylist->getSalon() !== $salon) {
                return $this->json(['error' => 'Ce coiffeur ne travaille pas dans ce salon'], Response::HTTP_BAD_REQUEST);
            }
        }

        $dateTime = $availabilityRequest->getDateAsDateTime();
        $availableSlots = $this->availabilityService->getAvailableSlots(
            $salon,
            $service,
            $dateTime,
            $availabilityRequest->duration,
            $stylist
        );

        $responseDto = AvailabilityResponseDto::fromStylistsWithSlots($availableSlots);

        return $this->json($responseDto);
    }

    #[Route('/{id}/hours', name: 'api_salon_hours_update', methods: ['PUT'])]
    public function updateHours(Request $request, Salon $salon): JsonResponse
    {
        // Vérifier les permissions avec Voter
        if (!$this->isGranted('SALON_MANAGE', $salon)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['openHours']) || !is_array($data['openHours'])) {
            return $this->json(['error' => 'Format openHours invalide'], Response::HTTP_BAD_REQUEST);
        }

        // TODO: Validation plus stricte du format des horaires
        $salon->setOpenHours($data['openHours']);
        $this->entityManager->flush();

        return $this->json(['message' => 'Horaires mis à jour avec succès']);
    }

    #[Route('/{id}', name: 'api_salon_delete', methods: ['DELETE'])]
    public function delete(Salon $salon): JsonResponse
    {
        // Vérifier les permissions avec Voter
        if (!$this->isGranted('SALON_DELETE', $salon)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($salon);
        $this->entityManager->flush();

        return $this->json(['message' => 'Salon supprimé avec succès']);
    }
}
