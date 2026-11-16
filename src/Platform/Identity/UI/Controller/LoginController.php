<?php

declare(strict_types=1);

namespace App\Platform\Identity\UI\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/** La pantalla. El POST lo atiende form_login de Symfony (security.yaml). */
final class LoginController extends AbstractController
{
    #[Route('/ingresar', name: 'identity_login', methods: ['GET', 'POST'])]
    public function __invoke(AuthenticationUtils $authentication): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('identity_home');
        }

        $error = $authentication->getLastAuthenticationError();

        return $this->render('identity/login.html.twig', [
            'last_email' => $authentication->getLastUsername(),
            'error' => null === $error ? null : self::message($error),
        ]);
    }

    /** El mismo mensaje para correo inexistente y contraseña incorrecta: no se revela quién tiene cuenta. */
    private static function message(AuthenticationException $error): string
    {
        return match (true) {
            $error instanceof TooManyLoginAttemptsAuthenticationException => 'Demasiados intentos. Espera unos minutos y vuelve a intentarlo.',
            $error instanceof CustomUserMessageAccountStatusException => $error->getMessageKey(),
            $error instanceof InvalidCsrfTokenException => 'La página estuvo abierta mucho tiempo. Vuelve a intentarlo.',
            default => 'Correo o contraseña incorrectos.',
        };
    }
}
