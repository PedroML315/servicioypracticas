# Flujo de vacantes de practicantes (Organismo externo)

Este documento describe el ciclo de vida de una vacante (solicitud de practicantes): creación desde el wizard del organismo, verificación de bloqueo por strikes, guardado con habilidades del perfil, gestión master-detail, edición, eliminación y expiración.

**Archivos involucrados:**

| Capa | Archivo | Responsabilidad |
|---|---|---|
| Vista | `view/pages/practicas/modalSolPracticantes.php` | Modales: gestor master-detail, wizard "Nueva Vacante" (4 pasos), edición, candidatos, reportes |
| JS | `view/assets/js/organismo/solicitudes.js` | Wizard, selector de habilidades, horario (+4h automática), AJAX y render de lista/detalle |
| Router organismo | `controller/organismo/forms.php` (cases `solicitarPracticas`, `getSolicitudes`, `getSolicitudById`, `updateSolicitud`, `deleteSolicitud`, `getHabilidadesCatalogo`) | Valida sesión, bloqueo por strikes, sanitiza habilidades, verifica propiedad |
| Controlador | `controller/forms.controller.php` (`PracticasController::solicitarPracticas`, `getSolicitudesPracticas`, `updateSolicitudPractica`, `deleteSolicitudPractica`) | Orquesta modelo + correos + notificaciones |
| Modelo | `model/PracticasModel.php` (`mdlSolicitarPracticas`, `mdlSaveSolicitudHabilidades`, `mdlGetSolicitudesPracticas`, `mdlGetProspectsBySolicitud`) | Persistencia |
| Admin | `controller/ajax/ajax.forms.php` (search=`practices`) | Aceptar/rechazar la vacante (`aceptado = 1`) |
| Correos | `controller/emails.php` (`sendSolicitudPracticas`) | Aviso al admin de nueva solicitud (via `email_queue`) |

**Tablas:** `solicitudes_practicantes`, `solicitudes_habilidades` (relación vacante↔habilidad), catálogo de habilidades, `organismos_externos`, `students_in_practices` (prospectos), `notifications`, `email_queue`.

> **Nota de alcance (2026):** el perfil de la vacante se define por **habilidades** (nuevo modelo). El campo `licenciatura` queda como legado para vacantes antiguas; el frontend muestra habilidades cuando existen y licenciatura como respaldo.

---

## 1. Vista general

```mermaid
flowchart LR
    A([Organismo crea vacante<br/>wizard 4 pasos]) --> B[(Vacante aceptado = 0<br/>pendiente)]
    B --> C{Admin de prácticas}
    C -- Acepta --> D[(aceptado = 1<br/>vacante activa)]
    C -- Rechaza --> E[(Vacante rechazada)]
    D --> F[Alumnos PP se postulan<br/>ver flujo_postulacion_alumnos.md]
    D --> G{¿fecha_limite vencida?}
    G -- Sí --> H[Deja de mostrarse<br/>filtro en frontend]
    D -- deleteSolicitud --> I[(Soft delete activo = 0)]
```

---

## 2. Creación — Wizard "Nueva Vacante" (4 pasos)

El wizard valida con HTML5 por paso y exige **al menos una habilidad** (paso 1). El horario propone jornadas Lun–Vie de 4 horas: la hora de salida se calcula automáticamente (+4h). Al enviar, el AJAX agrega `action=solicitarPracticas`.

Pasos del wizard:

1. **Perfil del Estudiante** — habilidades (chips con catálogo + personalizadas), número de vacantes, aptitudes deseadas.
2. **Plan Formativo** — actividades, funciones, objetivos, competencias, resultados esperados.
3. **Condiciones y Horario** — modalidad, fecha límite, apoyo económico (monto condicional), horario con salida automática.
4. **Sede y Responsable** — dirección, nombre y teléfono del responsable.

