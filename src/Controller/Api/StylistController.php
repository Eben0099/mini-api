<?php

namespace App\Controller\Api;

use App\DTO\Request\StylistCreateDto;
use App\DTO\Response\MediaUploadDto;
use App\DTO\Response\StylistResponseDto;
use App\Entity\Media;
use App\Entity\Salon;
use App\Entity\Service;
use App\Entity\Stylist;
use App\Repository\SalonRepository;
use App\Repository\ServiceRepository;
use App\Repository\StylistRepository;
use App\Repository\UserRepository;
use App\Service\PortfolioUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1')]
class StylistController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StylistRepository $stylistRepository,
        private SalonRepository $salonRepository,
        private UserRepository $userRepository,
        private ServiceRepository $serviceRepository,
        private PortfolioUploadService $portfolioUploadService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('/salons/{salonId}/stylists', name: 'api_stylists_create', methods: ['POST'])]
    public function create(Request $request, int $salonId): JsonResponse
    {
        // Récupérer le salon via le repository
        $salon = $this->salonRepository->find($salonId);

        if (!$salon) {
            return $this->json(['error' => 'Salon not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur peut gérer ce salon
        if (!$this->isGranted('SALON_MANAGE', $salon)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $createDto = $this->serializer->deserialize(
            $request->getContent(),
            StylistCreateDto::class,
            'json'
        );

        $errors = $this->validator->validate($createDto);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->find($createDto->userId);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur n'est pas déjà stylist dans ce salon
        $existingStylist = $this->stylistRepository->findOneBy([
            'salon' => $salon,
            'user' => $user
        ]);
        if ($existingStylist) {
            return $this->json(['error' => 'Cet utilisateur est déjà stylist dans ce salon'], Response::HTTP_CONFLICT);
        }

        // Vérifier que tous les services existent et appartiennent au salon
        $skills = [];
        foreach ($createDto->skillIds as $skillId) {
            $service = $this->serviceRepository->find($skillId);
            if (!$service || $service->getSalon() !== $salon) {
                return $this->json(['error' => 'Service non trouvé ou n\'appartenant pas au salon'], Response::HTTP_BAD_REQUEST);
            }
            $skills[] = $service;
        }

        $stylist = new Stylist();
        $stylist->setSalon($salon);
        $stylist->setUser($user);
        $stylist->setLanguages($createDto->languages);

        foreach ($skills as $skill) {
            $stylist->addSkill($skill);
        }

        // Assigner le rôle styliste à l'utilisateur s'il ne l'a pas déjà
        $currentRoles = $user->getRoles();
        if (!in_array('ROLE_STYLIST', $currentRoles)) {
            $currentRoles[] = 'ROLE_STYLIST';
            $user->setRoles($currentRoles);
        }

        $this->entityManager->persist($stylist);
        $this->entityManager->flush();

        $responseDto = new StylistResponseDto();
        $responseDto->id = $stylist->getId();
        $responseDto->user = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
        ];
        $responseDto->languages = $stylist->getLanguages();
        $responseDto->skills = $stylist->getSkills()->map(function (Service $service) {
            return [
                'id' => $service->getId(),
                'name' => $service->getName(),
                'description' => $service->getDescription(),
            ];
        })->toArray();
        $responseDto->averageRating = $stylist->getAverageRating();
        $responseDto->createdAt = $stylist->getCreatedAt();
        $responseDto->updatedAt = $stylist->getUpdatedAt();

        return $this->json($responseDto, Response::HTTP_CREATED);
    }

    #[Route('/stylists/{id}', name: 'api_stylist_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $stylist = $this->stylistRepository->find($id);
        if (!$stylist) {
            return $this->json(['error' => 'Stylist non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que l'utilisateur peut gérer le salon de ce stylist
        if (!$this->isGranted('SALON_MANAGE', $stylist->getSalon())) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $user = $stylist->getUser();

        $this->entityManager->remove($stylist);
        $this->entityManager->flush();

        // Vérifier si l'utilisateur est encore styliste dans d'autres salons
        $remainingStylistProfiles = $this->stylistRepository->findBy(['user' => $user]);
        if (empty($remainingStylistProfiles)) {
            // L'utilisateur n'est plus styliste dans aucun salon, retirer le rôle
            $currentRoles = $user->getRoles();
            $roles = array_filter($currentRoles, function($role) {
                return $role !== 'ROLE_STYLIST';
            });
            $user->setRoles(array_values($roles));
            $this->entityManager->flush();
        }

        return $this->json(['message' => 'Stylist retiré du salon avec succès']);
    }

    #[Route('/stylists/{id}/media', name: 'api_stylist_media_upload', methods: ['POST'])]
    public function uploadMedia(Request $request, Stylist $stylist): JsonResponse
    {
        // Vérifier que l'utilisateur connecté est bien le propriétaire du stylist
        if (!$this->isGranted('STYLIST_EDIT', $stylist)) {
            return $this->json(['error' => 'Accès refusé. Vous ne pouvez gérer que vos propres médias.'], Response::HTTP_FORBIDDEN);
        }

        try {
            // Récupérer tous les fichiers uploadés
            $allFiles = $request->files->all();

            $files = [];

            // Traiter les fichiers selon leur format d'envoi
            foreach ($allFiles as $fieldName => $fileData) {
                if ($fileData instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                    // Un seul fichier
                    $files[] = $fileData;
                } elseif (is_array($fileData)) {
                    // Plusieurs fichiers dans un champ array
                    foreach ($fileData as $file) {
                        if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                            $files[] = $file;
                        }
                    }
                }
            }

            // Valider qu'on a au moins un fichier valide et qui est une image
            $validFiles = array_filter($files, function($file) {
                if (!$file->isValid() || $file->getSize() <= 0) {
                    return false;
                }

                // Vérifier que c'est une image
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                return in_array($file->getMimeType(), $allowedMimeTypes);
            });

            if (empty($validFiles)) {
                $debugInfo = [
                    'total_files_received' => count($files),
                    'files_details' => array_map(function($f) {
                        return [
                            'name' => $f->getClientOriginalName(),
                            'size' => $f->getSize(),
                            'mime_type' => $f->getMimeType(),
                            'is_valid' => $f->isValid(),
                            'error' => $f->getError(),
                            'is_image' => in_array($f->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])
                        ];
                    }, $files)
                ];

                return $this->json([
                    'error' => 'Aucun fichier image valide fourni. Formats acceptés: JPG, PNG, WebP',
                    'details' => 'Assurez-vous d\'envoyer des fichiers images valides via form-data',
                    'debug' => $debugInfo
                ], Response::HTTP_BAD_REQUEST);
            }

            // Limiter à 10 fichiers maximum par upload
            if (count($validFiles) > 10) {
                return $this->json([
                    'error' => 'Trop de fichiers. Maximum 10 fichiers par upload autorisé.',
                    'received' => count($validFiles),
                    'maximum' => 10
                ], Response::HTTP_BAD_REQUEST);
            }

            $files = $validFiles;

            // Upload des fichiers via le service
            $uploadedMedia = $this->portfolioUploadService->uploadPortfolioFiles($files, $stylist);

            return $this->json([
                'message' => sprintf('%d fichier(s) uploadé(s) avec succès', count($uploadedMedia)),
                'media' => $uploadedMedia
            ], Response::HTTP_CREATED);

        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'upload: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/stylists/{id}/media', name: 'api_stylist_media_list', methods: ['GET'])]
    public function listMedia(Stylist $stylist): JsonResponse
    {
        // Vérifier que l'utilisateur connecté peut voir ces médias
        if (!$this->isGranted('STYLIST_VIEW', $stylist)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $media = $this->entityManager->getRepository(Media::class)
            ->findBy(['stylist' => $stylist], ['createdAt' => 'DESC']);

        $mediaDtos = array_map(function (Media $media) {
            $dto = new MediaUploadDto();
            $dto->id = $media->getId();
            $dto->originalName = $media->getOriginalName();
            $dto->sizeBytes = $media->getSizeBytes();
            $dto->mimeType = $media->getMimeType();
            $dto->path = $media->getPath();
            $dto->url = $media->getPath(); // L'URL publique est stockée dans le path
            $dto->createdAt = $media->getCreatedAt();
            $dto->stylistId = $media->getStylist()->getId();

            // Générer les URLs des thumbnails si elles existent
            $thumbnails = $this->generateThumbnailUrls($media);
            if (!empty($thumbnails)) {
                $dto->thumbnails = $thumbnails;
            }

            return $dto;
        }, $media);

        return $this->json([
            'stylist_id' => $stylist->getId(),
            'total' => count($mediaDtos),
            'media' => $mediaDtos
        ]);
    }

    #[Route('/stylists/{stylistId}/media/{mediaId}', name: 'api_stylist_media_delete', methods: ['DELETE'])]
    public function deleteMedia(int $stylistId, int $mediaId): JsonResponse
    {
        // Récupérer le média
        $media = $this->entityManager->getRepository(Media::class)->find($mediaId);

        if (!$media) {
            return $this->json(['error' => 'Média non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le média appartient bien au stylist demandé
        if ($media->getStylist()->getId() !== $stylistId) {
            return $this->json(['error' => 'Média non trouvé pour ce stylist'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier les droits d'accès
        if (!$this->isGranted('STYLIST_EDIT', $media->getStylist())) {
            return $this->json(['error' => 'Accès refusé. Vous ne pouvez supprimer que vos propres médias.'], Response::HTTP_FORBIDDEN);
        }

        try {
            // Supprimer le média via le service (qui gère aussi la suppression des fichiers)
            $this->portfolioUploadService->deletePortfolioMedia($media, $media->getStylist());

            return $this->json(['message' => 'Média supprimé avec succès']);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Génère les URLs des thumbnails pour un média
     */
    private function generateThumbnailUrls(Media $media): array
    {
        $thumbnails = [];
        $basePath = pathinfo($media->getPath(), PATHINFO_DIRNAME);
        $baseFilename = pathinfo($media->getPath(), PATHINFO_FILENAME);

        // Vérifier l'existence des thumbnails small et medium
        $smallPath = $basePath . '/' . $baseFilename . '_small.' . pathinfo($media->getPath(), PATHINFO_EXTENSION);
        $mediumPath = $basePath . '/' . $baseFilename . '_medium.' . pathinfo($media->getPath(), PATHINFO_EXTENSION);

        // Pour l'instant, on génère les URLs théoriques (le service les crée automatiquement)
        // En production, il faudrait vérifier si les fichiers existent réellement
        $thumbnails = [
            'small' => str_replace('/uploads/', '/uploads/', $smallPath),
            'medium' => str_replace('/uploads/', '/uploads/', $mediumPath)
        ];

        return $thumbnails;
    }
}
