<?php

namespace App\Twig;

use App\Repository\SecteurRepository;
use App\Repository\ZoneRepository;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

/**
 * Fonctions Twig liées aux contenus :
 *   - |contenu_html : affiche du HTML saisi en back-office, assaini ;
 *   - secteurs_publies() / zones_publiees() : maillage interne (pied de page, accueil).
 */
class ContenuExtension
{
    /** @var array<string, array<object>> Cache par requête : le pied de page est rendu sur chaque page. */
    private array $cache = [];

    public function __construct(
        #[Target('app.contenu_sanitizer')]
        private readonly HtmlSanitizerInterface $sanitizer,
        private readonly SecteurRepository $secteurRepository,
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    /**
     * Assainit le HTML puis rétrograde les <h1> en <h2> : l'éditeur du back-office (Trix)
     * produit des <h1>, or la page en a déjà un (un seul <h1> par page).
     */
    #[AsTwigFilter('contenu_html', isSafe: ['html'])]
    public function contenuHtml(?string $html): string
    {
        if (null === $html || '' === trim($html)) {
            return '';
        }

        $propre = $this->sanitizer->sanitize($html);

        return preg_replace('#<(/?)h1(?=[\s>])#i', '<$1h2', $propre);
    }

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

    /** @return \App\Entity\Secteur[] */
    #[AsTwigFunction('secteurs_publies')]
    public function secteursPublies(): array
    {
        return $this->cache['secteurs'] ??= $this->secteurRepository->findPublie();
    }

    /** @return \App\Entity\Zone[] */
    #[AsTwigFunction('zones_publiees')]
    public function zonesPubliees(): array
    {
        return $this->cache['zones'] ??= $this->zoneRepository->findPublie();
    }
}
