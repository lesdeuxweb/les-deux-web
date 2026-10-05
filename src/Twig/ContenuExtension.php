<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;

/**
 * Fonctions Twig liées aux contenus.
 */
class ContenuExtension
{
    /**
     * Prépare un texte pour une meta description : sans HTML, espaces normalisés,
     * coupé sur un mot à $max caractères au plus (points de suspension compris).
     */
    #[AsTwigFilter('resume_meta')]
    public function resumeMeta(?string $texte, int $max = 160): string
    {
        $texte = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $texte), \ENT_QUOTES | \ENT_HTML5, 'UTF-8')));

        if (mb_strlen($texte) <= $max) {
            return $texte;
        }

        $coupe = mb_substr($texte, 0, $max - 1);
        $dernierEspace = mb_strrpos($coupe, ' ');

        return rtrim(false !== $dernierEspace ? mb_substr($coupe, 0, $dernierEspace) : $coupe, ' ,;:.').'…';
    }
}
