<?php

namespace App\Service;

use App\Entity\Media;
use App\Entity\Stylist;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service de gestion des uploads de portfolios de stylistes
 */
class PortfolioUploadService
{
    public function __construct(
        private FileUploadService $fileUploadService,
        private EntityManagerInterface $entityManager,
        private ?LoggerInterface $logger = null
    ) {}

    /**
     * Upload des fichiers de portfolio pour un styliste
     *
     * @param UploadedFile[] $files
     * @param Stylist $stylist
     * @return Media[] Liste des médias créés
     * @throws \RuntimeException
     */
    public function uploadPortfolioFiles(array $files, Stylist $stylist): array
    {
        $this->log("Début de l'upload de portfolio pour le styliste ID: {$stylist->getId()}");

        if (empty($files)) {
            throw new \RuntimeException('Aucun fichier fourni pour l\'upload');
        }

        $uploadedMedia = [];
        $uploadedFilePaths = []; // Pour rollback en cas d'erreur

        try {
            // Upload des fichiers via le FileUploadService
            $uploadedFiles = $this->fileUploadService->uploadMultiple($files, 'stylists/portfolios', true);

            // Créer les entités Media pour chaque fichier uploadé
            foreach ($uploadedFiles as $uploadedFile) {
                $media = new Media();
                $media->setOriginalName(basename($uploadedFile['filename']));
                $media->setSizeBytes(filesize($uploadedFile['path']));
                $media->setMimeType($this->guessMimeType($uploadedFile['path']));
                $media->setPath($uploadedFile['url']);
                $media->setStylist($stylist);

                $this->entityManager->persist($media);
                $uploadedMedia[] = $media;
                $uploadedFilePaths[] = $uploadedFile['path'];

                // Collecter aussi les chemins des thumbnails pour le rollback
                foreach ($uploadedFile['thumbnails'] as $thumbUrl) {
                    $thumbPath = $this->getFilePathFromUrl($thumbUrl);
                    $uploadedFilePaths[] = $thumbPath;
                }
            }

            $this->entityManager->flush();
            $this->log("Upload de portfolio réussi: " . count($uploadedMedia) . " fichier(s) pour le styliste {$stylist->getId()}");

            return $uploadedMedia;

        } catch (\Exception $e) {
            $this->log("Erreur lors de l'upload de portfolio: " . $e->getMessage());

            // Rollback: supprimer les fichiers déjà uploadés
            $this->cleanupFiles($uploadedFilePaths);

            // Rollback: supprimer les entités Media déjà persistées
            foreach ($uploadedMedia as $media) {
                if ($this->entityManager->contains($media)) {
                    $this->entityManager->remove($media);
                }
            }
            $this->entityManager->flush();

            throw new \RuntimeException('Erreur lors de l\'upload du portfolio: ' . $e->getMessage());
        }
    }

    /**
     * Supprime un média de portfolio
     *
     * @param Media $media
     * @param Stylist $stylist
     * @throws \RuntimeException
     */
    public function deletePortfolioMedia(Media $media, Stylist $stylist): void
    {
        $this->log("Début de la suppression du média ID: {$media->getId()} pour le styliste {$stylist->getId()}");

        // Vérifier que le média appartient bien au styliste
        if ($media->getStylist()->getId() !== $stylist->getId()) {
            throw new \RuntimeException('Le média n\'appartient pas à ce styliste');
        }

        $filePathsToDelete = [];

        try {
            // Collecter le chemin du fichier principal
            $filePath = $this->getFilePathFromUrl($media->getPath());
            $filePathsToDelete[] = $filePath;

            // Générer et collecter les chemins des thumbnails
            $thumbnails = $this->generateThumbnailPaths($media->getPath());
            foreach ($thumbnails as $thumbPath) {
                $filePathsToDelete[] = $this->getFilePathFromUrl($thumbPath);
            }

            // Supprimer l'entité Media de la base de données
            $this->entityManager->remove($media);
            $this->entityManager->flush();

            // Supprimer les fichiers physiques
            $this->cleanupFiles($filePathsToDelete);

            $this->log("Suppression du média réussie: ID {$media->getId()}");

        } catch (\Exception $e) {
            $this->log("Erreur lors de la suppression du média: " . $e->getMessage());
            throw new \RuntimeException('Erreur lors de la suppression du média: ' . $e->getMessage());
        }
    }

    /**
     * Génère les chemins théoriques des thumbnails pour un média
     */
    private function generateThumbnailPaths(string $mediaPath): array
    {
        $thumbnails = [];
        $pathInfo = pathinfo($mediaPath);
        $basePath = $pathInfo['dirname'];
        $baseFilename = $pathInfo['filename'];

        // Générer les chemins des thumbnails small et medium
        $thumbnails[] = $basePath . '/' . $baseFilename . '_small.' . $pathInfo['extension'];
        $thumbnails[] = $basePath . '/' . $baseFilename . '_medium.' . $pathInfo['extension'];

        return $thumbnails;
    }

    /**
     * Détermine le type MIME d'un fichier
     */
    private function guessMimeType(string $filePath): string
    {
        $mimeType = mime_content_type($filePath);
        return $mimeType ?: 'application/octet-stream';
    }

    /**
     * Convertit une URL publique en chemin physique
     */
    private function getFilePathFromUrl(string $url): string
    {
        // L'URL publique est du type /uploads/stylists/portfolios/filename.jpg
        // Le chemin physique est dans public/uploads/stylists/portfolios/filename.jpg
        $relativePath = ltrim($url, '/');
        return __DIR__ . '/../../public/' . $relativePath;
    }

    /**
     * Supprime les fichiers du système de fichiers
     */
    private function cleanupFiles(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    $this->log("Fichier supprimé: {$filePath}");
                } else {
                    $this->log("Erreur lors de la suppression du fichier: {$filePath}");
                }
            }
        }
    }

    /**
     * Méthode de logging centralisée
     */
    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info("[PortfolioUploadService] {$message}");
        } else {
            error_log("[PortfolioUploadService] {$message}");
        }
    }
}
