<?php

namespace App\Service;

use App\Entity\MessageContact;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Traitement d'une demande de contact valide :
 *   1. enregistrement en base (en premier : un prospect n'est jamais perdu) ;
 *   2. notification à l'équipe (CONTACT_EMAIL_TO) ;
 *   3. accusé de réception au prospect.
 *
 * Un échec d'envoi est logué mais n'interrompt pas le traitement.
 */
class ContactService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'CONTACT_EMAIL_TO')]
        private readonly string $destinataire,
        #[Autowire(env: 'MAILER_FROM')]
        private readonly string $expediteur,
    ) {
    }

    public function traiter(MessageContact $message): void
    {
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->envoyer(
            (new TemplatedEmail())
                ->from(Address::create($this->expediteur))
                ->to($this->destinataire)
                ->replyTo(new Address($message->getEmail(), $message->getNom()))
                ->subject(sprintf('Nouvelle demande de contact : %s', $message->getNom()))
                ->htmlTemplate('emails/contact_notification.html.twig')
                ->textTemplate('emails/contact_notification.txt.twig')
                ->context(['contact' => $message]),
            $message,
            'notification',
        );

        // Accusé de réception volontairement générique : il ne reprend aucun texte saisi,
        // pour que le formulaire ne puisse pas servir à envoyer du contenu à un tiers.
        $this->envoyer(
            (new TemplatedEmail())
                ->from(Address::create($this->expediteur))
                ->to($message->getEmail())
                ->subject('Nous avons bien reçu votre demande')
                ->htmlTemplate('emails/contact_accuse.html.twig')
                ->textTemplate('emails/contact_accuse.txt.twig'),
            $message,
            'accusé de réception',
        );
    }

    private function envoyer(TemplatedEmail $email, MessageContact $message, string $type): void
    {
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Échec de l\'envoi de l\'email de {type} pour le message de contact #{id}.', [
                'type' => $type,
                'id' => $message->getId(),
                'exception' => $e,
            ]);
        }
    }
}
