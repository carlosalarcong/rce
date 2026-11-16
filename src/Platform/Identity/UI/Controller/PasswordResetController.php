<?php

declare(strict_types=1);

namespace App\Platform\Identity\UI\Controller;

use App\Platform\Identity\Application\InvalidResetTokenException;
use App\Platform\Identity\Application\RequestPasswordReset;
use App\Platform\Identity\Application\ResetPassword;
use App\Platform\Identity\Application\WeakPasswordException;
use App\Platform\Identity\UI\Form\ForgotPasswordType;
use App\Platform\Identity\UI\Form\ResetPasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * "¿Olvidaste tu contraseña?".
 *
 * - Siempre responde lo mismo, exista o no el correo.
 * - El enlace del correo guarda el token en la sesión y redirige a una URL sin
 *   él: así no queda en el historial ni viaja en el encabezado Referer.
 */
final class PasswordResetController extends AbstractController
{
    private const string SESSION_TOKEN = 'identity.reset_token';

    #[Route('/recuperar-clave', name: 'identity_forgot_password', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        RequestPasswordReset $requestReset,
        #[Target('password_reset_request.limiter')] RateLimiterFactoryInterface $limiter,
    ): Response {
        $form = $this->createForm(ForgotPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{email: string} $data */
            $data = $form->getData();
            $email = mb_strtolower(trim($data['email']));
            // Sin aviso al pasarse del límite: la respuesta es la misma y no se envía otro correo.
            if ($limiter->create($email.'|'.$request->getClientIp())->consume()->isAccepted()) {
                $requestReset($email);
            }

            return $this->redirectToRoute('identity_forgot_password_sent');
        }

        return $this->render('identity/forgot_password.html.twig', ['form' => $form]);
    }

    #[Route('/recuperar-clave/enviado', name: 'identity_forgot_password_sent', methods: ['GET'])]
    public function sent(): Response
    {
        return $this->render('identity/forgot_password_sent.html.twig');
    }

    #[Route('/recuperar-clave/enlace/{token}', name: 'identity_reset_password', requirements: ['token' => '[A-Za-z0-9_-]{32,128}'], methods: ['GET'])]
    public function fromEmail(Request $request, string $token): Response
    {
        $request->getSession()->set(self::SESSION_TOKEN, $token);

        return $this->redirectToRoute('identity_reset_password_form');
    }

    #[Route('/recuperar-clave/nueva', name: 'identity_reset_password_form', methods: ['GET', 'POST'])]
    public function reset(Request $request, ResetPassword $resetPassword): Response
    {
        $session = $request->getSession();
        $token = $session->get(self::SESSION_TOKEN);
        if (!\is_string($token) || !$resetPassword->isValid($token)) {
            $session->remove(self::SESSION_TOKEN);

            return $this->render('identity/reset_password_invalid.html.twig', [], new Response(status: Response::HTTP_GONE));
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{password: string} $data */
            $data = $form->getData();
            try {
                $resetPassword($token, $data['password']);
            } catch (WeakPasswordException $e) {
                $form->get('password')->get('first')->addError(new FormError($e->getMessage()));

                return $this->render('identity/reset_password.html.twig', ['form' => $form], new Response(status: Response::HTTP_UNPROCESSABLE_ENTITY));
            } catch (InvalidResetTokenException) {
                $session->remove(self::SESSION_TOKEN);

                return $this->render('identity/reset_password_invalid.html.twig', [], new Response(status: Response::HTTP_GONE));
            }

            $session->remove(self::SESSION_TOKEN);
            $this->addFlash('success', 'Listo, ya puedes entrar con tu contraseña nueva.');

            return $this->redirectToRoute('identity_login');
        }

        return $this->render('identity/reset_password.html.twig', ['form' => $form]);
    }
}
