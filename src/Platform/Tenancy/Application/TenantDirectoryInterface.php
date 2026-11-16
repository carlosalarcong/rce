<?php

declare(strict_types=1);

namespace App\Platform\Tenancy\Application;

/** Lista de tenants registrados en Main, para tareas que recorren todas las clínicas. */
interface TenantDirectoryInterface
{
    /** @return list<string> identificadores de tenant */
    public function identifiers(): array;
}
