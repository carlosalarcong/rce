# Platform/Identity — Cuentas e inicio de sesión

Decisión completa en `docs/adr/0005`. La cuenta es **de la persona**, no de una clínica: vive en la base **Main** (EM por defecto). A qué clínicas entra (membresías) y qué puede hacer en cada una llegan en los pasos siguientes.

## Reglas
- La cuenta se identifica por su **UUID** (v7). El correo solo sirve para entrar y para escribirle; **nunca identifica** ni se usa como llave en otras tablas.
- Contraseñas:
  - siempre cifradas (argon2id, `security.yaml` en "auto");
  - mínimo 12 caracteres (`Application/PasswordPolicy`);
  - nunca en logs, mensajes ni la línea de comandos (`#[\SensitiveParameter]`).
- Inicio de sesión (`form_login`):
  - el mismo mensaje para correo inexistente y contraseña incorrecta;
  - la cuenta suspendida se revisa **después** de la contraseña (`AccountUserChecker::checkPostAuth`), para no revelar qué correos existen;
  - freno de 5 intentos cada 15 minutos por correo e IP, en Valkey.
- "Salir" es POST con token CSRF (`/salir`); por GET responde 405.
- **CSRF clásico, guardado en la sesión** (`config/packages/csrf.yaml`): funciona sin JavaScript. No volver a la variante "sin estado" de la receta.
- **Sesiones en Valkey** (`RedisSessionHandler`, prefijo `rce:sesion:`). En el usuario de la sesión se guarda solo una huella de la contraseña cifrada (`SecurityUser::__serialize`): cuando la contraseña cambia, Symfony cierra las demás sesiones.
- "¿Olvidaste tu contraseña?":
  - responde siempre lo mismo, exista o no el correo;
  - solo las cuentas activas reciben enlace;
  - el enlace dura 30 minutos, sirve una vez y solo el último vale;
  - en la base se guarda solo su huella SHA-256;
  - al abrirlo, el token pasa a la sesión y la URL queda sin él;
  - freno: 3 correos por hora por correo e IP.
- Dominio sin Symfony ni Doctrine (`Domain/`); mapeo XML en `Infrastructure/Doctrine/Mapping`, registrado en `doctrine.yaml` (`PlatformIdentityMain`). Entidades no `final`.

## Piezas
| Pieza | Qué hace |
|---|---|
| `Domain/UserAccount`, `EmailAddress`, `AccountStatus`, `PasswordResetToken` | La cuenta y el enlace de recuperación |
| `Application/CreateAccount`, `RequestPasswordReset`, `ResetPassword` | Casos de uso |
| `Infrastructure/Security/*` | `SecurityUser`, `AccountUserProvider`, `AccountUserChecker`, `RecordLastLogin` y el cifrado |
| `Infrastructure/Mail/TwigPasswordResetMailer` | Correo de recuperación (`MAILER_FROM`) |
| `UI/Controller/*` | `/ingresar`, `/salir`, `/recuperar-clave/*` y el inicio provisorio `/` |
| `UI/Console/CreateAccountCommand` | `make cuenta EMAIL=…` (pide la contraseña sin mostrarla) |

## Desarrollo
- `make cuenta EMAIL=tu@correo.cl` crea tu cuenta.
- Los correos los atrapa Mailpit: `http://localhost:8025`.
- Hasta el paso 2 de la ADR 0005 (resolver por subdominio de Hakam apagado), se entra por `http://rce.localhost:8180`: con `app.` Hakam busca una clínica llamada "app".
- Tests contra la base `rce_main_test` (`make test-db`, lo corre `make test`). Las pruebas de pantalla usan una IP al azar, para que los frenos de intentos (en Valkey) no se mezclen entre pruebas.

## Pendiente (siguientes pasos de la ADR 0005)
Membresías y clínica activa, perfiles y permisos, panel de soporte, invitaciones, segundo factor y auditoría de cada ingreso (`Platform/Audit`).
