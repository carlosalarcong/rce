# Adapter/Imed — Bono electrónico IMED

**Estado:** implementado (`ImedGateway`) **a partir de melisa_prod, no del kit oficial**. Está **inactivo**: antes de activarlo hay que validarlo con el kit y con el ambiente de pruebas de IMED.

- **Contrato:** `VoucherProviderInterface` (`startVoucherSale` → ventana de IMED → `confirmVoucherSale` → `cancel`).
- **Nombre:** `imed`. **Tag:** `#[AutoconfigureTag('payment.voucher_provider')]`.
- **Transporte:** SOAP, con la extensión `soap` de PHP (en el `Dockerfile`). `NativeImedSoapClientFactory` usa timeout `IMED_TIMEOUT_SECONDS` (10 s) y convierte todo fault o error de red en `ProviderUnavailableException`.

## Documentación
- **No hay documentación pública.** IMED entrega con el convenio el kit, el WSDL y las credenciales.
- Referencia usada: `melisa_prod`, en `RecaudacionBundle/.../Imed/ImedController.php` y `docs/negocio/caja/CAJA-06-imed.md`. Es código legacy que funciona, pero sin patrón.

## Configuración por centro (`ProviderCredentialsInterface`, clave `imed`)
| Clave | Qué es | En IMED |
|---|---|---|
| `username`, `password` | credenciales del centro | `CodUsuario`, `CodClave` |
| `wsdl_url` | servicio SOAP | `WsInterfaz.php?wsdl` |
| `window_url` | ventana donde el paciente se identifica y paga | `login.php` |
| `place_code` | lugar de atención | `CodLugar` |
| `agreement_run` | RUT del prestador en convenio | `RutConvenio` |

Quien llama entrega ya traducidos el código IMED de la previsión (`CodFinanciador`), de la especialidad y de cada prestación. Esa traducción es del negocio (catálogos), no de este adaptador.

## Flujo
1. **`startVoucherSale()`** llama `VtaBonInterMul` y recibe la transacción (`NumTransac`, puede venir más de una) y la auditoría (`CodAuditoria`). Devuelve `Redirect` a `window_url` con `NroAuditoria` y `NumTransac`. La referencia queda como `"NumTransac|CodAuditoria"`. Si IMED responde `CodError` 1, la venta vuelve **rechazada con su motivo**, sin excepción.
2. **Ventana de IMED:** el paciente se identifica (huella u otro método) y paga el copago. El RCE no ve nada de eso.
3. **`confirmVoucherSale()`** llama `VerResulEmi`:
   - `CodEstado` 1 (en proceso), 3 (con advertencia) o desconocido → `Pending`;
   - 0 o 4 → `Rejected`, con el motivo;
   - 2 → llama `ObtBonInterMul` y devuelve los bonos.

   Cada bono trae folio y total, y cada prestación su bonificación, copago, seguro complementario y marca GES. El excedente es la forma de pago 6. **No graba nada**: se puede repetir sin duplicar.
4. **`cancel()`** llama `AnulBonInterfaz` con un folio por llamada:
   - `CodError` 0 → anulado;
   - `CodError` 1 → `cancelled: false`, con el motivo;
   - sin respuesta o con error de red → excepción. **Nunca se da por anulado sin certeza.**

## Pendientes antes de activar
- **Validar con el kit:**
  - nombres y anidación de los campos;
  - formato de los RUN (se rellenan con ceros a 12 caracteres, `ImedRun`);
  - significado de `CodEstado` 3 (hoy queda pendiente);
  - formato de `FecEmi` (se asume AAAAMMDD);
  - honorarios y pabellón (`CodItem` 8, 1 y 2; melisa manda `MtoTotal` en vez de `MontoPrest`);
  - tipo de tratamiento y fechas de tratamiento (hoy fijos: 1 y `19000101`).
- **Puerto 80 y DMZ.** IMED atiende por HTTP plano (puerto 80/8080, sin TLS), así que usuario y clave viajan legibles. En producción la salida a IMED va por una **DMZ o proxy de salida** con IP fija, y solo esa máquina sale por ese puerto.
  - Se puede simular en local con Docker: una red interna sin salida a internet para `php`, más un contenedor proxy (por ejemplo, Squid) que es el único con salida y solo permite el host de IMED. Un IMED simulado (WSDL más respuestas fijas) en otro contenedor permite probar de punta a punta sin el IMED real. Va con su ADR cuando se haga.
  - Exigir a IMED un canal cifrado si lo ofrece.
- Página de retorno desde la ventana de IMED: toca `Platform/Security` y requiere confirmación.

## Errores de melisa_prod que este adaptador evita
- **Idempotencia:** la referencia vive en el intento, no en la sesión PHP.
- **Confirmar sin efectos:** consultar no graba bonos (melisa los duplicaba en cada consulta).
- **Montos y folios** solo desde la respuesta de IMED, nunca desde el navegador.
- **Anulación:** una anulación sin respuesta o con error de red nunca se marca como anulada.
- **Errores de red:** los faults SOAP se capturan (en melisa daban error 500), con timeout corto y sin consultas infinitas, porque el intento expira.
- **Credenciales:** van por centro y cifradas, nunca copiadas en cada transacción.
- **Datos para auditoría:** sin RUN ni datos del paciente; solo transacción, estado, folios y glosas.

## Tests
`tests/Unit/Integration/Payments/Adapter/Imed/`, con IMED simulado (`FakeImedSoap`): inicio, rechazo al iniciar, varias transacciones, en proceso, con advertencia, error, uno y varios folios (seguro, excedente, GES), consulta repetida, emitido pero ilegible, anulación OK, rechazada, sin respuesta y con error de red, formato de RUN, WSDL inalcanzable.
