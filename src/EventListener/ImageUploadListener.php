<?php

namespace App\EventListener;

use App\Service\ImageOptimiseur;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Vich\UploaderBundle\Event\Event;
use Vich\UploaderBundle\Event\Events;

/**
 * Avant chaque upload Vich, remplace l'image envoyée par sa version WebP redimensionnée.
 * Vich calcule ensuite le nom du fichier et ses dimensions à partir du WebP.
 */
#[AsEventListener(event: Events::PRE_UPLOAD)]
class ImageUploadListener
{
    public function __construct(private readonly ImageOptimiseur $optimiseur)
    {
    }

    public function __invoke(Event $event): void
    {
        $objet = $event->getObject();
        $mapping = $event->getMapping();
        $fichier = $mapping->getFile($objet);

        if (null === $fichier || !str_starts_with((string) $fichier->getMimeType(), 'image/')) {
            return;
        }

        $mapping->setFile($objet, $this->optimiseur->versWebp($fichier));
    }
}
