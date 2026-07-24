# Flujo de reportes y evaluaciones (Prácticas Profesionales)

Este documento describe los hitos de **180 horas** (reporte parcial + evaluación parcial) y **360 horas** (reporte final + evaluación final), la revisión en dos niveles (organismo → admin), las rúbricas de evaluación y el cierre con la constancia de acreditación. Estos hitos además **bloquean el registro de nuevas asistencias** si no se cumplen.

**Archivos involucrados:**

| Capa | Archivo | Responsabilidad |
|---|---|---|
| Alumno | `controller/practices/students_flow.php` (`uploadPartialReport`, `uploadFinalReport`) | Subida del reporte al alcanzar el hito de horas |
| Organismo | `controller/organismo/forms.php` (cases `getReportsPractices`, `acceptReport`, `rejectReport`, `acceptReportFinal`, `rejectReportFinal`, `evaluarParticipanteParcial`, `evaluarParticipanteFinal`) | Revisión del reporte y evaluación con rúbricas |
| Vista organismo | `view/pages/practicas/modalSolPracticantes.php` (modales `reporteModal`, `reporteFinalModal`) | Botones "Rechazar" / "Aceptar y Evaluar" |
| Admin | `controller/ajax/ajax.forms.php` (search=`reports`) | Aprobación final de reportes; vista `reports_practices` |
| Constancia | `controller/practices/generarConstanciaAcreditacion.php` | PDF con folio al concluir la práctica |
| Modelo | `model/PracticasModel.php` (`mdlCheckBloqueoEvaluaciones`, `mdlGetSemaforoEvaluaciones`, rúbricas en constantes) | Persistencia, bloqueos y semáforos |
| Correos | `controller/emails.php` | Reporte aceptado/rechazado, aprobado por admin, evaluaciones |

**Tablas:** `reportes_parciales_practicas`, `reportes_finales_practicas`, `evaluaciones_parciales_practicas`, `evaluaciones_finales_practicas`, `constancias_acreditacion`, `asistencias_practicas` (horas acumuladas), `email_queue`, `notifications`.

---

## 1. Vista general — hitos por horas acumuladas

Las horas validadas de asistencias aprobadas (ver `docs/flujo_asistencias_alumnos.md`) alimentan dos hitos. Si el alumno alcanza el hito y **no entrega** el reporte o falta la evaluación, `mdlCheckBloqueoEvaluaciones` bloquea el registro de nuevas asistencias.

```mermaid
flowchart TD
    A([Práctica activa<br/>asistencias aprobadas suman horas]) --> B{Horas acumuladas}
    B -- "≥ 180 h" --> C[Hito parcial:<br/>reporte parcial + evaluación parcial]
    B -- "≥ 360 h" --> D[Hito final:<br/>reporte final + evaluación final]
    C --> E{¿Entregado y evaluado?}
    E -- No --> F[Bloqueo: no puede registrar<br/>nuevas asistencias]
    E -- Sí --> G[Continúa acumulando horas]
    D --> H{¿Entregado y evaluado?}
    H -- No --> F
    H -- Sí --> I([Cierre: constancia<br/>de acreditación])
```

---

## 2. Reporte parcial (hito 180 h)

Doble revisión: primero el **organismo** (que al aceptar pasa directo a evaluar), después el **admin de prácticas**.

