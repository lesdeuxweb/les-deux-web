<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Convertit une image (JPEG, PNG, WebP) en WebP redimensionné, avec GD.
 * Une photo de téléphone de plusieurs Mo devient une image d'environ 100 à 300 Ko.
 *
 * Si GD ne gère pas le WebP ou si l'image est illisible, le fichier d'origine est conservé.
 */
class ImageOptimiseur
{
    public const LARGEUR_MAX = 1600;
    public const QUALITE = 80;

    /**
     * @return File Le fichier WebP (temporaire), ou le fichier d'origine si la conversion est impossible
     */
    public function versWebp(File $fichier, int $largeurMax = self::LARGEUR_MAX): File
    {
        if (!\function_exists('imagewebp') || !($image = $this->charger($fichier))) {
            return $fichier;
        }

        $image = $this->corrigerOrientation($image, $fichier);

        if (imagesx($image) > $largeurMax) {
            $image = imagescale($image, $largeurMax) ?: $image;
        }

        $chemin = tempnam(sys_get_temp_dir(), 'webp_');
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        if (!imagewebp($image, $chemin, self::QUALITE)) {
            return $fichier;
        }

        $nomOrigine = $fichier instanceof UploadedFile ? $fichier->getClientOriginalName() : $fichier->getFilename();

        // test: true : fichier déjà sur le disque, pas issu d'un vrai upload HTTP
        return new UploadedFile($chemin, pathinfo($nomOrigine, \PATHINFO_FILENAME).'.webp', 'image/webp', null, true);
    }

    private function charger(File $fichier): \GdImage|false
    {
        return match ($fichier->getMimeType()) {
            'image/jpeg' => @imagecreatefromjpeg($fichier->getPathname()),
            'image/png' => @imagecreatefrompng($fichier->getPathname()),
            'image/webp' => @imagecreatefromwebp($fichier->getPathname()),
            default => false,
        };
    }

    /** Les photos de téléphone sont souvent stockées « couchées » avec une indication EXIF. */
    private function corrigerOrientation(\GdImage $image, File $fichier): \GdImage
    {
        if ('image/jpeg' !== $fichier->getMimeType() || !\function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data($fichier->getPathname())['Orientation'] ?? 1;

        return match ($orientation) {
            3 => imagerotate($image, 180, 0) ?: $image,
            6 => imagerotate($image, -90, 0) ?: $image,
            8 => imagerotate($image, 90, 0) ?: $image,
            default => $image,
        };
    }
}
