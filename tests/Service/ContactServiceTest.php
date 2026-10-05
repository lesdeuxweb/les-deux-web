<?php

namespace App\Tests\Service;

use App\Entity\MessageContact;
use App\Service\ContactService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\AbstractLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

class ContactServiceTest extends KernelTestCase
{
    public function testMessageEnregistreEtErreurLogueeSiLEnvoiEchoue(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $mailerEnPanne = new class implements MailerInterface {
            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                throw new TransportException('Serveur SMTP injoignable');
            }
        };

        $logger = new class extends AbstractLogger {
            /** @var list<array{string, string}> */
            public array $journal = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->journal[] = [$level, (string) $message];
            }
        };

        $service = new ContactService($em, $mailerEnPanne, $logger, 'equipe@example.com', 'Site <noreply@example.com>');

        $message = (new MessageContact())
            ->setNom('Jean Martin')
            ->setEmail('jean@example.com')
            ->setMessage('Je souhaite refaire le site de ma boulangerie.');

        $service->traiter($message);

        // Le message est bien en base malgré l'échec des deux envois
        $em->clear();
        self::assertNotNull($message->getId());
        self::assertNotNull($em->getRepository(MessageContact::class)->find($message->getId()));

        // Les deux échecs (notification + accusé) sont logués en erreur
        $erreurs = array_filter($logger->journal, fn (array $ligne) => 'error' === $ligne[0]);
        self::assertCount(2, $erreurs);
    }
}
