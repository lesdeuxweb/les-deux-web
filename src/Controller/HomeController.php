<?php

namespace App\Controller;

use App\Entity\MessageContact;
use App\Entity\Offre;
use App\Form\ContactType;
use App\Repository\OffreRepository;
use App\Repository\OptionTarifaireRepository;
use App\Service\ContactService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Site en une page : toutes les sections sont sur l'accueil, y compris le formulaire de contact.
 */
class HomeController extends AbstractController
{
    /**
     * Accueil + traitement du formulaire de contact (Post/Redirect/Get vers /#contact).
     * ?offre={slug} pré-sélectionne une offre publiée dans le formulaire.
     */
    #[Route('/', name: 'app_home', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        OffreRepository $offreRepository,
        OptionTarifaireRepository $optionRepository,
        ContactService $contactService,
        RateLimiterFactoryInterface $contactLimiter,
        TranslatorInterface $translator,
        LoggerInterface $logger,
    ): Response {
        $message = new MessageContact();
        if ($slug = $request->query->getString('offre')) {
            $message->setOffre($offreRepository->findOnePublieBySlug($slug));
        }

        $form = $this->createForm(ContactType::class, $message, [
            'action' => $this->generateUrl('app_home').'#contact',
        ]);
        $form->handleRequest($request);
        $statut = Response::HTTP_OK;

        if ($form->isSubmitted()) {
            // Honeypot rempli : robot. Rejet silencieux, en simulant un succès.
            if ('' !== trim((string) $form->get(ContactType::CHAMP_PIEGE)->getData())) {
                $logger->info('Formulaire de contact : envoi rejeté (honeypot rempli).', ['ip' => $request->getClientIp()]);

                return $this->confirmer();
            }

            if ($form->isValid()) {
                // Seuls les envois valides sont comptés : corriger une erreur de saisie ne bloque pas.
                if ($contactLimiter->create($request->getClientIp())->consume()->isAccepted()) {
                    $contactService->traiter($message);

                    return $this->confirmer();
                }

                $form->addError(new FormError($translator->trans('contact.trop_d_envois')));
                $statut = Response::HTTP_TOO_MANY_REQUESTS;
            } else {
                $statut = Response::HTTP_UNPROCESSABLE_ENTITY;
            }
        }

        return $this->render('home/index.html.twig', [
            // Section offres : 3 cartes, puis grille complète dépliable (autres offres, options, abonnements)
            'cartes' => $offreRepository->findPublieesParCategorieEtCarte(Offre::CATEGORIE_CREATION, true),
            'autres_creations' => $offreRepository->findPublieesParCategorieEtCarte(Offre::CATEGORIE_CREATION, false),
            'abonnements' => $offreRepository->findPublieesParCategorie(Offre::CATEGORIE_ABONNEMENT),
            'options' => $optionRepository->findPublie(),
            'form' => $form,
        ], new Response(status: $statut));
    }

    /** Ancienne adresse de la page Contact : redirige vers le formulaire de l'accueil. */
    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function contact(Request $request): Response
    {
        return $this->redirectToRoute('app_home', [
            ...$request->query->all(),
            '_fragment' => 'contact',
        ], Response::HTTP_MOVED_PERMANENTLY);
    }

    private function confirmer(): Response
    {
        $this->addFlash('succes', 'contact.succes');

        return $this->redirectToRoute('app_home', ['_fragment' => 'contact'], Response::HTTP_SEE_OTHER);
    }
}
