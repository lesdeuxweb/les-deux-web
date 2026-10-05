<?php

namespace App\Tests\Service;

use App\Service\ImageOptimiseur;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageOptimiseurTest extends TestCase
{
    public function testConvertitEnWebpEtRedimensionne(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'jpg_');
        $image = imagecreatetruecolor(3000, 2000);
        imagefill($image, 0, 0, imagecolorallocate($image, 46, 94, 78));
        imagejpeg($image, $source, 95);

        $resultat = (new ImageOptimiseur())->versWebp(new UploadedFile($source, 'Photo Chantier.JPG', 'image/jpeg', null, true));

        self::assertSame('image/webp', $resultat->getMimeType());
        self::assertSame('Photo Chantier.webp', $resultat->getClientOriginalName());
        [$largeur, $hauteur] = getimagesize($resultat->getPathname());
        self::assertSame(1600, $largeur);
        self::assertEqualsWithDelta(1067, $hauteur, 1, 'Proportions conservées (3:2).');
    }

    public function testPetiteImageNonAgrandie(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'png_');
        imagepng(imagecreatetruecolor(800, 500), $source);

        $resultat = (new ImageOptimiseur())->versWebp(new UploadedFile($source, 'logo.png', 'image/png', null, true));

        self::assertSame([800, 500], \array_slice(getimagesize($resultat->getPathname()), 0, 2));
    }

    public function testFichierNonImageInchange(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'txt_');
        file_put_contents($source, 'pas une image');
        $fichier = new UploadedFile($source, 'notes.txt', 'text/plain', null, true);

        self::assertSame($fichier, (new ImageOptimiseur())->versWebp($fichier));
    }
}
