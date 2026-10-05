<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Crée un compte administrateur pour le back-office.
 * Le mot de passe est demandé en saisie masquée, jamais passé en argument
 * (il resterait sinon dans l'historique du terminal).
 */
#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un compte administrateur (ROLE_ADMIN) pour le back-office',
)]
class CreateAdminCommand extends Command
{
    public const MOT_DE_PASSE_LONGUEUR_MIN = 12;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::OPTIONAL, 'Adresse email de l\'administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email') ?? $io->ask('Adresse email');
        $email = mb_strtolower(trim((string) $email));

        if (\count($this->validator->validate($email, [new Assert\NotBlank(), new Assert\Email()])) > 0) {
            $io->error(sprintf('L\'adresse « %s » n\'est pas valide.', $email));

            return Command::FAILURE;
        }

        if (null !== $this->userRepository->findOneBy(['email' => $email])) {
            $io->error(sprintf('Un compte existe déjà pour « %s ».', $email));

            return Command::FAILURE;
        }

        $motDePasse = $io->askQuestion(
            (new Question(sprintf('Mot de passe (%d caractères minimum)', self::MOT_DE_PASSE_LONGUEUR_MIN)))->setHidden(true)
        );

        if (mb_strlen((string) $motDePasse) < self::MOT_DE_PASSE_LONGUEUR_MIN) {
            $io->error(sprintf('Le mot de passe doit contenir au moins %d caractères.', self::MOT_DE_PASSE_LONGUEUR_MIN));

            return Command::FAILURE;
        }

        $confirmation = $io->askQuestion((new Question('Confirmez le mot de passe'))->setHidden(true));

        if ($confirmation !== $motDePasse) {
            $io->error('Les deux mots de passe ne correspondent pas.');

            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($email)
            ->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $motDePasse));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Administrateur « %s » créé.', $email));

        return Command::SUCCESS;
    }
}
