# Flujo de postulación y selección de alumnos (Prácticas)

Este documento describe el camino del alumno de Prácticas Profesionales desde su registro público hasta quedar activo en una práctica: registro con matrícula GES, aprobación del admin, postulación a vacantes, entrevista, aceptación/rechazo por el organismo, carta de presentación (con vigencia de 24 horas hábiles) y confirmación de presentación.

**Archivos involucrados:**

| Capa | Archivo | Responsabilidad |
|---|---|---|
| Registro alumno | `controller/ajax/ajax.forms.php` (`checkMatricula`, `registerStudentPracticas`) | Búsqueda en SQL Server GES y alta en `students_practicas` |
| Admin PP | `controller/ajax/ajax.forms.php` (search=`student`, `acceptStudentPractice`) y `controller/practices/students.php` | Aceptar/rechazar alumnos, reenviar credenciales |
| Postulación | `controller/ajax/ajax.forms.php` (search=`practices_available`, `applyForPractice`) | Vacantes activas y creación de la postulación |
| Organismo | `controller/organismo/forms.php` (cases `evaluarEntrevista`, `aceptarProspecto`, `rechazarProspecto`, `confirmarPresentacion`, `getHistorialAlumnos`) | Selección de candidatos |
| Carta | `controller/practices/generarCartaPresentacion.php` | PDF con folio y fecha límite de presentación |
| Modelo | `model/PracticasModel.php`, `model/GesModel.php` | Persistencia y búsqueda GES |
| Correos | `controller/emails.php` | Credenciales, postulación recibida, aceptación/rechazo, carta generada/expirada, presentación confirmada |

**Tablas:** `students_practicas`, `students_in_practices`, `solicitudes_practicantes`, `cartas_practicas_profesionales`, `organismos_externos`, `email_queue`, `notifications`. Externas: SQL Server **GES** (búsqueda de matrícula).

---

## 1. Vista general

```mermaid
flowchart LR
    A([Registro público<br/>con matrícula]) --> B[(Alumno pendiente<br/>isAcepted = 0)]
    B --> C[Admin acepta:<br/>credenciales por correo]
    C --> D[Alumno se postula<br/>a una vacante activa]
    D --> E{Organismo evalúa<br/>entrevista y decide}
    E -- Rechaza --> F([Postulación rechazada<br/>puede postularse a otra])
    E -- Acepta --> G[Carta de presentación<br/>24 horas hábiles]
    G --> H{¿Se presentó<br/>a tiempo?}
    H -- No --> I([Carta expirada])
    H -- Sí --> J([Práctica activa:<br/>asistencias, reportes,<br/>evaluaciones])
```

---

## 2. Registro del alumno PP (público)

```mermaid
flowchart TD
    A([Alumno abre registro PP<br/>sin login]) --> B[Escribe su matrícula]
    B --> C[action = checkMatricula]
    C --> D[(SELECT en SQL Server GES<br/>GesModel::mdlSearchStudentGES)]
    D --> E{¿Matrícula encontrada?}
    E -- No --> ERR1[Error: matrícula inexistente]
    E -- Sí --> F[Autocompletar datos académicos]
    F --> G[action = registerStudentPracticas]
    G --> H[(INSERT students_practicas<br/>isAcepted = 0)]
    H --> I([Pendiente de aprobación<br/>del admin de prácticas])

    I --> J{Admin de prácticas}
    J -- Rechaza --> K([Registro rechazado])
    J -- Acepta --> L[Generar contraseña +<br/>correo practicas_info<br/>con credenciales]
    L --> M([Alumno puede iniciar sesión<br/>role = alumno_practicas])
```

---

## 3. Postulación a una vacante

El alumno solo ve vacantes **activas** (`aceptado = 1`, con fecha límite vigente y filtradas por `tipo_practica`). Al postularse se crea el registro en `students_in_practices`.

```mermaid
flowchart TD
    A([Dashboard del alumno]) --> B[search = practices_available]
    B --> C[(SELECT vacantes activas)]
    C --> D[Alumno elige vacante y se postula<br/>action = applyForPractice]
    D --> E[(INSERT students_in_practices<br/>estado pendiente)]
    E --> F[Correo al alumno:<br/>Postulación recibida]
    F --> G([Prospecto visible para el organismo<br/>en el gestor de vacantes])
```

