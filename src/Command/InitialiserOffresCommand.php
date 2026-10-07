<?php

namespace App\Command;

use App\Data\GrilleTarifaire;
use App\Entity\Offre;
use App\Entity\OptionTarifaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Remplit la base avec la grille tarifaire de lancement (mise en ligne).
 * Ne fait rien si des offres ou des options existent déjà : on ne risque pas
 * d'écraser ce qui a été modifié dans l'admin.
 */
#[AsCommand(
    name: 'app:initialiser-offres',
    description: 'Crée les offres, packs et options de la grille tarifaire de lancement (base vide uniquement)',
)]
class InitialiserOffresCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $existantes = $this->entityManager->getRepository(Offre::class)->count([])
            + $this->entityManager->getRepository(OptionTarifaire::class)->count([]);

        if ($existantes > 0) {
            $io->warning('La base contient déjà des offres ou des options : rien n\'a été modifié. Utilisez l\'admin pour les changer.');

            return Command::SUCCESS;
        }

        $entites = GrilleTarifaire::creerEntites();
        foreach ($entites as $entite) {
            $this->entityManager->persist($entite);
        }
        $this->entityManager->flush();

        $io->success(sprintf('%d offres et options créées.', \count($entites)));

        return Command::SUCCESS;
    }
}
