<?php

namespace App\Tests\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminCommandTest extends KernelTestCase
{
    private CommandTester $tester;

    protected function setUp(): void
    {
        $application = new Application(self::bootKernel());
        $this->tester = new CommandTester($application->find('app:create-admin'));
    }

    public function testCreeUnAdministrateur(): void
    {
        $this->tester->setInputs(['motdepasse-solide', 'motdepasse-solide']);
        $this->tester->execute(['email' => 'Contact@LesDeuxWeb.fr']);

        $this->tester->assertCommandIsSuccessful();

        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'contact@lesdeuxweb.fr']);
        self::assertInstanceOf(User::class, $user);
        self::assertContains('ROLE_ADMIN', $user->getRoles());

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user, 'motdepasse-solide'));
    }

    public function testRefuseUnEmailInvalide(): void
    {
        $this->tester->execute(['email' => 'pas-un-email']);

        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('n\'est pas valide', $this->tester->getDisplay());
    }

    public function testRefuseUnMotDePasseTropCourt(): void
    {
        $this->tester->setInputs(['court']);
        $this->tester->execute(['email' => 'admin@example.com']);

        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('au moins 12 caractères', $this->tester->getDisplay());
    }

    public function testRefuseUneConfirmationDifferente(): void
    {
        $this->tester->setInputs(['motdepasse-solide', 'autre-motdepasse']);
        $this->tester->execute(['email' => 'admin@example.com']);

        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('ne correspondent pas', $this->tester->getDisplay());
    }

    public function testRefuseUnEmailDejaUtilise(): void
    {
        $this->tester->setInputs(['motdepasse-solide', 'motdepasse-solide']);
        $this->tester->execute(['email' => 'admin@example.com']);
        $this->tester->assertCommandIsSuccessful();

        $this->tester->execute(['email' => 'admin@example.com']);

        self::assertSame(Command::FAILURE, $this->tester->getStatusCode());
        self::assertStringContainsString('existe déjà', $this->tester->getDisplay());
    }
}
