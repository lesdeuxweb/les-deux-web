<?php

namespace App\Tests\Command;

use App\Entity\MessageContact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PurgerMessagesCommandTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->tester = new CommandTester((new Application(self::bootKernel()))->find('app:purger-messages'));
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->creerMessage('Ancien', '-3 years -1 day');
        $this->creerMessage('Presque trois ans', '-3 years +1 day');
        $this->creerMessage('Récent', '-1 week');
        $this->em->flush();
    }

    public function testSupprimeUniquementLesMessagesDePlusDeTroisAns(): void
    {
        $this->tester->execute([]);

        $this->tester->assertCommandIsSuccessful();
        self::assertStringContainsString('1 message(s)', $this->tester->getDisplay());
        self::assertSame(['Presque trois ans', 'Récent'], $this->nomsRestants());
    }

    public function testSimulationNeSupprimeRien(): void
    {
        $this->tester->execute(['--simulation' => true]);

        self::assertStringContainsString('1 message(s)', $this->tester->getDisplay());
        self::assertCount(3, $this->nomsRestants());
    }

    private function creerMessage(string $nom, string $anciennete): void
    {
        $message = (new MessageContact())->setNom($nom)->setEmail('test@example.com')->setMessage('Un message de test.');
        // createdAt n'a pas de setter (renseigné à l'enregistrement) : on le force pour le test
        (new \ReflectionProperty(MessageContact::class, 'createdAt'))->setValue($message, new \DateTimeImmutable($anciennete));
        $this->em->persist($message);
    }

    /** @return list<string> */
    private function nomsRestants(): array
    {
        $this->em->clear();
        $noms = array_map(fn (MessageContact $m) => $m->getNom(), $this->em->getRepository(MessageContact::class)->findAll());
        sort($noms);

        return $noms;
    }
}
