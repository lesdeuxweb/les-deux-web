<?php

namespace App\Controller;

use App\Entity\MessageContact;
use App\Form\ContactType;
use App\Repository\OffreRepository;
use App\Service\ContactService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactController extends AbstractController
{
    /**
     * Formulaire de contact (Post/Redirect/Get).
     * ?offre={slug} pré-sélectionne une offre publiée dans la liste déroulante.
     */
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        OffreRepository $offreRepository,
        ContactService $contactService,
        RateLimiterFactoryInterface $contactLimiter,
        TranslatorInterface $translator,
        LoggerInterface $logger,
    ): Response {
        $message = new MessageContact();
        if ($slug = $request->query->getString('offre')) {
            $message->setOffre($offreRepository->findOnePublieBySlug($slug));
        }

        $form = $this->createForm(ContactType::class, $message);
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

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ], new Response(status: $statut));
    }

    private function confirmer(): Response
    {
        $this->addFlash('succes', 'contact.succes');

        return $this->redirectToRoute('app_contact', ['_fragment' => 'contact'], Response::HTTP_SEE_OTHER);
    }
}
