# Mapa de módulos del RCE

Copia en texto del whiteboard [Mapa de módulos RCE](https://claude.ai/artifact/SMhQcqUSiPsa6q5dSFwuPu), al 16-11-2026. El JSON completo (para restaurarlo o verlo con detalle) está en `mapa-de-modulos.json`.

El **% entre corchetes** es el avance real en el código a esa fecha. El detalle de cómo se midió está en `como-funciona-el-rce.md`, sección 6.

## Base transversal · la usa todo el sistema (abajo: capa de integración)

- **Usuarios y roles** [15 %]
  Permisos por perfil
- **Auditoría** [0 %]
  Quién vio o cambió qué
- **Seguridad** [20 %]
  Cifrado · Ley 21.719
- **Interoperabilidad** [0 %]
  API FHIR R4
- **Reportes** [0 %]
  Gestión y producción
- **Multi-sede** [25 %]
  Clínicas y sucursales
- **HAPI FHIR** [0 %]
  Servidor FHIR R4 · MINSAL
- **Cola con reintentos** [80 %]
  Symfony Messenger + outbox
- **Visor de mensajes** [10 %]
  Reprocesar · alertas
- **OIE (Mirth gratis)** [0 %]
  HL7 v2: labs, equipos, legacy

## Núcleo clínico compartido · lo usan todas las áreas

- **Paciente** [0 %]
  RUN, antecedentes, alergias
- **Evolución clínica** [0 %]
  Anamnesis, examen, notas
- **Diagnósticos** [0 %]
  CIE-10 · SNOMED CT
- **Receta e indicaciones** [0 %]
  CENS Pharma
- **Exámenes** [0 %]
  Órdenes y resultados
- **Documentos** [0 %]
  Consentimiento · LME

## PRIVADO

## PÚBLICO · se suma a la base y al núcleo

### Ambulatorio · caja propia

- **Atención** [5 %]
  - Agenda y reserva online
  - Recordatorios WhatsApp/email
  - Telemedicina (sello CENS)
- **Caja ambulatoria** [20 %]
  - Admisión, previsión, convenios
  - Bonos IMED y copago
  - Honorarios y boleta SII

### Ambulatorio (APS) · casi sin caja

- **Modelo APS** [0 %]
  - Población inscrita (per cápita)
  - Salud familiar y visitas
  - Programas: PSCV, ECICEP…
- **Reportes y red** [0 %]
  - REM mensual · vacunas RNI
  - Interconsultas a hospital
  - Modo offline (zonas rurales)

### Hospitalario · otra caja

- **Compartido hospitalario** (lo usan las 4 áreas de abajo) [0 %]
  - Admisión hospitalaria · camas y censo
  - Cuenta paciente: caja hospitalaria, PAM, programa médico
  - Farmacia e insumos cargados al paciente
- **Urgencia** [0 %]
  - Triage (ESI)
  - DAU
  - Alta o paso a hospitalización
- **Pabellón** [0 %]
  - Tabla quirúrgica
  - Pausa de seguridad (OMS)
  - Protocolo operatorio y anestesia
- **Hospitalización** [0 %]
  - Ingreso y visita médica
  - Indicaciones diarias
  - Epicrisis y alta
- **Enfermería** [0 %]
  - Plan de cuidados
  - Administración de medicamentos
  - Signos vitales y escalas de riesgo

### Hospitalario público

- **Compartido hospitalario público** (lo usan las 4 áreas) [0 %]
  - Gestión de camas en red
  - Codificación GRD de egresos
  - Recaudación: Isapre y particulares · farmacia y abastecimiento
- **Urgencia** [0 %]
  - Ley de urgencia
  - REM de urgencia
  - Derivación en la red
- **Pabellón** [0 %]
  - Lista de espera quirúrgica
  - Garantías GES (SIGGES)
- **Hospitalización** [0 %]
  - Epicrisis para GRD
  - Traslados entre hospitales
- **Enfermería** [0 %]
  - Indicadores y REM
  - Protocolos MINSAL

## Plan en horas · Dev A: 1 → 3 → 4 · Dev B: 1 → 2 → 5 · juntos: 6

- **1 · Fundaciones**
  **146 a 210 h**
  - Cuentas + doble factor
  - Auditoría, cifrado, respaldos
  - Catálogos: CIE-10, aranceles
  - Adjuntos cifrados
  - Monitoreo (Sentry)
  - Alta del centro por script
- **2 · Paciente y agenda**
  **136 a 150 h**
  - Ficha del paciente (RUN)
  - Antecedentes y alergias con alerta visible
  - Agenda interna
  - Recepción y sala de espera
  - Recordatorios por email
- **3 · Atención clínica**
  **224 a 236 h**
  - Evolución y signos vitales
  - CIE-10 y lista de problemas
  - Historial en línea de tiempo
  - Correcciones sin borrar
  - Med. general + 1 especialidad
  - Consentimiento informado
- **4 · Receta y exámenes**
  **104 a 108 h**
  - Receta (CENS Pharma)
  - Órdenes; resultados como PDF
  - Certificados
  - PDF con firma simple
- **5 · Caja**
  **120 a 160 h**
  - Admisión y convenios: 40–60 h
  - Bonos con folio manual: 20–30 h
  - Boleta vía facturador: 20–30 h
  - Cierre de caja y caja diaria: 40 h
  - Export de producción
- **6 · Pulido y pruebas**
  **100 h**
  - Tests en cada bloque, no al final
  - Ajustes de UX y velocidad
  - Listo para el piloto
- **En paralelo · dominio y trámites (Álvaro)**
  - Pauta del sello RCE y licencia CENS Pharma (semana 1)
  - Validar plantillas clínicas con médicos (meses 1 y 2)
  - Convenio IMED y facturador electrónico (meses 1 a 3)
  - Términos, privacidad, DPA y hosting en Chile (mes 2)
  - Conseguir el centro piloto (desde el mes 2)
- **Riesgos que pueden mover las fechas**
  - Sin margen: si algo se atrasa, certificados o correcciones pasan a las semanas 1–2 del piloto
  - Convenios IMED o facturador que tardan
  - Cambios del piloto que obligan a rehacer flujos
  - Si el segundo dev no es full-time: +30 a 50%

## Fase 2 · Lo que salió del MVP · 432 a 610 h · 1,4 a 1,9 meses (durante el piloto)

- **Medios de pago**
  **90 a 140 h** · 0,3 a 0,4 meses
  - IMED (bono electrónico): 60–90 h
  - Transbank Webpay: 30–50 h
- **Honorarios**
  **40 a 50 h** · 0,1 a 0,2 meses
  - Liquidación por profesional
- **WhatsApp**
  **20 a 30 h** · 0,1 meses
  - Recordatorios con plantillas aprobadas por Meta
- **Más especialidades**
  **40 a 60 h** · 0,1 a 0,2 meses
  - Plantillas según los clientes del piloto
- **Firma y visor**
  **40 a 60 h** · 0,1 a 0,2 meses
  - Firma electrónica avanzada: 20–30 h
  - Visor de mensajes: 20–30 h
- **Agenda y exámenes**
  **60 h** · 0,2 meses
  - Reserva online pública: 30 h
  - Resultados estructurados: 30 h
- **Datos y ventas**
  **28 a 46 h** · 0,1 meses
  - Importar pacientes (CSV): 20–30 h
  - Demo con datos ficticios: 8–16 h
- **Ficha avanzada**
  **36 a 60 h** · 0,1 a 0,2 meses
  - Interconsulta interna: 12–20 h
  - Copia de ficha al paciente: 12–20 h
  - Acceso restringido: 12–20 h
- **Telemedicina**
  **40 a 60 h** · 0,1 a 0,2 meses
  - Videollamada vía proveedor
  - Pensada para el sello CENS
- **Admin y FHIR**
  **38 a 44 h** · 0,1 meses
  - Pantalla de alta del centro: 18–24 h
  - API FHIR: 20 h

## Fase 3 · Hospitalario privado · 1.040 a 1.360 h · 3,3 a 4,3 meses

- **Compartido hospitalario**
  **320 a 400 h** · 1 a 1,3 meses
  - Admisión hospitalaria: 70–90 h
  - Camas y censo: 80–100 h
  - Cuenta paciente y PAM: 110–130 h
  - Farmacia e insumos: 60–80 h
- **Urgencia**
  **180 a 240 h** · 0,6 a 0,8 meses
  - Admisión y triage ESI: 50–70 h
  - DAU y atención: 70–90 h
  - Tablero de boxes: 30–40 h
  - Alta o traspaso: 30–40 h
- **Pabellón**
  **200 a 260 h** · 0,6 a 0,8 meses
  - Tabla quirúrgica: 60–80 h
  - Pausa de seguridad: 30–40 h
  - Protocolo operatorio: 50–60 h
  - Hoja de anestesia: 40–50 h
  - Recuperación: 20–30 h
- **Hospitalización**
  **160 a 220 h** · 0,5 a 0,7 meses
  - Ingreso médico: 40–50 h
  - Visita y evolución diaria: 40–60 h
  - Indicaciones médicas: 40–60 h
  - Epicrisis y alta: 40–50 h
- **Enfermería**
  **180 a 240 h** · 0,6 a 0,8 meses
  - Plan de cuidados: 50–60 h
  - Administración de medicamentos: 60–80 h
  - Signos vitales y balance: 40–60 h
  - Escalas de riesgo: 30–40 h

## Fase 4 · Capa pública · 1.080 a 1.600 h · 3,4 a 5 meses (dominio menos conocido: más margen)

- **Levantamiento**
  **80 a 120 h** · 0,3 a 0,4 meses
  - Entrevistas con equipos APS: 40–60 h
  - Revisar REM y normas técnicas: 40–60 h
- **Modelo APS**
  **320 a 480 h** · 1 a 1,5 meses
  - Población inscrita y per cápita: 80–120 h
  - Sectores y familias: 60–90 h
  - Programas (PSCV, ECICEP…): 120–180 h
  - Visitas domiciliarias: 60–90 h
- **REM y vacunas**
  **180 a 260 h** · 0,6 a 0,8 meses
  - Extracción de datos REM: 100–140 h
  - Validación y envío: 40–60 h
  - Registro de vacunas RNI: 40–60 h
- **GES y lista de espera**
  **140 a 220 h** · 0,4 a 0,7 meses
  - Notificación GES: 60–90 h
  - Envío a SIGGES: 40–70 h
  - Lista de espera: 40–60 h
- **Modo offline**
  **200 a 280 h** · 0,6 a 0,9 meses
  - Almacenamiento local: 60–80 h
  - Sincronización: 80–120 h
  - Conflictos y seguridad: 60–80 h
- **Hospital público**
  **160 a 240 h** · 0,5 a 0,8 meses
  - Codificación GRD: 60–90 h
  - Camas en red: 50–70 h
  - Recaudación (Isapre, particulares): 50–80 h

## Fase 5 · Integraciones · según demanda · 300 a 500 h · 0,9 a 1,6 meses

- **Montar OIE**
  **40 a 80 h** · 0,1 a 0,25 meses
  - Docker y TLS: 16–30 h
  - Monitoreo y alertas: 12–25 h
  - Respaldos: 12–25 h
- **Primer laboratorio**
  **40 a 80 h** · 0,1 a 0,25 meses
  - Órdenes de salida (ORM): 16–30 h
  - Resultados de entrada (ORU): 16–30 h
  - Pruebas con el lab: 8–20 h
  - Cada canal nuevo: 16–40 h
- **Receta MINSAL**
  **80 a 120 h** · 0,25 a 0,4 meses
  - Mapeo a la guía FHIR: 30–40 h
  - Envío y estados: 30–50 h
  - Pruebas de certificación: 20–30 h
- **Licencia médica**
  **60 a 100 h** · 0,2 a 0,3 meses
  - Integración con operador: 30–50 h
  - Formulario y validaciones: 20–30 h
  - Pruebas: 10–20 h
- **Imágenes**
  **80 a 120 h** · 0,25 a 0,4 meses
  - Órdenes a PACS: 30–40 h
  - Visor DICOM web: 30–50 h
  - Informes en la ficha: 20–30 h

## Textos del plan

- Fase 1 · MVP privado ambulatorio
- 830 a 964 horas de desarrollo (2 personas): 2,6 a 3 meses full-time, sin margen
- Después del MVP
- Sistema completo: 3.682 a 5.034 h · 11,5 a 15,5 meses (2 devs full-time = 320 h/mes)