```mermaid
flowchart TD
    A([Alumno alcanza 180 h]) --> B[Sube PDF: action = uploadPartialReport<br/>objetivo + actividades]
    B --> C[(INSERT reportes_parciales_practicas<br/>status pendiente)]
    C --> D[Organismo abre reporteModal<br/>action = getReportsPractices]
    D --> E{Decisión del organismo}

    E -- rejectReport --> F[(status = rechazado)]
    F --> G[Correo al alumno:<br/>reporte rechazado]
    G --> H([Alumno corrige y vuelve a subir])
    H --> B

    E -- "acceptReport (Aceptar y Evaluar)" --> I[(status = aceptado por revisor)]
    I --> J[Correo al alumno:<br/>reporte aceptado por el revisor]
    J --> K[Se abre la evaluación parcial<br/>evaluarParticipanteParcial]
    K --> L[(INSERT evaluaciones_parciales_practicas<br/>6 rubros + 10 actitudes<br/>+ fortalezas/debilidades)]

    L --> M{Revisión del admin<br/>search = reports}
    M -- Rechaza --> F
    M -- Aprueba --> N[(status = aprobado)]
    N --> O[Correo: reporte parcial aprobado]
    O --> P([Hito 180 h cumplido:<br/>se desbloquean asistencias])
```

---

## 3. Reporte final (hito 360 h)

Mismo esquema que el parcial, con el modal `reporteFinalModal` (incluye capacitación recibida, experiencia personal/profesional y resultados obtenidos) y la evaluación final.

```mermaid
flowchart TD
    A([Alumno alcanza 360 h]) --> B[Sube PDF: action = uploadFinalReport]
    B --> C[(INSERT reportes_finales_practicas<br/>status pendiente)]
    C --> D{Organismo — reporteFinalModal}
    D -- rejectReportFinal --> E[(status = rechazado)] --> F[Correo de rechazo] --> B
    D -- "acceptReportFinal (Aceptar y Evaluar)" --> G[(status = aceptado por revisor)]
    G --> H[evaluarParticipanteFinal]
    H --> I[(INSERT evaluaciones_finales_practicas)]
    I --> J{Admin — search = reports}
    J -- Rechaza --> E
    J -- Aprueba --> K[(status = aprobado)]
    K --> L[Correo: reporte final aprobado]
    L --> M[Admin genera constancia<br/>generarConstanciaAcreditacion.php]
    M --> N[(INSERT folio en<br/>constancias_acreditacion)]
    N --> O([Práctica concluida y acreditada])
```

---

## 4. Rúbricas de evaluación (parcial y final)

Ambas evaluaciones usan la misma estructura, definida como constantes en `PracticasModel`:

**Rubros (6):** asistencia y puntualidad · aplicación de conocimientos · contribución a la solución de problemas · iniciativa · responsabilidad · logro de objetivos.

**Actitudes (10):** interés en subsanar necesidades de aprendizaje · cooperación en equipo · tolerancia · expresión verbal y escrita · soluciones dentro de normas · organización y priorización · búsqueda de información adicional · adaptación · automotivación · receptividad ante la autoridad.

Más campos abiertos de **fortalezas** y **debilidades**.

**Semáforo de evaluaciones:** `mdlGetSemaforoEvaluaciones` calcula por alumno un indicador `semaforo_empresa` / `semaforo_alumno` que el organismo ve en su lista de practicantes (`getAllPractices`) para saber quién tiene evaluaciones pendientes.

---

## 5. Estados de un reporte

```mermaid
stateDiagram-v2
    [*] --> Pendiente : Alumno sube PDF
    Pendiente --> Rechazado : Organismo rechaza
    Pendiente --> AceptadoRevisor : Organismo acepta y evalúa
    AceptadoRevisor --> Rechazado : Admin rechaza
    AceptadoRevisor --> Aprobado : Admin aprueba
    Rechazado --> Pendiente : Alumno vuelve a subir
    Aprobado --> [*] : Hito cumplido (desbloquea asistencias / habilita constancia)
```

> **Nota de seguridad:** las evaluaciones del organismo **no verifican** que el alumno pertenezca al organismo en sesión (ver SEC-006 en `docs/arquitectura_sistema.md`).

---

## 6. Referencias cruzadas

- Acumulación de horas y bloqueos: `docs/flujo_asistencias_alumnos.md`
- Cómo llegó el alumno a la práctica: `docs/flujo_postulacion_alumnos.md`
- Correos del proceso (Nos. 8–11 del catálogo): `docs/mails/practicas_profesionales.md`
