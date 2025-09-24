<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des uploads de fichiers
 * Supporte images (jpg, png, webp) et vidéos (mp4, webm)
 */
class FileUploadService
{
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/webm'];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB pour les portfolios

    // Signatures magiques pour validation avancée
    private const MAGIC_BYTES = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'image/webp' => ["\x52\x49\x46\x46", "\x57\x45\x42\x50"],
        'video/mp4' => ["\x00\x00\x00\x20\x66\x74\x79\x70"],
        'video/webm' => ["\x1A\x45\xDF\xA3"],
    ];

    private const IMAGE_CONFIGS = [
        'salon/profiles' => [
            'max_width' => 800,
            'max_height' => 600,
            'quality' => 90
        ],
        'salon/covers' => [
            'max_width' => 1920,
            'max_height' => 1080,
            'quality' => 85
        ],
        'user/avatars' => [
            'max_width' => 400,
            'max_height' => 400,
            'quality' => 90
        ],
        'hairdresser/portfolios' => [
            'max_width' => 1200,
            'max_height' => 900,
            'quality' => 85,
            'thumbnails' => [
                'small' => ['width' => 150, 'height' => 150, 'quality' => 90],
                'medium' => ['width' => 300, 'height' => 300, 'quality' => 85]
            ]
        ],
        'stylists/portfolios' => [
            'max_width' => 1200,
            'max_height' => 900,
            'quality' => 85,
            'thumbnails' => [
                'small' => ['width' => 150, 'height' => 150, 'quality' => 90],
                'medium' => ['width' => 300, 'height' => 300, 'quality' => 85]
            ]
        ],
        'onboarding' => [
            'max_width' => 1080,    // Format mobile optimal
            'max_height' => 1920,   // Portrait pour onboarding
            'quality' => 90         // Bonne qualité pour mobile
        ]
    ];

    public function __construct(
        private string $uploadDirectory,
        private SluggerInterface $slugger,
        private ?LoggerInterface $logger = null
    ) {
        $this->uploadDirectory = rtrim($uploadDirectory, '/');

        // Vérifier si l'extension GD est disponible
        if (!extension_loaded('gd')) {
            $this->log("ATTENTION: Extension GD non disponible. L'optimisation d'images sera désactivée.");
        } else {
            $this->log("Extension GD disponible pour l'optimisation d'images.");
        }
    }

    /**
     * Upload un seul fichier - Nouvelle approche robuste
     */
    public function upload(UploadedFile $file, string $category, bool $strictValidation = false): array
    {
        $this->log("Début de l'upload pour la catégorie: {$category}");

        // Validation basique (avec option stricte pour portfolios)
        $this->validateBasicFile($file, $strictValidation);

        $targetDirectory = $this->getTargetDirectory($category);
        $this->ensureDirectoryExists($targetDirectory);

        $fileName = $this->generateFileName($file, $category, $strictValidation);
        $filePath = $targetDirectory . '/' . $fileName;

        $thumbnails = [];

        try {
            // APPROCHE ROBUSTE : Copie immédiate du fichier temporaire
            $tempPath = $file->getPathname();
            $this->log("Fichier temporaire source: {$tempPath}");
            $this->log("Destination cible: {$filePath}");

            // Vérifier que le fichier temporaire existe et est lisible
            if (!file_exists($tempPath)) {
                throw new \RuntimeException("Fichier temporaire introuvable: {$tempPath}");
            }

            if (!is_readable($tempPath)) {
                throw new \RuntimeException("Fichier temporaire non lisible: {$tempPath}");
            }

            // Obtenir la taille du fichier
            $fileSize = filesize($tempPath);
            if ($fileSize === false) {
                throw new \RuntimeException("Impossible de déterminer la taille du fichier: {$tempPath}");
            }

            $this->log("Taille du fichier: {$fileSize} bytes");

            // Copie directe du fichier temporaire vers la destination
            if (!copy($tempPath, $filePath)) {
                throw new \RuntimeException("Échec de la copie du fichier vers: {$filePath}");
            }

            // Vérifier que la copie a réussi
            if (!file_exists($filePath)) {
                throw new \RuntimeException("Le fichier copié n'existe pas: {$filePath}");
            }

            $copiedSize = filesize($filePath);
            if ($copiedSize !== $fileSize) {
                // Nettoyer le fichier partiellement copié
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                throw new \RuntimeException("Copie incomplète: {$copiedSize}/{$fileSize} bytes");
            }

            $this->log("Copie réussie: {$fileSize} bytes");

            // Changer les permissions du fichier (lecture/écriture pour tous)
            if (!chmod($filePath, 0666)) {
                $this->log("Attention: Impossible de changer les permissions de {$filePath}");
            } else {
                $this->log("Permissions du fichier définies à 0666: {$filePath}");
            }

            // Traiter l'image (optimisation, EXIF sanitization, thumbnails)
            if ($this->isImage($file)) {
                try {
                    // Sanitization des métadonnées EXIF si activée
                    if ($strictValidation) {
                        $this->sanitizeExifData($filePath);
                    }

                    // Optimiser l'image
                    $this->optimizeImage($filePath, $category);

                    // Générer les thumbnails si configurés
                    $thumbnails = $this->generateThumbnails($filePath, $category);

                } catch (\Exception $e) {
                    $this->log("Erreur lors du traitement de l'image: " . $e->getMessage() . " - Upload continué sans traitement");
                    // L'upload continue même si le traitement échoue
                }
            }

            $publicUrl = $this->getPublicUrl($category, $fileName);
            $this->log("Upload terminé avec succès: {$publicUrl}");

            return [
                'url' => $publicUrl,
                'path' => $filePath,
                'filename' => $fileName,
                'thumbnails' => $thumbnails
            ];

        } catch (\Exception $e) {
            $this->log("Erreur lors de l'upload: " . $e->getMessage());

            // Nettoyer le fichier partiellement uploadé
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
                $this->log("Fichier partiellement uploadé nettoyé: {$filePath}");
            }

            // Nettoyer les thumbnails partiellement créés
            foreach ($thumbnails as $thumbPath) {
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }

            throw new \RuntimeException('Erreur lors de l\'upload du fichier: ' . $e->getMessage());
        }
    }

    /**
     * Upload multiple fichiers
     */
    public function uploadMultiple(array $files, string $category, bool $strictValidation = false): array
    {
        $uploadedFiles = [];
        $uploadedPaths = []; // Pour rollback en cas d'erreur
        $uploadedThumbnails = []; // Pour rollback des thumbnails

        try {
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $result = $this->upload($file, $category, $strictValidation);
                    $uploadedFiles[] = $result;
                    $uploadedPaths[] = $result['path'];

                    // Collecter tous les chemins de thumbnails pour rollback
                    foreach ($result['thumbnails'] as $thumbType => $thumbUrl) {
                        $uploadedThumbnails[] = $this->getFilePathFromUrl($thumbUrl);
                    }
                }
            }

            return $uploadedFiles;

        } catch (\Exception $e) {
            // Rollback: supprimer les fichiers et thumbnails déjà uploadés
            $allPaths = array_merge($uploadedPaths, $uploadedThumbnails);
            $this->cleanupFiles($allPaths);
            throw $e;
        }
    }

    /**
     * Supprime des fichiers (pour rollback)
     */
    public function deleteFiles(array $urls): void
    {
        foreach ($urls as $url) {
            $filePath = $this->getFilePathFromUrl($url);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    /**
     * Validation basique du fichier (avant copie)
     */
    private function validateBasicFile(UploadedFile $file, bool $strictValidation = false): void
    {
        $this->log("Validation du fichier: " . $file->getClientOriginalName());

        // Vérifier si le fichier est valide
        if (!$file->isValid()) {
            $error = $file->getError();
            $errorMessage = match($error) {
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale du formulaire',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                UPLOAD_ERR_CANT_WRITE => 'Erreur d\'écriture sur le disque',
                UPLOAD_ERR_EXTENSION => 'Upload stoppé par une extension PHP',
                default => 'Erreur inconnue lors de l\'upload'
            };
            $this->log("Erreur de validation: {$errorMessage}");
            throw new \RuntimeException('Fichier invalide: ' . $errorMessage);
        }

        // Vérifier la taille du fichier (stricte pour les portfolios)
        $maxSize = $strictValidation ? self::MAX_IMAGE_SIZE : self::MAX_FILE_SIZE;
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            $this->log("Fichier trop volumineux: {$file->getSize()} bytes");
            throw new \RuntimeException("Fichier trop volumineux (max {$maxSizeMB}MB)");
        }

        // Vérifier le type MIME
        $mimeType = $file->getMimeType();
        $allowedTypes = $strictValidation
            ? self::ALLOWED_IMAGE_TYPES  // Uniquement images pour portfolios
            : array_merge(self::ALLOWED_IMAGE_TYPES, self::ALLOWED_VIDEO_TYPES);

        if (!in_array($mimeType, $allowedTypes)) {
            $allowedFormats = $strictValidation ? 'JPG, PNG, WebP' : 'JPG, PNG, WebP, MP4, WebM';
            $this->log("Type MIME non autorisé: {$mimeType}");
            throw new \RuntimeException('Type de fichier non autorisé. Formats acceptés: ' . $allowedFormats);
        }

        // Validation avancée avec magic bytes si activée
        if ($strictValidation) {
            $this->validateMagicBytes($file, $mimeType);
        }

        $this->log("Validation basique réussie pour: {$mimeType}");
    }

    /**
     * Validation avancée avec vérification des magic bytes
     */
    private function validateMagicBytes(UploadedFile $file, string $mimeType): void
    {
        if (!isset(self::MAGIC_BYTES[$mimeType])) {
            $this->log("Pas de signature magique définie pour: {$mimeType}");
            return;
        }

        $filePath = $file->getPathname();
        $handle = fopen($filePath, 'rb');

        if (!$handle) {
            throw new \RuntimeException('Impossible de lire le fichier pour validation');
        }

        $signatures = self::MAGIC_BYTES[$mimeType];
        $bytesToRead = max(array_map('strlen', $signatures));
        $fileBytes = fread($handle, $bytesToRead);
        fclose($handle);

        if ($fileBytes === false) {
            throw new \RuntimeException('Erreur lors de la lecture du fichier');
        }

        $validSignature = false;
        foreach ($signatures as $signature) {
            if (substr($fileBytes, 0, strlen($signature)) === $signature) {
                $validSignature = true;
                break;
            }
        }

        if (!$validSignature) {
            $this->log("Signature magique invalide pour {$mimeType}");
            throw new \RuntimeException('Fichier corrompu ou type MIME falsifié détecté');
        }

        $this->log("Validation magic bytes réussie pour: {$mimeType}");
    }

    /**
     * Valide un fichier uploadé (méthode legacy pour compatibilité)
     */
    private function validateFile(UploadedFile $file): void
    {
        $this->validateBasicFile($file);
    }

    /**
     * Vérifie si l'extension GD est disponible et fonctionnelle
     */
    private function isGdAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromjpeg');
    }

    /**
     * Méthode de logging centralisée
     */
    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info("[FileUploadService] {$message}");
        } else {
            // Fallback vers error_log si pas de logger
            error_log("[FileUploadService] {$message}");
        }
    }

    /**
     * Génère un nom de fichier unique et sécurisé
     */
    private function generateFileName(UploadedFile $file, string $category, bool $useUuid = false): string
    {
        $extension = $file->guessExtension();

        if ($useUuid) {
            // Pour les portfolios : noms UUID organisés par date
            $uuid = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            return $uuid . '.' . $extension;
        } else {
            // Méthode originale pour compatibilité
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);

            $categoryPrefix = str_replace('/', '_', $category);
            $timestamp = time();
            $random = bin2hex(random_bytes(4));

            return $categoryPrefix . '_' . $timestamp . '_' . $random . '_' . $safeFilename . '.' . $extension;
        }
    }

    /**
     * Obtient le répertoire cible pour une catégorie
     */
    private function getTargetDirectory(string $category): string
    {
        return $this->uploadDirectory . '/' . $category;
    }

    /**
     * Vérifie simplement que le répertoire existe
     */
    private function ensureDirectoryExists(string $directory): void
    {
        $this->log("Vérification du répertoire: {$directory}");

        // Normaliser les séparateurs
        $directory = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $directory);

        if (is_dir($directory)) {
            $this->log("Répertoire existe: {$directory}");

            // Vérifier s'il est accessible en écriture
            if (!is_writable($directory)) {
                $this->log("Répertoire non accessible en écriture, ajustement des permissions");
                @chmod($directory, 0755) || @chmod($directory, 0777);
            }
        } else {
            $this->log("Répertoire n'existe pas: {$directory} - Tentative de création");

            // Tenter de créer le répertoire (seulement si nécessaire)
            if (@mkdir($directory, 0755, true) || @mkdir($directory, 0777, true)) {
                $this->log("Répertoire créé: {$directory}");
            } else {
                $this->log("⚠️ Impossible de créer le répertoire: {$directory} - Continuation");
                // Ne pas throw d'exception - l'upload pourrait encore fonctionner
            }
        }
    }

    /**
     * Génère l'URL publique du fichier
     */
    private function getPublicUrl(string $category, string $fileName): string
    {
        return '/uploads/' . $category . '/' . $fileName;
    }

    /**
     * Récupère le chemin physique à partir de l'URL
     */
    private function getFilePathFromUrl(string $url): string
    {
        $relativePath = ltrim($url, '/');
        return $this->uploadDirectory . '/../' . $relativePath;
    }

    /**
     * Vérifie si le fichier est une image
     */
    private function isImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), self::ALLOWED_IMAGE_TYPES);
    }

    /**
     * Optimise une image (redimensionnement et compression)
     */
    private function optimizeImage(string $filePath, string $category): void
    {
        // Vérifier si l'extension GD est disponible et fonctionnelle
        if (!$this->isGdAvailable()) {
            $this->log("Extension GD non disponible ou incomplète, optimisation d'image ignorée pour: {$filePath}");
            return;
        }

        if (!isset(self::IMAGE_CONFIGS[$category])) {
            $this->log("Pas de configuration d'optimisation pour la catégorie: {$category}");
            return; // Pas de config spécifique, on laisse tel quel
        }

        $config = self::IMAGE_CONFIGS[$category];
        $imageInfo = getimagesize($filePath);

        if (!$imageInfo) {
            $this->log("Impossible d'obtenir les informations de l'image: {$filePath}");
            return; // Pas une image valide
        }

        [$width, $height, $type] = $imageInfo;
        $this->log("Optimisation d'image: {$width}x{$height}, type: {$type} pour {$filePath}");

        // Créer l'image source selon le type
        $sourceImage = match($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => imagecreatefrompng($filePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($filePath),
            default => null
        };

        if (!$sourceImage) {
            return;
        }

        // Calculer les nouvelles dimensions en gardant le ratio
        $newDimensions = $this->calculateNewDimensions(
            $width,
            $height,
            $config['max_width'],
            $config['max_height']
        );

        // Redimensionner si nécessaire
        if ($newDimensions['width'] < $width || $newDimensions['height'] < $height) {
            $resizedImage = imagecreatetruecolor($newDimensions['width'], $newDimensions['height']);

            // Préserver la transparence pour PNG et WebP
            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            }

            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $newDimensions['width'], $newDimensions['height'],
                $width, $height
            );

            // Sauvegarder l'image optimisée
            match($type) {
                IMAGETYPE_JPEG => imagejpeg($resizedImage, $filePath, $config['quality']),
                IMAGETYPE_PNG => imagepng($resizedImage, $filePath, (int)((100 - $config['quality']) / 10)),
                IMAGETYPE_WEBP => imagewebp($resizedImage, $filePath, $config['quality']),
            };

            imagedestroy($resizedImage);
        }

        imagedestroy($sourceImage);
    }

    /**
     * Calcule les nouvelles dimensions en gardant le ratio
     */
    private function calculateNewDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return ['width' => $width, 'height' => $height];
        }

        $ratioWidth = $maxWidth / $width;
        $ratioHeight = $maxHeight / $height;
        $ratio = min($ratioWidth, $ratioHeight);

        return [
            'width' => (int)($width * $ratio),
            'height' => (int)($height * $ratio)
        ];
    }

    /**
     * Sanitize les métadonnées EXIF d'une image
     */
    private function sanitizeExifData(string $filePath): void
    {
        $this->log("Sanitization EXIF pour: {$filePath}");

        // Créer une nouvelle image sans métadonnées EXIF
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            $this->log("Impossible d'obtenir les informations de l'image pour EXIF sanitization");
            return;
        }

        [$width, $height, $type] = $imageInfo;

        // Charger l'image selon son type
        $sourceImage = match($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => imagecreatefrompng($filePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($filePath),
            default => null
        };

        if (!$sourceImage) {
            $this->log("Type d'image non supporté pour EXIF sanitization");
            return;
        }

        // Sauvegarder l'image sans métadonnées EXIF
        $tempPath = $filePath . '.tmp';
        $success = match($type) {
            IMAGETYPE_JPEG => imagejpeg($sourceImage, $tempPath, 95),
            IMAGETYPE_PNG => imagepng($sourceImage, $tempPath),
            IMAGETYPE_WEBP => imagewebp($sourceImage, $tempPath, 90),
            default => false
        };

        imagedestroy($sourceImage);

        if ($success && file_exists($tempPath)) {
            // Remplacer le fichier original
            if (rename($tempPath, $filePath)) {
                $this->log("EXIF sanitization réussie pour: {$filePath}");
            } else {
                unlink($tempPath);
                $this->log("Erreur lors du remplacement du fichier après EXIF sanitization");
            }
        } else {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            $this->log("Erreur lors de la sauvegarde après EXIF sanitization");
        }
    }

    /**
     * Génère les thumbnails pour une image
     */
    private function generateThumbnails(string $filePath, string $category): array
    {
        if (!isset(self::IMAGE_CONFIGS[$category]['thumbnails'])) {
            return [];
        }

        $this->log("Génération des thumbnails pour: {$filePath}");

        $thumbnails = [];
        $thumbnailConfigs = self::IMAGE_CONFIGS[$category]['thumbnails'];

        // Charger l'image source
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            $this->log("Impossible de charger l'image pour génération de thumbnails");
            return [];
        }

        [$width, $height, $type] = $imageInfo;

        $sourceImage = match($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG => imagecreatefrompng($filePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($filePath),
            default => null
        };

        if (!$sourceImage) {
            $this->log("Type d'image non supporté pour génération de thumbnails");
            return [];
        }

        try {
            foreach ($thumbnailConfigs as $thumbType => $config) {
                $thumbWidth = $config['width'];
                $thumbHeight = $config['height'];
                $thumbQuality = $config['quality'];

                // Calculer les dimensions du thumbnail en gardant le ratio
                $thumbDimensions = $this->calculateNewDimensions($width, $height, $thumbWidth, $thumbHeight);

                // Créer le thumbnail
                $thumbnail = imagecreatetruecolor($thumbDimensions['width'], $thumbDimensions['height']);

                // Préserver la transparence pour PNG/WebP
                if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
                    imagealphablending($thumbnail, false);
                    imagesavealpha($thumbnail, true);
                    $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                    imagefill($thumbnail, 0, 0, $transparent);
                }

                // Redimensionner
                imagecopyresampled(
                    $thumbnail, $sourceImage,
                    0, 0, 0, 0,
                    $thumbDimensions['width'], $thumbDimensions['height'],
                    $width, $height
                );

                // Générer le nom du fichier thumbnail
                $fileInfo = pathinfo($filePath);
                $thumbnailFilename = $fileInfo['filename'] . "_{$thumbType}." . $fileInfo['extension'];
                $thumbnailPath = $fileInfo['dirname'] . '/' . $thumbnailFilename;

                // Sauvegarder le thumbnail
                $saveSuccess = match($type) {
                    IMAGETYPE_JPEG => imagejpeg($thumbnail, $thumbnailPath, $thumbQuality),
                    IMAGETYPE_PNG => imagepng($thumbnail, $thumbnailPath, (int)((100 - $thumbQuality) / 10)),
                    IMAGETYPE_WEBP => imagewebp($thumbnail, $thumbnailPath, $thumbQuality),
                    default => false
                };

                if ($saveSuccess) {
                    // Générer l'URL publique du thumbnail
                    $relativePath = str_replace($this->uploadDirectory, '', $thumbnailPath);
                    $thumbnailUrl = '/uploads' . $relativePath;

                    $thumbnails[$thumbType] = $thumbnailUrl;
                    $this->log("Thumbnail {$thumbType} généré: {$thumbnailUrl}");

                    // Définir les permissions du thumbnail
                    chmod($thumbnailPath, 0666);
                } else {
                    $this->log("Erreur lors de la sauvegarde du thumbnail {$thumbType}");
                }

                imagedestroy($thumbnail);
            }

        } catch (\Exception $e) {
            $this->log("Erreur lors de la génération des thumbnails: " . $e->getMessage());
        } finally {
            imagedestroy($sourceImage);
        }

        return $thumbnails;
    }

    /**
     * Nettoie les fichiers (pour rollback)
     */
    private function cleanupFiles(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
}
