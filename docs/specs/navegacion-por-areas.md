# Navegación por áreas y cambio de centro

- **Estado:** propuesta (maqueta aprobada como dirección, sin implementar)
- **Fecha:** 2026-11-15
- **Maqueta:** https://claude.ai/artifact/HaD8bZPPdQjgw5mrxqs7p2 (privada; navegable con Play)

## Objetivo
Cambiar entre Ambulatorio, Urgencia, Hospitalizado, Enfermería, Pabellón y las demás áreas con un clic, sin perderse nunca: siempre se sabe en qué área y en qué centro se está.

## Reglas de la pantalla
1. **Selector de área arriba a la izquierda** (no pestañas). Muestra ícono, nombre y unidad del área actual ("Urgencia · Adultos · Box 1 a 12").
2. Al abrirlo:
   - lista **solo las áreas en que la persona tiene permiso**;
   - cada área muestra su estado sin entrar ("3 críticos", "1 atrasado");
   - atajos: Alt 1 a Alt 5 y Ctrl K para buscar un área o un paciente.
3. **Al entrar se abre la última área usada.** Quien trabaja siempre en la misma no toca el selector.
4. **El área se reconoce por tres señales, nunca solo por el color:** franja de color, ícono y nombre escrito. Indicar algo en el área equivocada es un riesgo clínico.
5. **El esqueleto es el mismo en todas las áreas**; cambia la lista de trabajo con la que abre:

   | Área | Abre en |
   |---|---|
   | Ambulatorio | Agenda de hoy |
   | Urgencia | Tablero por categoría de triage (C1 a C5) y espera |
   | Hospitalizado | Pacientes del servicio por cama, con diagnóstico CIE-10 |
   | Enfermería | Mi turno: medicamentos por hora, atrasados primero |
   | Pabellón | Tabla quirúrgica |

6. **El paciente es uno solo en todas las áreas.** La búsqueda (nombre o RUN) y los "Recientes" funcionan en todas; la ficha es la misma (núcleo `Clinical`).
7. **Las alergias se ven siempre**, en rojo y con texto, junto al nombre del paciente en todas las listas.
8. La acción principal de cada área va en un botón destacado ("Ingresar paciente", "Agendar cita", "Nuevo ingreso").

## Ideas aprobadas (por diseñar)

### A. Borrador automático al cambiar de área
- Si alguien cambia de área (o de centro) con una nota u otro formulario a medio escribir, **se guarda un borrador automático**. No se pregunta ni se bloquea el cambio.
- Al volver, el borrador se ofrece para continuar.
- Por diseñar:
  - dónde vive el borrador (base del tenant, nunca en el navegador, porque tiene datos clínicos);
  - cuánto dura;
  - que un borrador **no es** un registro clínico firmado: no aparece en la ficha hasta que se guarda de verdad.

### B. Un mismo usuario, varios centros (como Acredita360)
- Un profesional asociado a varias clínicas entra **una vez** y cambia de clínica desde el menú del usuario (arriba a la derecha), sin volver a iniciar sesión.
- El centro activo se muestra siempre en la cabecera.
- El cambio de centro guarda borradores (idea A) y abre la última área usada en ese centro.
- **Choca con lo decidido hoy; requiere ADR antes de implementar.** Acredita360 lo resolvió en su D-21 (con D-20):
  - un solo host por producto, en vez de un subdominio por centro;
  - el centro activo sale de un TenantContext validado y guardado en la sesión;
  - un listener antes del firewall despacha `SwitchDbEvent`;
  - el resolver por subdominio de Hakam queda apagado;
  - el usuario de seguridad va atado al centro (se compara en `refreshUser`);
  - identidad única federada (Nexia Core): quién entra a qué centro lo decide la identidad central; qué puede hacer adentro lo decide cada base.

  Adoptarlo en el RCE cambia:
  - `CLAUDE.md` raíz ("el tenant se resuelve por subdominio" y "usuarios del centro en la base del tenant");
  - `config/packages/hakam_multi_tenancy.yaml` (resolver);
  - `Platform/Security` y `Platform/Identity`.

  Todo eso requiere confirmación (CLAUDE.md, regla 4).
- **Ventaja adicional:** con un solo host no se puede averiguar qué clínicas son clientes probando subdominios.

### C. Pantalla de enfermería en tablet, junto a la cama
- Pantalla propia, táctil y de letra grande. No es la de escritorio achicada.
- Uso típico: registrar signos vitales y administración de medicamentos al lado del paciente, con una mano.
- Por diseñar:
  - identificación del paciente antes de administrar (pulsera o código), para evitar errores de paciente;
  - botones grandes (mínimo 44 px, idealmente más);
  - funcionamiento con mala señal.

## Pendiente
- Maqueta de la ficha del paciente vista desde dos áreas distintas.
- Maqueta de la tablet de enfermería (idea C).
- ADR de cambio de centro con un solo usuario (idea B).
