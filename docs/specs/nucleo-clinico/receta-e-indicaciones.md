# Receta e indicaciones

- **Módulo:** `Clinical/Prescription`
- **Cobertura de la NT 231 (NT820):** **ninguna.** La norma no estandariza medicamentos, dosis ni recetas. Solo los nombra entre los "datos clínicos básicos" (indicaciones de tratamiento, p. 174).
- **Estado:** borrador, 2026-11-17

## Lo que sí viene de la NT820 (de otros capítulos)

| Dato de la receta | De dónde sale | Archivo |
|---|---|---|
| Identificación del paciente (nombre, nombre social, RUN u otro identificador, edad) | Cap. II | [paciente.md](paciente.md) |
| Alergias (alerta visible en la receta) | Cap. V 2.2, anamnesis remota | [paciente.md](paciente.md#8-antecedentes-y-alergias) |
| Profesional que prescribe: identificación, título, especialidad, registro (RNPI) | Cap. III 4.1-4.2 (p. 114-131) | Vive en `Platform/Identity`, no aquí |
| Establecimiento: código DEIS, dirección | Cap. III 4.4 (p. 152-156) | Vive en `Platform/Tenancy` |
| Diagnóstico asociado | Cap. V 3 | [diagnosticos.md](diagnosticos.md) |

## Exigencias legales (verificadas)

Detalle y fuentes en [marco-legal-nucleo-clinico.md](../../normas/marco-legal-nucleo-clinico.md), sección 5.

- **Contenido mínimo** (DS 466 art. 38):
  - profesional: nombre, RUN, profesión, domicilio y número de registro en la Superintendencia ("REG-SIS Nº");
  - paciente: nombre, RUN y domicilio;
  - medicamento: nombre del producto y su **DCI** si es distinta, dosis, forma farmacéutica, vía, posología con intervalo y período de tratamiento;
  - otras indicaciones, nombre, firma y fecha.
- **Tipos:** receta simple, retenida y cheque, según la condición de venta que fija el **registro sanitario de cada producto** en el ISP; y receta magistral (fórmula para un paciente).
- **Controlados** (estupefacientes y psicotrópicos): **un producto por receta**, cantidades en letras y números, sin enmiendas.
- **Receta cheque:** formularios oficiales en papel que entrega el Servicio de Salud al médico. **El RCE no la emite**; puede registrar la indicación y el folio.
- **Vigencia:** retenida y cheque, 30 días. Receta simple: sin plazo general encontrado.
- **Tratamiento crónico:** la receta indica el período total o la repetición periódica.
- **Receta electrónica:** basta firma electrónica avanzada **o** un sistema con validación del prescriptor y **Clave Única** (DS 466 art. 38, desde nov. 2025). El paciente siempre puede pedirla en papel. Las recetas impresas van foliadas y timbradas o "validadas mediante sistema electrónico".
- **Sistema nacional de receta electrónica (SNRE)**: existe desde dic. 2025, con FHIR; no se encontró obligación de integrarse.

## Por diseñar

1. **Catálogo de medicamentos** con DCI, presentación, forma, vía y **condición de venta**. Elegir fuente (ISP, TFC/CENS Pharma u otra).
2. **Posología estructurada:** dosis, unidad, vía, frecuencia, duración y cantidad total.
3. **Tipo de receta deducido del medicamento**, y separación automática: un controlado por receta.
4. **Receta magistral:** componentes por DCI o nombre químico.
5. **Folio, vigencia, anulación** (nunca se edita una receta emitida) y **receta crónica**.
6. **Firma:** Clave Única con validación del prescriptor, o firma avanzada. Verificar el registro del profesional en la Superintendencia.
7. **Impresión** con los datos de ley y un código para verificarla.
8. **Alertas al recetar:** alergias, interacciones, dosis máximas, duplicidad, embarazo y lactancia.
9. **Integración con el SNRE** (fase posterior).
10. **Indicaciones no farmacológicas** (reposo, dieta): ¿van en la receta o en la evolución?