---

## 4. Selección por el organismo (entrevista → decisión)

El organismo ve los prospectos por vacante en el modal de candidatos. Puede registrar una **evaluación de entrevista** y después aceptar o rechazar, siempre con un motivo de al menos 10 caracteres. Todas las acciones verifican que la vacante pertenezca al organismo (403 si no).

```mermaid
flowchart TD
    A([Prospecto postulado]) --> B{¿La vacante pertenece<br/>al organismo en sesión?}
    B -- No --> ERR[HTTP 403 Acceso no autorizado]
    B -- Sí --> C[Opcional: evaluarEntrevista<br/>llegó a tiempo, formal,<br/>calificación de respuestas, comentarios]
    C --> D{Decisión del organismo}

    D -- rechazarProspecto --> E{¿Motivo ≥ 10 caracteres?}
    E -- No --> ERR2[Error: motivo muy corto]
    E -- Sí --> F[(UPDATE postulación: rechazada)]
    F --> G[Correo al alumno con motivo]
    G --> H([Alumno puede postularse<br/>a otra vacante])

    D -- aceptarProspecto --> I{¿Motivo ≥ 10 caracteres<br/>y fecha de inicio?}
    I -- No --> ERR2
    I -- Sí --> J[(UPDATE postulación: aceptada<br/>+ fecha de inicio)]
    J --> K[Correo al alumno:<br/>aceptación con mensaje]
    K --> L([Listo para carta<br/>de presentación])
```

---

## 5. Carta de presentación y confirmación

Tras la aceptación, se genera la carta de presentación en PDF (con folio en `cartas_practicas_profesionales`). El alumno tiene **24 horas hábiles** para presentarse; el organismo confirma su llegada con `confirmarPresentacion`.

```mermaid
flowchart TD
    A([Alumno aceptado]) --> B[Generar carta de presentación<br/>generarCartaPresentacion.php]
    B --> C[(INSERT folio en<br/>cartas_practicas_profesionales)]
    C --> D[Correo: carta generada<br/>+ fecha límite 24h hábiles]
    D --> E{¿El alumno se presenta<br/>dentro del plazo?}

    E -- No --> F[Proceso automático detecta<br/>expiración del plazo]
    F --> G[Correo: carta expirada]
    G --> H([Proceso detenido /<br/>admin puede reiniciarlo])

    E -- Sí --> I[Organismo: action = confirmarPresentacion]
    I --> J[(UPDATE: presentación confirmada)]
    J --> K[Correo: presentación confirmada]
    K --> L([Práctica activa:<br/>inicia registro de asistencias])
```

---

## 6. Estados de la postulación

```mermaid
stateDiagram-v2
    [*] --> Pendiente : applyForPractice
    Pendiente --> Entrevistado : evaluarEntrevista (opcional)
    Pendiente --> Rechazado : rechazarProspecto (motivo)
    Entrevistado --> Rechazado : rechazarProspecto (motivo)
    Entrevistado --> Aceptado : aceptarProspecto (motivo + fecha inicio)
    Pendiente --> Aceptado : aceptarProspecto
    Aceptado --> CartaGenerada : Carta de presentación (folio)
    CartaGenerada --> Expirado : No se presentó en 24h hábiles
    CartaGenerada --> Activo : confirmarPresentacion
    Rechazado --> [*] : Puede postularse a otra vacante
    Activo --> [*] : Continúa en flujo de asistencias/reportes
```

---

## 7. Referencias cruzadas

- Vacantes a las que se postula: `docs/flujo_vacantes_practicantes.md`
- Asistencias diarias tras quedar activo: `docs/flujo_asistencias_alumnos.md`
- Reportes 180h/360h y evaluaciones: `docs/flujo_reportes_evaluaciones_practicas.md`
- Correos del proceso (Nos. 1–7 del catálogo): `docs/mails/practicas_profesionales.md`