```mermaid
flowchart TD
    subgraph FE ["Frontend — modalSolPracticantes.php + solicitudes.js"]
        A([Clic en Nueva Vacante]) --> B[Wizard paso actual 1 a 4]
        B --> C{¿Inputs required válidos<br/>y al menos 1 habilidad en paso 1?}
        C -- No --> B
        C -- "Sí, faltan pasos" --> B
        C -- "Sí, paso 4" --> D[AJAX POST FormData<br/>action = solicitarPracticas<br/>habilidades como JSON]
    end

    subgraph BE ["Backend PHP — controller/organismo/forms.php"]
        D --> E[session_start + switch action]
        E --> F[(mdlGetExternals:<br/>leer organismo)]
        F --> G{¿solicitudes_bloqueadas = 1?<br/>Fase 3 strikes}
        G -- Sí --> ERR1[JSON error: solicitudes<br/>bloqueadas + motivo]
        G -- No --> H[parseHabilidadesPost:<br/>strip_tags, máx 120 chars]
        H --> I{¿Al menos 1<br/>habilidad válida?}
        I -- No --> ERR2[JSON error: falta habilidad]
        I -- Sí --> J[PracticasController::solicitarPracticas]
    end

    subgraph DB ["Base de Datos MySQL"]
        J --> K[(INSERT solicitudes_practicantes<br/>aceptado = 0)]
        K --> L{¿success?}
        L -- Sí --> M[(INSERT habilidades de la vacante<br/>mdlSaveSolicitudHabilidades)]
    end

    M --> N[Encolar correo al admin<br/>sendSolicitudPracticas → email_queue]
    N --> O[(INSERT notificación in-app al admin)]
    L -- No --> ERR3[JSON success = false]

    O --> P[Alert de éxito, reset del wizard,<br/>recargar lista y cerrar modal]
    ERR1 --> Q[Alert con mensaje de error]
    ERR2 --> Q
    ERR3 --> Q
```

> **Notas:**
> - La validación de campos (`numPract`, `fechaLimite`, horario) es **solo de cliente**; el backend únicamente re-valida habilidades y bloqueo. Un POST directo puede insertar datos inválidos.
> - El correo **no bloquea** la respuesta: `MailService::sendMail` encola en `email_queue` y retorna de inmediato (ver `docs/flujo_correos.md`).

---

## 3. Gestor de vacantes (master-detail)

Al abrir el gestor, `solicitudes()` trae todas las vacantes del organismo con sus prospectos y pinta la lista (panel izquierdo) y el detalle al hacer clic (panel derecho).

```mermaid
flowchart LR
    subgraph FE ["Frontend — solicitudes.js"]
        A([Carga de página]) --> B[AJAX action = getSolicitudes]
    end

    subgraph BE ["Backend PHP"]
        B --> C[(SELECT vacantes del organismo<br/>filtrado por sesión)]
        C --> D[Bucle por cada vacante]
        D --> E[(SELECT prospectos por vacante<br/>consulta N+1)]
        E --> F[JSON vacantes + prospects]
    end

    subgraph FE2 ["Frontend — render"]
        F --> G{¿Hay vacantes?}
        G -- No --> H[Mensaje: aún no tienes vacantes]
        G -- Sí --> I[Filtrar vencidas comparando<br/>fecha_limite con reloj del navegador]
        I --> J[Lista master: título por habilidades<br/>o licenciatura legada + badges]
        J --> K{Clic en tarjeta}
        K --> L[renderDetalleSolicitud:<br/>detalle + postulantes]
    end
```

> **Cuellos de botella conocidos:** consulta N+1 de prospectos (`getSolicitudesPracticas`) y filtro de vencidas con la fecha del **cliente** (un reloj desfasado muestra/oculta vacantes incorrectamente).

---

## 4. Edición y eliminación

Ambas verifican **propiedad**: la solicitud debe pertenecer al `organismo_externo_id` de la sesión (HTTP 403 si no).

```mermaid
flowchart TD
    A([Organismo abre detalle de vacante]) --> B{Acción}

    B -- Editar --> C[Modal editarPractModal<br/>action = getSolicitudById]
    C --> D{¿Es dueño de la solicitud?}
    D -- No --> ERR[HTTP 403 Acceso no autorizado]
    D -- Sí --> E[Precarga campos + habilidades]
    E --> F[Submit action = updateSolicitud]
    F --> G{¿Dueño y al menos 1 habilidad?}
    G -- No --> ERR
    G -- Sí --> H[(UPDATE solicitud +<br/>reemplazo de habilidades)]

    B -- Eliminar --> I[action = deleteSolicitud]
    I --> J{¿Es dueño?}
    J -- No --> ERR
    J -- Sí --> K[(Soft delete: activo = 0)]
```

---

## 5. Estados de una vacante

```mermaid
stateDiagram-v2
    [*] --> Pendiente : Organismo publica (aceptado = 0)
    Pendiente --> Activa : Admin acepta (aceptado = 1)
    Pendiente --> Rechazada : Admin rechaza
    Activa --> Vencida : fecha_limite pasa (filtro frontend)
    Activa --> Eliminada : deleteSolicitud (activo = 0)
    Pendiente --> Eliminada : deleteSolicitud
    Activa --> Activa : updateSolicitud (edición)
```

---

## 6. Referencias cruzadas

- Alta del organismo y bloqueo por strikes: `docs/flujo_registro_organismos.md`
- Postulación y selección de alumnos sobre la vacante: `docs/flujo_postulacion_alumnos.md`
- Asistencias de los practicantes aceptados: `docs/flujo_asistencias_alumnos.md`
- Correos involucrados: `docs/mails/practicas_profesionales.md`
