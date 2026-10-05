<?php

namespace App\Command;

use App\Entity\MessageContact;
use App\Repository\MessageContactRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Supprime les messages de contact plus anciens que la durée de conservation (3 ans),
 * comme annoncé dans la politique de confidentialité.
 * À lancer chaque jour par une tâche planifiée (cron OVH) : voir le README.
 */
#[AsCommand(
    name: 'app:purger-messages',
    description: 'Supprime les messages de contact de plus de 3 ans (RGPD)',
)]
class PurgerMessagesCommand extends Command
{
    public function __construct(private readonly MessageContactRepository $repository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('simulation', null, InputOption::VALUE_NONE, 'Affiche le nombre de messages concernés sans rien supprimer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limite = new \DateTimeImmutable('-'.MessageContact::DUREE_CONSERVATION);

        if ($input->getOption('simulation')) {
            $io->note(sprintf(
                '%d message(s) antérieur(s) au %s seraient supprimés.',
                $this->repository->countAnterieursA($limite),
                $limite->format('d/m/Y'),
            ));

            return Command::SUCCESS;
        }

        $supprimes = $this->repository->supprimerAnterieursA($limite);
        $io->success(sprintf('%d message(s) antérieur(s) au %s supprimé(s).', $supprimes, $limite->format('d/m/Y')));

        return Command::SUCCESS;
    }
}
