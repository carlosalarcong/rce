<?php

declare(strict_types=1);

namespace App\Platform\Identity\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Inicio provisorio: el paso 2 lleva a elegir clínica (ADR 0005). */
final class HomeController extends AbstractController
{
    #[Route('/', name: 'identity_home', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function __invoke(): Response
    {
        return $this->render('identity/home.html.twig');
    }
}
