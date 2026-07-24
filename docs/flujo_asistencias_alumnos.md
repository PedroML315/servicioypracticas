# Flujo de asistencias de los alumnos (Prácticas)

Este documento describe el ciclo de vida completo de una asistencia registrada por un alumno de prácticas, desde el registro hasta su aprobación/rechazo, incluyendo la lógica de strikes (Fase 3).

**Archivos involucrados:**

| Capa | Archivo | Responsabilidad |
|---|---|---|
| Router alumno | `controller/practices/students_flow.php` (case `registerAttendance`) | Recibe el POST del alumno y decide el flujo según `tipo_practica` |
| Router organismo | `controller/organismo/forms.php` (cases `aprobarAsistencia`, `rechazarAsistencia`, `actualizarHorarios`) | Acciones del organismo externo |
| Controlador | `controller/forms.controller.php` | `ctrRegisterAttendance`, `ctrAprobarAsistencia`, `ctrRechazarAsistencia`, `ctrRegisterAttendanceArea`, `ctrAprobarAsistenciaArea` |
| Modelo | `model/PracticasModel.php` | `mdlRegisterAttendance`, `mdlRegisterAttendanceArea`, `mdlCheckBloqueoEvaluaciones`, lógica de strikes |
| Correos | `controller/emails.php` | Notificaciones de registro, aprobación, rechazo y strikes |

**Tablas:** `asistencias_practicas` (empresa), `asistencias_areas_practicas` (universidad), `strikes_practicas`, `students_practicas`, `organismos_externos`.

---

## 1. Vista general

Existen **dos flujos** según el tipo de práctica del alumno (`$_SESSION['user']['tipo_practica']`):

- **`empresa`** → práctica en organismo externo. La asistencia la aprueba el **organismo externo** y aplica lógica de strikes.
- **`universidad`** → práctica en área interna de la universidad. La asistencia la aprueba el **encargado del área** (sin strikes).

```mermaid
flowchart TD
    A[Alumno envía formulario de asistencia<br/>fecha, hora entrada, hora salida, actividad] --> B{tipo_practica}
    B -- empresa --> C[Flujo organismo externo<br/>asistencias_practicas]
    B -- universidad --> D[Flujo área interna<br/>asistencias_areas_practicas]
```

---

## 2. Flujo empresa (organismo externo) — Registro

`students_flow.php` → `ctrRegisterAttendance()` → `mdlRegisterAttendance()`

```mermaid
flowchart TD
    A[POST action=registerAttendance<br/>fechaAsistencia, horaEntrada,<br/>horaSalida, actividadRealizada] --> B{¿Campos completos?}
    B -- No --> ERR1[Error: Missing required fields]
    B -- Sí --> C[Buscar práctica activa<br/>ctrIsStudentRegisteredInPractices]
    C --> D{¿Bloqueo por evaluaciones?<br/>mdlCheckBloqueoEvaluaciones}
    D -- "≥180h sin reporte parcial<br/>o sin eval. integral 180h" --> ERR2[Bloqueado: debe entregar<br/>reporte parcial / eval. 180h]
    D -- "≥360h sin reporte final<br/>o sin eval. integral 360h" --> ERR3[Bloqueado: debe entregar<br/>reporte final / eval. 360h]
    D -- Sin bloqueo --> E{Validación 1:<br/>¿Ya existe asistencia para<br/>esa fecha no rechazada?}
    E -- Sí --> ERR4[Asistencia ya registrada]
    E -- No --> F{Validación 2:<br/>¿El día cae dentro del rango<br/>autorizado dia_inicio–dia_fin<br/>de la solicitud?}
    F -- No --> ERR5[Día no autorizado]
    F -- Sí --> G{Validación 3:<br/>¿hora_salida > hora_entrada?}
    G -- No --> ERR6[Horario inválido]
    G -- Sí --> H[INSERT en asistencias_practicas<br/>status = 'pendiente']
    H --> I[Email al organismo externo<br/>sendAssistanceRegisteredEmail]
    I --> J([Asistencia pendiente de<br/>aprobación por el organismo])
```

> **Nota Fase 3:** la validación estricta de máximo 4 horas diarias en el registro está **comentada** intencionalmente (`mdlRegisterAttendance`, `model/PracticasModel.php` ~línea 1542). Se permite registrar el exceso para que el organismo lo evalúe y, en su caso, asigne el strike al aprobar.

---

## 3. Flujo empresa — Aprobación / rechazo y strikes

`controller/organismo/forms.php` → `ctrAprobarAsistencia()` / `ctrRechazarAsistencia()`

El organismo ve las asistencias con `status = 'pendiente'` (`mdlGetAssistancesPractices`) y puede aprobar, rechazar o corregir horarios antes de aprobar.

