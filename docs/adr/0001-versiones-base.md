# 0001 — Versiones base: PHP 8.5 y Symfony 7.4 LTS

- **Estado:** aceptada
- **Fecha:** 2026-11-15

## Contexto
En noviembre de 2026 la última versión estable de Symfony es la 8.1 y la LTS vigente es la 7.4. En PHP, la última estable es la 8.5 (la 8.6 está en release candidate).

La multi-tenancy depende de `hakam/multi-tenancy-bundle`. Su última versión (v3.1.0, abril 2026) exige:
- `symfony/dependency-injection: ^6.4|^7.0` (no soporta Symfony 8),
- `doctrine/doctrine-bundle: ^2.7` (no soporta DoctrineBundle 3),
- `doctrine/doctrine-migrations-bundle: ^3.0` (no soporta MigrationsBundle 4).

## Decisión
- **PHP 8.5** (`config.platform.php = 8.5.0`). Symfony 7.4 y Hakam lo soportan, y tiene soporte de seguridad hasta fines de 2029.
- **Symfony 7.4 LTS** (mantenimiento hasta 11/2028, fin de vida 11/2029). Para un RCE con retención de 15 años conviene una LTS.
- **DoctrineBundle 2.x y DoctrineMigrationsBundle 3.x** fijados en `composer.json`, por exigencia de Hakam. Doctrine ORM 3.
- PostgreSQL 18. Docker con nginx + php-fpm.

## Consecuencias
- No podemos subir a Symfony 8 ni a DoctrineBundle 3 hasta que Hakam los soporte. Revisar sus versiones en cada release de Symfony.
- Si Hakam se estanca, evaluar un fork o reemplazarlo. El costo baja si mantenemos el uso del bundle encapsulado en `Platform/Tenancy` y en la configuración.
