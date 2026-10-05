<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages légales : mentions légales et politique de confidentialité.
 */
class PageController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal', methods: ['GET'])]
    public function legal(): Response
    {
        return $this->render('page/legal.html.twig');
    }

    #[Route('/confidentialite', name: 'app_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('page/privacy.html.twig');
    }
}