```mermaid
flowchart TD
    A([Asistencia pendiente]) --> B{Acción del organismo}

    B -- actualizarHorarios --> B1[Corrige hora_entrada / hora_salida<br/>Email al alumno: sendAssistanceUpdatedEmail] --> A

    B -- rechazarAsistencia --> R1[status = 'rechazado']
    R1 --> R2[Email al alumno<br/>sendAssistanceRejectedEmail]
    R2 --> R3([Alumno puede volver a registrar<br/>esa fecha])

    B -- aprobarAsistencia --> C[status = 'aprobado']
    C --> D[Calcular horas reales<br/>hora_salida − hora_entrada]
    D --> E{Horas reales}

    E -- "≤ 4h 00m" --> F[horas_validadas = horas reales<br/>sin strike]
    E -- "> 4h y ≤ 4h 10m<br/>(margen de gracia)" --> G[horas_validadas = 4.0<br/>sin strike]
    E -- "> 4h 10m" --> H[⚠ STRIKE<br/>horas_validadas = 4.0<br/>tiene_strike = 1]

    H --> I[INSERT en strikes_practicas<br/>tipo = exceso_horas]
    I --> J{Strikes del alumno<br/>en esta práctica}
    J -- "1er strike" --> K[Advertencia<br/>Email: sendStrikeAdvertenciaAlumno]
    J -- "2º strike o más" --> L[Baja de prácticas<br/>mdlBajaAlumnoPorStrike<br/>+ bloqueo del alumno en la empresa<br/>Email: sendStrikeBajaAlumno]

    I --> M[Incrementar strikes del organismo<br/>mdlIncrementStrikesOrganismo]
    M --> N{Strikes del organismo}
    N -- "1er strike" --> O[Advertencia<br/>Email: sendStrikeOrganismo]
    N -- "2º strike o más" --> P[Bloqueo de solicitudes<br/>mdlBloquearSolicitudesOrganismo<br/>Emails: sendOrganismoBloqueadoAuto<br/>+ notificación al admin]

    I --> Q[Notificar al admin<br/>sendStrikeAdmin]

    F --> Z[Email al alumno<br/>sendAssistanceApprovedEmail]
    G --> Z
    K --> Z
    L --> Z
    O --> Z
    P --> Z
    Q --> Z
    Z --> FIN([Asistencia aprobada:<br/>suma a horas acumuladas])
```

**Reglas de strikes (Fase 3):**

El límite diario es de **4 horas** con una **tolerancia de 10 minutos** (`$limiteHoras = 4`, `$tolerancia = 10/60` en `ctrAprobarAsistencia`).

| Horas reales de la asistencia | Horas validadas | Strike |
|---|---|---|
| ≤ 4h 00m | Horas reales | No |
| > 4h 00m y ≤ 4h 10m | 4.0 h (se recorta) | No (margen de gracia) |
| > 4h 10m | 4.0 h (se recorta) | **Sí** (para alumno y organismo) |

- **Alumno con 2 strikes** → baja de prácticas (`dado_de_baja_por_strike = 1`) y bloqueo en esa empresa.
- **Organismo con 2 strikes** → bloqueo automático de nuevas solicitudes de practicantes.
- El admin puede revertir strikes (`mdlRemoveStrikeStudent`, `mdlRemoveStrikeOrganismo`); al bajar de 2 se levanta la baja/el bloqueo automáticamente.

---

## 4. Flujo universidad (área interna)

`students_flow.php` → `ctrRegisterAttendanceArea()` → `mdlRegisterAttendanceArea()`. Aprueba el encargado del área (`ctrAprobarAsistenciaArea` / `ctrRechazarAsistenciaArea`).

```mermaid
flowchart TD
    A[POST action=registerAttendance<br/>tipo_practica = universidad] --> B{¿Postulación a área<br/>activa? status = 1}
    B -- No --> ERR1[No tienes una práctica activa]
    B -- Sí --> C{¿Ya existe asistencia para<br/>esa fecha no rechazada?}
    C -- Sí --> ERR2[Asistencia ya registrada]
    C -- No --> D[INSERT en asistencias_areas_practicas<br/>status = 'pendiente']
    D --> E([Pendiente de aprobación<br/>por el encargado del área])

    E --> F{Acción del encargado}
    F -- Aprobar --> G[status = 'aprobada'<br/>suma a horas acumuladas del área]
    F -- Rechazar --> H[status = 'rechazada'<br/>el alumno puede volver a registrar]
```

Diferencias respecto al flujo empresa:

- **No hay strikes** ni recorte de horas validadas.
- **No hay validación de día autorizado** ni de horario entrada/salida en el modelo.
- **No se envían correos** al aprobar/rechazar (solo cambia el status).
- El alumno solo ve en su dashboard las asistencias con `status = 'aprobada'` (`mdlGetAsistenciasArea`).

---

## 5. Estados de una asistencia

```mermaid
stateDiagram-v2
    [*] --> pendiente : Alumno registra
    pendiente --> pendiente : Organismo corrige horarios
    pendiente --> aprobado : Organismo/encargado aprueba
    pendiente --> rechazado : Organismo/encargado rechaza
    rechazado --> [*] : No cuenta horas (permite re-registro de la fecha)
    aprobado --> [*] : Suma horas validadas al acumulado
```

Las horas acumuladas aprobadas alimentan los hitos de **180 h** (reporte parcial + evaluación integral intermedia) y **360 h** (reporte final + evaluación integral final), que a su vez bloquean el registro de nuevas asistencias si no se cumplen (ver diagrama del punto 2).

---

## 6. Referencias cruzadas

- Cómo llegó el alumno a la práctica (postulación y presentación): `docs/flujo_postulacion_alumnos.md`
- Hitos 180h/360h, reportes y evaluaciones: `docs/flujo_reportes_evaluaciones_practicas.md`
- Bloqueo de solicitudes del organismo por strikes: `docs/flujo_registro_organismos.md` y `docs/flujo_vacantes_practicantes.md`
- Mecánica de envío de correos (cola): `docs/flujo_correos.md`
