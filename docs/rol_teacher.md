# Rol: Teacher (Encargado de Área)

> Documento de permisos y accesos · Generado: Junio 2026

---

## 1. Objetivo del Rol

El **teacher** es un usuario responsable de supervisar los eventos de servicio social asignados a su área. Puede ver los alumnos inscritos en sus eventos, aprobar/rechazar candidatos, calificar participación y gestionar las áreas de prácticas asignadas.

Se determina en login vía:
- `controller/ajax/ajax.login.php` → `FormsController::ctrLogin()` con `$_SESSION['user']['role'] === 'teacher'`

**Nota**: `teacher` NO se reasigna a otro rol en `whiteList.php` — mantiene su rol tal cual.

---

## 2. Matriz de Permisos

### 2.1 Páginas permitidas (`whiteList.php` línea 58-63)

| Slug | Vista | Dominio |
|------|-------|---------|
| `inicio` | `selectDashboard.php` → `dashboardProfesor.php` | Dashboard |
| `events` | `view/pages/events.php` | SS — Eventos |
| `students` | `view/pages/students.php` | SS — Alumnos |
| `register_event` | `view/pages/register_event.php` | SS — Registro evento |
| `area_practicas` | `view/pages/practicas/area_practicas.php` | PP — Mis áreas asignadas |

### 2.2 Menú lateral (`menu.php` línea 63-67)

```
📊 Tablero
👨‍🎓 Estudiantes
🏛️ Mis Áreas PP
```

### 2.3 Acciones de escritura

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| Crear evento | `ajax.forms.php` search=`event` | Solo de su tipo de servicio |
| Aprobar/rechazar candidato | `ajax.forms.php` search=`event` action=`approveEvent` | Solo sus eventos |
| Calificar participación | `ajax.forms.php` search=`event` action=`gradeStudent` | Puntos al alumno |
| Aceptar/rechazar candidato evento | `ajax.forms.php` search=`event` action=`acceptCandidate` | |
| Aprobar asistencias PP | `practices/teacher.php` | Áreas asignadas |

---

## 3. Restricciones

- **No accede** a: `users`, `configs`, `courses`, `degrees`, `event_types`, `internship_companies`, `internship_students`, `reports_practices`, `external_students`.
- No puede crear/editar usuarios.
- No puede aceptar/rechazar alumnos SS ni PP.
- No puede aprobar documentos IJUMICH.
- Solo ve eventos filtrados por su `tipo_servicio` en el dashboard.

---

## 4. Interacciones clave

### 4.1 Tablas MySQL

| Tabla | Operación |
|-------|-----------|
| `events` | CRUD (solo los propios) |
| `students_events` | R/W (aprobar/rechazar/calificar) |
| `student` | R (ver datos alumnos) |
| `areas` | R (ver áreas asignadas) |
| `areas_users` | R (relación usuario-área) |
| `asistencias_practicas` | R/W (áreas PP asignadas) |

### 4.2 Dashboard (`dashboardProfesor.php`)

El dashboard del profesor muestra:
- Eventos creados por el encargado
- Candidatos pendientes de aprobación
- Alumnos inscritos con puntos acumulados
- Áreas de prácticas asignadas (si tiene rol de encargado en PP)

---

*Generado como parte de la auditoría del sistema — Universidad Montrer · Junio 2026*
