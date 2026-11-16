# Adapter/Imed — Bono electrónico IMED

**Estado:** sin implementar. Se espera el kit de integración de IMED. Ya está analizado cómo lo hace `melisa_prod`.

- **Contrato:** `VoucherProviderInterface`. Probablemente haya que ajustarlo: ver "Impacto en el contrato".
- **Nombre:** `imed`. **Tag:** `#[AutoconfigureTag('payment.voucher_provider')]`.

## Documentación
- **No hay documentación pública.** La integración se pide a IMED con convenio, que entrega el kit, el WSDL y las credenciales.
- `ayuda.imed.cl` solo publica requisitos de red.

## Cómo lo hace melisa_prod (analizado el 2026-11-15)
Código legacy, que funciona pero sin patrón. Análisis completo en `melisa_prod/docs/negocio/caja/CAJA-06-imed.md` y en `RecaudacionBundle/.../Imed/ImedController.php`. **Validar todo contra el kit oficial.**

**Transporte:** SOAP con WSDL (`WsInterfaz.php?wsdl`). Las credenciales `CodUsuario` y `CodClave` van como campos de cada operación, sin certificados. En melisa la URL es `http://` sin TLS: **exigir HTTPS a IMED**.

**Datos por centro:**
- `CodLugar`: lugar de atención, uno por sucursal.
- `RutConvenio`: RUT del prestador, con "00" delante.
- `CodFinanciador`: código IMED de cada previsión (Fonasa, cada Isapre).
- Códigos IMED de prestación (pueden variar por previsión) y de especialidad.

**Flujo** (se parece a Webpay: redirigir y después consultar):
1. **`VtaBonInterMul`**: inicia la venta con el convenio, el profesional tratante, el beneficiario, el cajero y las prestaciones (código, ítem, cantidad, monto), más las URL de éxito y error. Devuelve `NumTransac` y `CodAuditoria`, o `CodError` = 1 con `GloError`.
2. **Ventana de IMED:** se abre con `NroAuditoria` y `NumTransac`. **Allí IMED identifica al beneficiario** (huella u otro método), confirma y cobra el copago. El RCE no ve nada de eso.
3. **Consulta del resultado:** `VerResulEmi` y `ObtBonInterMul`.
   - `CodEstado`: 1 = en proceso, 2 = emitido, 3 = emitido con advertencia, 0 y 4 = error. Confirmar el significado oficial.
   - `ObtBonInterMul` devuelve los bonos, uno o varios folios:
     - por bono: `FolioBono`, `MontoTotal`;
     - por prestación: `MontoBon` (bonificación), `MontoCopago`, `EsGes` y `CodPatologia`/`CodCanasta`;
     - seguros complementarios (`ListaOtrasBon`) y formas de pago (`ListaForPag`; `CodForPag` 6 = excedente).
4. **`AnulBonInterfaz`:** anula un folio (se llama una vez por cada folio).

**Errores de melisa que NO hay que repetir:**
- Estado guardado en la sesión PHP, sin llave de idempotencia (un doble clic crea otra venta).
- Consulta cada 5 s sin límite y sin timeout.
- Bonos grabados en cada consulta, antes de ver el estado (duplicados).
- Una anulación que falla por red se marca como exitosa. Con varios folios, una falla a medias deja todo inconsistente.
- Montos y folio tomados del formulario del navegador.
- La clave de IMED copiada en cada fila de la transacción y guardada en texto plano en `parametro`.
- Páginas de retorno con XSS y creación de archivos con nombre controlado por quien envía el POST.
- Excepciones SOAP sin capturar (error 500).

## Impacto en el contrato (decidir al implementar; cambia `Contract/`)
Hoy `VoucherProviderInterface` supone `validate()` → `issueVoucher()` → `cancel()` en el servidor. IMED funciona de otra forma:
- inicia la venta y redirige a su ventana;
- después se consulta el resultado, que puede quedar "en proceso";
- puede entregar varios folios por venta;
- no hay un `validate()` separado.

Probablemente convenga un flujo como el de pagos (`start` → `PaymentNextStep::Redirect` → `confirm`, que puede quedar `Pending`) y un resultado con varios folios, cada uno con bonificación, copago, seguro complementario y marca GES.

## Reglas
- Credenciales por centro con `ProviderCredentialsInterface`, cifradas. Nunca en logs ni copiadas en cada transacción.
- **Datos sensibles:** viajan el RUN, la previsión y las prestaciones del paciente. Nunca en logs ni en mensajes de error.
- Montos y folios **solo** desde la respuesta de IMED, nunca desde el navegador.
- Anulación: si falla, el bono sigue vigente. Se reintenta y se alerta; nunca se marca como anulado.
- **Tests obligatorios:** camino feliz con uno y con varios folios, en proceso, error de IMED, timeout, consulta repetida (no duplica), anulación fallida (no se marca como anulado), anulación parcial con varios folios.
