# Flujo de correos — Plantillas, cola y procesamiento

Este documento describe cómo el sistema envía correos: renderizado de plantillas de la tabla `email_templates` (editables desde el panel admin), encolado en `email_queue` (el envío **no bloquea** la petición del usuario) y procesamiento en segundo plano vía cron con reintentos.

**Archivos involucrados:**

| Capa | Archivo | Responsabilidad |
|---|---|---|
| Servicio | `controller/emails.php` (`MailService::sendMail`, `MailService::dispatchMail`, `interpolate`, `sendTemplateByKey`, funciones `send*` por evento) | Render de plantillas y encolado |
| Modelo | `model/EmailsModel.php` (`getMailTemplateByKey`) | Lectura de plantillas |
| Procesador | `controller/process_email_queue.php` | Cron que envía la cola por SMTP con reintentos |
| Editor | Panel admin de plantillas (editor con vista previa) | Edición del asunto/cuerpo de cada `tkey` |
| Estilos | `view/assets/css/email_templates/email.css` | CSS inyectado como variable `{{{css}}}` |
| Config | `.env` (`SMTP_*`, `FROM_EMAIL`, `FROM_NAME`, `EMAIL_QUEUE_SECRET`) | Credenciales SMTP y token del cron |

**Tablas:** `email_queue` (to_email, subject, body, plain_text, from_name, attachments, status, attempts), `email_templates` (plantillas por `tkey`).

> **Catálogo completo de correos** (destinatario, momento de envío y texto): `docs/mails/practicas_profesionales.md` (41 correos) y `docs/mails/servicio_social.md`.

---

## 1. Vista general

```mermaid
flowchart LR
    A([Acción de negocio<br/>ej. aprobar asistencia]) --> B["Función send*() en emails.php"]
    B --> C[sendTemplateByKey:<br/>carga plantilla por tkey]
    C --> D[interpolate: rellena variables]
    D --> E[(INSERT en email_queue<br/>status = pending)]
    E --> F([Respuesta inmediata al usuario<br/>sin esperar SMTP])
    G([Cron cada N minutos]) --> H[process_email_queue.php]
    H --> I[(Lee lote pendiente)]
    I --> J[Envío SMTP PHPMailer]
    J --> K[(status = sent / failed)]
```

---

## 2. Renderizado de plantillas

Las plantillas viven en `email_templates` y se editan desde el panel admin (editor con vista previa). Los placeholders admiten dos sintaxis:

- `{{variable}}` → se reemplaza **escapando HTML** (`htmlspecialchars`).
- `{{{variable}}}` → se reemplaza **sin escape** (para HTML como el CSS o bloques enriquecidos).

```mermaid
flowchart TD
    A["Llamada sendTemplateByKey(tkey, destinatario, vars)"] --> B[(SELECT plantilla por tkey<br/>EmailsModel::getMailTemplateByKey)]
    B --> C{¿Existe la plantilla?}
    C -- No --> ERR["error_log y return false<br/>(el correo no se envía)"]
    C -- Sí --> D[Cargar email.css e inyectarlo<br/>como variable css]
    D --> E["interpolate: {{{var}}} sin escape,<br/>luego {{var}} escapado"]
    E --> F[Generar versión texto plano]
    F --> G[MailService::sendMail]
```

> **Nota de mantenimiento:** el editor de plantillas construye su vista previa con `buildEmailDoc()`, que replica el wrapper HTML del mailer. Si cambia el layout en `emails.php`, hay que actualizar también la vista previa para que no diverjan.

---

## 3. Encolado (sendMail)

`MailService::sendMail` **no envía**: inserta en `email_queue` y retorna `'ok'` de inmediato. Solo si el INSERT falla intenta el envío directo como respaldo.

```mermaid
flowchart TD
    A[MailService::sendMail] --> B[Filtrar adjuntos:<br/>solo rutas de archivo existentes]
    B --> C[(INSERT email_queue<br/>status = pending, attempts = 0)]
    C --> D{¿INSERT exitoso?}
    D -- Sí --> E([Retorna ok inmediato<br/>el envío queda en cola])
    D -- No --> F[Fallback: dispatchMail<br/>envío SMTP directo]
    F --> G{¿SMTP exitoso?}
    G -- Sí --> E
    G -- No --> H([error_log y return false])
```

---

## 4. Procesamiento de la cola (cron)

`process_email_queue.php` se ejecuta por cron (CLI o HTTP con token `EMAIL_QUEUE_SECRET`, comparado con `hash_equals`). Marca el lote de forma **atómica** (incrementa `attempts` antes de leer) para evitar dobles envíos si dos procesadores corren a la vez.

```mermaid
flowchart TD
    A([Cron invoca process_email_queue.php]) --> B{¿CLI o token válido?<br/>hash_equals EMAIL_QUEUE_SECRET}
    B -- No --> ERR[HTTP 403]
    B -- Sí --> C[(UPDATE atómico:<br/>attempts + 1 al lote pending<br/>con attempts < MAX_ATTEMPTS)]
    C --> D[(SELECT exactamente los<br/>registros recién marcados)]
    D --> E[Por cada correo:<br/>dispatchMail via PHPMailer SMTP]
    E --> F{¿Envío exitoso?}
    F -- Sí --> G[(status = sent, sent_at = NOW)]
    F -- No --> H{¿attempts ≥ MAX_ATTEMPTS?}
    H -- No --> I[(status = pending:<br/>se reintenta en el siguiente cron)]
    H -- Sí --> J[(status = failed + error_log)]
    G --> K([Fin del lote])
    I --> K
    J --> K
```

---

## 5. Estados de un correo en cola

```mermaid
stateDiagram-v2
    [*] --> pending : sendMail encola
    pending --> sent : SMTP exitoso
    pending --> pending : Falla con attempts < MAX (reintento)
    pending --> failed : Falla con attempts = MAX
    sent --> [*]
    failed --> [*] : Requiere revisión manual (error_log)
```

---

## 6. Observaciones

- **SEC-018:** el token del cron es estático y viaja en la URL en modo HTTP; si los logs del cron son visibles, queda expuesto. Preferir la invocación CLI.
- Los correos `failed` no se re-encolan automáticamente; hay que revisarlos en la tabla.
- Los adjuntos se guardan como rutas JSON; si el archivo se borra antes de que el cron procese el correo, el adjunto simplemente se omite.

---

## 7. Referencias cruzadas

- Catálogo de correos PP: `docs/mails/practicas_profesionales.md`
- Catálogo de correos SS: `docs/mails/servicio_social.md`
- Flujos que disparan correos: `docs/flujo_postulacion_alumnos.md`, `docs/flujo_asistencias_alumnos.md`, `docs/flujo_reportes_evaluaciones_practicas.md`, `docs/flujo_vacantes_practicantes.md`
