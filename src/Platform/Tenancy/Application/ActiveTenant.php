<?php

declare(strict_types=1);

namespace App\Platform\Tenancy\Application;

use Hakam\MultiTenancyBundle\Event\SwitchDbEvent;
use Hakam\MultiTenancyBundle\Event\TenantSwitchedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Sigue a qué tenant está conectado Hakam (TenantSwitchedEvent) y cambia de
 * tenant con SwitchDbEvent. No decide QUIÉN puede entrar a un tenant: eso es
 * de quien llama (sesión validada, tarea firmada, comando de operación).
 */
#[AsAlias(ActiveTenantInterface::class)]
final class ActiveTenant implements ActiveTenantInterface, ResetInterface
{
    private ?string $identifier = null;

    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function identifier(): ?string
    {
        return $this->identifier;
    }

    public function switchTo(string $identifier): void
    {
        if ('' === $identifier) {
            throw new \InvalidArgumentException('Tenant identifier cannot be empty.');
        }

        $this->dispatcher->dispatch(new SwitchDbEvent($identifier));
        // Hakam no avisa si ya estaba conectado a ese tenant: se fija aquí también.
        $this->identifier = $identifier;
    }

    #[AsEventListener]
    public function onTenantSwitched(TenantSwitchedEvent $event): void
    {
        $identifier = $event->getTenantIdentifier();
        $this->identifier = \is_scalar($identifier) ? (string) $identifier : null;
    }

    public function reset(): void
    {
        $this->identifier = null;
    }
}
