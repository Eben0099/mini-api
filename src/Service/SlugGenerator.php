<?php

namespace App\Service;

use App\Repository\SalonRepository;

class SlugGenerator
{
    private SalonRepository $salonRepository;

    public function __construct(SalonRepository $salonRepository)
    {
        $this->salonRepository = $salonRepository;
    }

    /**
     * Génère un slug unique à partir d'un nom
     */
    public function generateUniqueSlug(string $name): string
    {
        $baseSlug = $this->slugify($name);
        $slug = $baseSlug;
        $counter = 1;

        // Vérifier l'unicité et ajouter un numéro si nécessaire
        while ($this->salonRepository->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Génère un slug à partir d'un nom (sans vérification d'unicité)
     */
    public function slugify(string $text): string
    {
        // Convertir en minuscules
        $text = strtolower($text);

        // Remplacer les caractères accentués
        $text = $this->removeAccents($text);

        // Remplacer les espaces et caractères spéciaux par des tirets
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        // Supprimer les tirets multiples et les tirets au début/fin
        $text = trim($text, '-');
        $text = preg_replace('/-+/', '-', $text);

        return $text;
    }

    /**
     * Supprime les accents des caractères
     */
    private function removeAccents(string $text): string
    {
        $search = ['à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ'];
        $replace = ['a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y'];

        return str_replace($search, $replace, $text);
    }
}
