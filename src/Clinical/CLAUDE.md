# Clinical

Núcleo clínico compartido: Patient, Encounter, Diagnosis, Prescription, Order, ClinicalDocument.

- Solo depende de `Platform`. **No sabe** que existen la caja, el hospital ni el sector público.
- La ficha no se borra ni se sobrescribe: toda corrección es una enmienda (quién, cuándo, por qué, valor anterior visible).
- Todo acceso y cambio se audita vía `Platform/Audit`.
- Entidades en `Domain/` sin atributos de Doctrine y **no `final`** (Doctrine ORM 3 las necesita para lazy ghosts). El mapeo es XML en `Infrastructure/Doctrine/Mapping/` y se registra en `doctrine.yaml` (ver `docs/adr/0002`).
- Diagnósticos siempre en CIE-10. RUN siempre con el value object `Run`.
- Casos borde obligatorios en los tests.
