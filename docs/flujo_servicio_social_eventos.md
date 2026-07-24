# Flujo de Servicio Social — Alumnos, eventos y puntos

Este documento describe el ciclo del alumno de Servicio Social (rol `student`): registro y aprobación, selección del tipo de servicio, inscripción a eventos con puntos, calificación por el encargado (teacher) y la meta de puntos mínimos por licenciatura que habilita la carta de conclusión. El flujo documental IJUMICH se detalla aparte en `docs/flujo_ijumich.md`.

**Archivos involucrados:**

| Capa | Archivo | Responsabilidad |
|---|---|---|
| Registro | `controller/ajax/ajax.forms.php` (search=`student`, action=`addStudent`) | Alta pública del alumno SS (`isAcepted = 0`) |
| Admin SS | `controller/ajax/ajax.forms.php` (search=`student`: `acceptStudent`, `denegateStudent`, `dropStudent`, `editStudent`) | Gestión de alumnos |
| Primer login | `view/pages/modals/firstLogStudent.php` + action=`selectServiceActive` | Selección del tipo de servicio |
| Eventos | `controller/ajax/ajax.forms.php` (search=`event`: `applyEvent`, `approveEvent`, `acceptCandidate`, `gradeStudent`) | Ciclo del evento |
| Controlador | `controller/forms.controller.php` (`FormsController`) | Login, eventos, puntos |
| Modelo | `model/generalModels.php` (`FormsModel`: `mdlAddEvent`, `mdlApplyEvent`, `mdlStudentPoints`, `mdlStudentEventsPoints`) | Persistencia SS |
| Carta | `controller/ajax/generarCartaConclusionServicio.php` | PDF al cumplir puntos mínimos |
| Correos | `controller/emails.php` | Credenciales, aviso de nuevos eventos, resultados |

**Tablas:** `student`, `events`, `event_types`, `students_events`, `degrees` (con `minPoints`), `courses`, `areas`, `areas_users`, `tipo_servicio`, `cartas_conclusion_servicio`, `email_queue`.

---

## 1. Vista general

```mermaid
flowchart LR
    A([Registro público del alumno]) --> B[(Pendiente isAcepted = 0)]
    B --> C[Admin acepta:<br/>credenciales por correo]
    C --> D[Primer login: selecciona<br/>tipo de servicio]
    D --> E[Se inscribe a eventos<br/>y acumula puntos]
    E --> F{¿Puntos ≥ minPoints<br/>de su licenciatura?}
    F -- No --> E
    F -- Sí --> G([Carta de conclusión<br/>de Servicio Social])
    D -.-> H[Flujo documental IJUMICH<br/>en paralelo — ver flujo_ijumich.md]
```

---

## 2. Registro y aprobación del alumno

```mermaid
flowchart TD
    A([Alumno llena registro SS<br/>action = addStudent, público]) --> B[(INSERT student<br/>isAcepted = 0)]
    B --> C{Admin SS revisa}
    C -- denegateStudent --> D([Registro rechazado])
    C -- acceptStudent --> E[Generar contraseña<br/>generateRandomPassword]
    E --> F[(UPDATE isAcepted = 1)]
    F --> G[Correo con credenciales]
    G --> H([Login: role = student])

    H --> I{¿tipo_servicio = null?<br/>primer inicio de sesión}
    I -- Sí --> J[Modal firstLogStudent:<br/>action = selectServiceActive]
    J --> K[(UPDATE tipo de servicio<br/>interno / externo)]
    I -- No --> L([Dashboard según tipo:<br/>interno o externo])
    K --> L
```

> El tipo elegido define el dashboard (`dashboardEstudianteInterno.php` / `dashboardEstudianteExterno.php`) y qué eventos ve el alumno (filtrados por `tipo_servicio`).

---

## 3. Ciclo de un evento con puntos

El encargado (teacher) o el admin SS crea eventos con vacantes y puntos. El alumno se inscribe si hay cupo; el encargado aprueba candidatos y al final califica la participación asignando los puntos.

```mermaid
flowchart TD
    subgraph Teacher ["Encargado / Admin SS"]
        A([Crear evento: nombre, fecha,<br/>vacantes, puntos, tipo de servicio]) --> B[(INSERT events<br/>mdlAddEvent)]
        B --> C[(Consulta de alumnos elegibles<br/>⚠ N+1: 1 query de puntos por alumno)]
        C --> D[Correo de aviso a cada<br/>alumno elegible via email_queue]
    end

    subgraph Alumno ["Alumno SS"]
        D --> E[Ve eventos vigentes:<br/>fecha futura, con vacantes,<br/>de su tipo de servicio]
        E --> F[action = applyEvent]
        F --> G{¿Hay vacantes<br/>disponibles?}
        G -- No --> ERR[No hay cupo]
        G -- Sí --> H[(INSERT students_events<br/>candidato pendiente)]
    end

    subgraph Teacher2 ["Encargado del evento"]
        H --> I{Revisión del candidato<br/>acceptCandidate / approveEvent}
        I -- Rechaza --> J([Candidato rechazado])
        I -- Acepta --> K([Alumno inscrito:<br/>asiste al evento])
        K --> L[Calificar participación<br/>action = gradeStudent]
        L --> M[(UPDATE students_events:<br/>puntos obtenidos)]
    end

    M --> N([Puntos suman al<br/>acumulado del alumno])
```

> **Cuello de botella (OPT-001):** `mdlAddEvent` carga todos los alumnos y consulta sus puntos uno por uno (N+1) para decidir a quién avisar por correo.

---

## 4. Meta de puntos y carta de conclusión

Cada licenciatura (`degrees`) define un mínimo de puntos (`minPoints`). El dashboard del alumno muestra su avance (`mdlStudentEventsPoints`).

```mermaid
flowchart TD
    A([Alumno consulta su avance]) --> B[(SELECT puntos acumulados<br/>de eventos aprobados)]
    B --> C{¿Puntos ≥ minPoints<br/>de su licenciatura?}
    C -- No --> D([Sigue inscribiéndose<br/>a eventos])
    C -- Sí --> E[Solicita carta de conclusión<br/>generarCartaConclusionServicio.php]
    E --> F[(INSERT en<br/>cartas_conclusion_servicio<br/>con folio)]
    F --> G([PDF descargable:<br/>Servicio Social concluido])
```

---

## 5. Estados del alumno en un evento

```mermaid
stateDiagram-v2
    [*] --> Candidato : applyEvent (hay vacantes)
    Candidato --> Rechazado : Encargado rechaza
    Candidato --> Inscrito : Encargado acepta
    Inscrito --> Calificado : gradeStudent (asigna puntos)
    Rechazado --> [*] : No suma puntos
    Calificado --> [*] : Puntos suman al acumulado
```

---

## 6. Referencias cruzadas

- Flujo documental IJUMICH (9 pasos interno / externo): `docs/flujo_ijumich.md`
- Catálogo de correos de Servicio Social: `docs/mails/servicio_social.md`
- Hallazgos de seguridad relacionados (SEC-004 roles en AJAX): `docs/arquitectura_sistema.md`
