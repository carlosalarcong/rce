# Clinical

Núcleo clínico compartido: Patient, Encounter, Diagnosis, Prescription, Order, ClinicalDocument.

- Solo depende de `Platform`. **No sabe** que existen la caja, el hospital ni el sector público.
- La ficha no se borra ni se sobrescribe: toda corrección es una enmienda (quién, cuándo, por qué, valor anterior visible).
- Todo acceso y cambio se audita vía `Platform/Audit`.
- Entidades en `Domain/` sin atributos de Doctrine y **no `final`** (Doctrine ORM 3 las necesita para lazy ghosts). El mapeo es XML en `Infrastructure/Doctrine/Mapping/` y se registra en `doctrine.yaml` (ver `docs/adr/0002`).
- Diagnósticos siempre en CIE-10. RUN siempre con el value object `Run`.
- Casos borde obligatorios en los tests.


## DecisionSupport (CDSS)
Apoyo a la decisión clínica: muestra alertas al profesional mientras atiende. **No decide por él.**

- **Es transversal:** corre en cualquier área que use el núcleo (ambulatorio, urgencia, hospitalización, APS). Ninguna área reprograma estas reglas.
- **Reglas generales** viven aquí, en `DecisionSupport/Domain/Rules/`: alergias, interacciones, dosis máximas, duplicidad, signos vitales fuera de rango, recordatorios GES.
- **Reglas particulares** viven en su área (ej. riesgo de caídas en `Inpatient/Nursing`, programas en `PublicSector/PrimaryCare`) e implementan `RuleInterface` de este módulo.
- Prueba para decidir dónde va una regla: si funciona en una consulta ambulatoria simple, es general y va aquí; si necesita datos que solo existen en un área, va en esa área.
- Toda regla implementa `RuleInterface` y se registra con el tag `clinical.decision_rule`. El motor (`Application/EvaluateEncounter`) las encuentra solo; nunca conoce reglas concretas ni áreas.
- Las reglas son lógica de negocio: van en `Domain/`, sin Doctrine ni Symfony. Las reglas y value objects sí pueden ser `final` (no son entidades).
- Se evalúan por eventos: `PrescriptionDrafted` (alergias, interacciones, dosis), `VitalSignsRecorded` (rangos), `EncounterClosed` (GES).
- **Datos de referencia** (interacciones, dosis, rangos por edad, problemas GES) viven en `Platform/Catalog` con versión y fecha. Se consultan por interfaces del dominio (ej. `DrugKnowledgeInterface`); la implementación va en `Infrastructure/`.
- Cada alerta tiene severidad (`INFO`, `WARNING`, `CRITICAL`), mensaje y **fuente con versión**.
- Si el profesional ignora una alerta `WARNING` o `CRITICAL`, se registra el motivo en `Platform/Audit`.
- Pocas reglas y solo las importantes: demasiadas alertas hacen que se ignoren todas.
- Tests obligatorios por regla: caso que dispara, caso que no dispara y casos borde (dosis justo en el límite, alergia por grupo de fármacos).