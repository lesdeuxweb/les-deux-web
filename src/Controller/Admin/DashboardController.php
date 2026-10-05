<?php

namespace App\Controller\Admin;

use App\Entity\MessageContact;
use App\Entity\Offre;
use App\Entity\Realisation;
use App\Entity\Secteur;
use App\Entity\Zone;
use App\Repository\MessageContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly MessageContactRepository $messageContactRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly Packages $assets,
    ) {
    }

    public function index(): Response
    {
        $compteurs = [];
        foreach ([
            'Offres publiées' => [Offre::class, OffreCrudController::class],
            'Réalisations publiées' => [Realisation::class, RealisationCrudController::class],
            'Secteurs publiés' => [Secteur::class, SecteurCrudController::class],
            'Zones publiées' => [Zone::class, ZoneCrudController::class],
        ] as $libelle => [$entite, $crud]) {
            $repository = $this->entityManager->getRepository($entite);
            $compteurs[] = [
                'libelle' => $libelle,
                'publies' => $repository->count(['publie' => true]),
                'total' => $repository->count([]),
                'url' => $this->urlCrud($crud),
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'messages_non_traites' => $this->messageContactRepository->countNonTraites(),
            'url_messages_non_traites' => $this->urlCrud(MessageContactCrudController::class, [
                'filters' => ['traite' => ['comparison' => '=', 'value' => '0']],
            ]),
            'url_tous_messages' => $this->urlCrud(MessageContactCrudController::class),
            'url_nouvelle_realisation' => $this->urlCrud(RealisationCrudController::class, action: Action::NEW),
            'url_nouvelle_offre' => $this->urlCrud(OffreCrudController::class, action: Action::NEW),
            'compteurs' => $compteurs,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle(sprintf(
                '<span class="admin-logo"><img src="%s" width="36" height="36" alt=""><img src="%s" width="65" height="32" alt="Les deux web"></span>',
                $this->assets->getUrl('images/logo/embleme-96.webp'),
                $this->assets->getUrl('images/logo/texte-96.webp'),
            ))
            ->setFaviconPath('images/logo/favicon-48.png')
            ->setLocales(['fr'])
            ->disableDarkMode();
    }

    public function configureAssets(): Assets
    {
        return Assets::new()->addCssFile('styles/admin.css');
    }

    public function configureMenuItems(): iterable
    {
        $nonTraites = $this->messageContactRepository->countNonTraites();

        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-gauge');

        yield MenuItem::section('Demandes');
        yield MenuItem::linkTo(MessageContactCrudController::class, 'Messages', 'fa fa-envelope')
            ->setBadge($nonTraites > 0 ? $nonTraites : false, 'danger');

        yield MenuItem::section('Contenus du site');
        yield MenuItem::linkTo(OffreCrudController::class, 'Offres', 'fa fa-tags');
        yield MenuItem::linkTo(OptionTarifaireCrudController::class, 'Options en supplément', 'fa fa-puzzle-piece');
        yield MenuItem::linkTo(RealisationCrudController::class, 'Réalisations', 'fa fa-images');
        yield MenuItem::linkTo(SecteurCrudController::class, 'Secteurs (métiers)', 'fa fa-briefcase');
        yield MenuItem::linkTo(ZoneCrudController::class, 'Zones d\'intervention', 'fa fa-map-location-dot');

        yield MenuItem::section();
        yield MenuItem::linkToRoute('Voir le site', 'fa fa-arrow-up-right-from-square', 'app_home');
    }

    /** @param array<string, mixed> $parametres */
    private function urlCrud(string $crud, array $parametres = [], string $action = Action::INDEX): string
    {
        return $this->adminUrlGenerator
            ->unsetAll()
            ->setController($crud)
            ->setAction($action)
            ->setAll($parametres)
            ->generateUrl();
    }
}
