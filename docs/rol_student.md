# Rol: Student (Alumno Servicio Social)

> Documento de permisos y accesos · Generado: Junio 2026

---

## 1. Objetivo del Rol

El **student** es el alumno de Servicio Social. Tiene dos sub-variantes según el tipo de registro:

- **Interno** (`student.type === 'universidad'`): Alumnos que registran su SS desde la propia universidad. Tienen acceso al flujo IJUMICH de 9 pasos, acumulación de puntos por eventos, y generación de cartas.
- **Externo** (`student.type === 'empresa'`): Alumnos que registran su SS desde una empresa/organismo externo. Tienen un flujo diferente (IJUMICH externo) con menos pasos.

Se determina en login vía:
- `controller/ajax/ajax.login.php` → `FormsController::ctrLoginStudentService()` → `$_SESSION['user']['role'] === 'student'`

La tabla de datos es `student` (no `students_practicas` que es para PP).

---

## 2. Matriz de Permisos

### 2.1 Páginas permitidas (`whiteList.php` línea 64-68)

| Slug | Vista | Dominio |
|------|-------|---------|
| `inicio` | `selectDashboard.php` → (interno: `dashboardEstudianteInterno.php`, externo: `dashboardEstudianteExterno.php`) | Dashboard |

Solo tienen acceso al dashboard. Todo lo demás se hace desde modales/AJAX dentro del propio dashboard.

### 2.2 Menú lateral (`menu.php` línea 68)

```
📊 Tablero
```

(Solo un enlace — el alumno opera enteramente desde su tablero)

---

## 3. Flujo del Alumno Interno (9 Pasos IJUMICH)

### 3.1 Selección de tipo de servicio

Al iniciar sesión por primera vez, si `tipo_servicio === null`, se muestra el modal `firstLogStudent.php` para seleccionar el tipo de servicio (SS Interno, SS Externo, etc.).

**Acción**: `selectServiceActive` → `FormsModel::mdlSelectServiceActive()`

### 3.2 Flujo IJUMICH (pasos gestionados por el alumno interno)

| Paso | Tipo (BD) | Acción del alumno | Endpoint AJAX |
|------|-----------|-------------------|---------------|
| 1 | `carta_aceptacion_servicio` | Solicitar carta de aceptación | `solicitar_carta_aceptacion_interno` |
| 2 | `carta_practicas_interno` | Subir carta de finalización de prácticas | `cargar_carta_practicas_interno` |
| 3 | `solicitud_registro` | Subir solicitud de registro (PDF) | `cargar_solicitud_registro` |
| 4 | `reporte_parcial_1` | Subir reporte parcial #1 | `cargar_reporte_parcial` (num=1) |
| 5 | `reporte_parcial_2` | Subir reporte parcial #2 | `cargar_reporte_parcial` (num=2) |
| 6 | `reporte_parcial_3` | Subir reporte parcial #3 | `cargar_reporte_parcial` (num=3) |
| 7 | `carta_liberacion_interno` | Subir carta de liberación | `cargar_carta_liberacion_interno` |
| 8 | `evaluacion_unidad_productiva` | Subir evaluación unidad productiva (PDF) | `cargar_evaluacion_unidad_productiva` |
| 9 | `evaluacion_global` | Subir evaluación global (PDF) | `cargar_evaluacion_global` |

**Estado de cada paso**: `pendiente` → `aprobado` / `rechazado` (gestionado por admin)

**Ver historial**: `get_historial_interno` → `ServicioModel::mdlGetHistorialInternoAlumno()`

### 3.3 Eventos y puntos

- **Ver eventos disponibles**: Filtrados por `tipo_servicio` y `date > hoy` y `vacancies > inscritos`
- **Inscribirse**: `applyEvent` → `FormsModel::mdlApplyEvent()`
- **Ver puntos acumulados**: `studentEventsPoints` → `FormsModel::mdlStudentEventsPoints()`
- **Requisito para carta**: Alcanzar `minPoints` de su licenciatura (`degrees.minPoints`)

### 3.4 Generación de documentos

| Documento | Endpoint | Condiciones |
|-----------|----------|-------------|
| Carta de conclusión de SS | `generarCartaConclusionServicio.php` | Puntos ≥ mínimo |
| Carta de aceptación SS | `generarCartaAceptacionServicio.php` | Tras solicitud aprobada |
| Carta de presentación | `generarCartaPresentacion.php` | Tras solicitud IJUMICH aprobada |
| Carta IJUMICH | `generarCartaIjumich.php` | Carta presentación IJUMICH |

---

## 4. Flujo del Alumno Externo (IJUMICH)

El alumno externo (`type === 'empresa'`) interactúa con el flujo IJUMICH **sin el flujo interno de 9 pasos**. Sus acciones se centran en:

| Acción | Endpoint |
|--------|----------|
| Ver historial IJUMICH | `get_historial_ijumich` |
| Solicitar carta de presentación | `solicitar_carta_presentacion_ijumich` |
| Subir carta de prácticas | `cargar_carta_practicas_ijumich` |
| Subir carta de liberación | `cargar_carta_liberacion_ijumich` |

Su dashboard (`dashboardEstudianteExterno.php`) muestra el progreso de estos pasos.

---

## 5. Restricciones

- Solo accede a la página `inicio` — cualquier otra slug redirige a error.
- No puede ver datos de otros alumnos.
- Los archivos subidos se validan por MIME type y tamaño (máx 5MB general, 10MB para evaluaciones).
- El `student_id` se toma **siempre de la sesión** (`$_SESSION['user']['idStudent']`), no del POST → previene IDOR en uploads.
- `session_timeout`: 30 minutos.

---

## 6. Interacciones clave

### 6.1 Tablas MySQL

| Tabla | Operación |
|-------|-----------|
| `student` | R (datos propios) |
| `events` | R (filtrado por tipo servicio, fecha, vacantes) |
| `students_events` | R/W (inscripción a eventos) |
| `servicio_social_ijumich` | R/W (subir documentos, ver historial) |
| `cartas_servicio_social` | R/W (folios de cartas) |
| `cartas_aceptacion_servicio` | R/W (folios carta aceptación) |
| `cartas_conclusion_servicio` | R/W (carta de conclusión generada) |
| `tipo_servicio` | R (selección tipo servicio) |

### 6.2 Peticiones AJAX principales

```
POST controller/ajax/ajax.forms.php
  search=event                → FormsController::ctrGetEvents() (filtrado)
  search=event&action=applyEvent → FormsController::ctrApplyEvent()
  action=get_historial_interno → ServicioModel::mdlGetHistorialInternoAlumno()
  action=cargar_reporte_parcial → ServicioModel::mdlCargarReporteParcial()
  action=selectServiceActive   → FormsModel::mdlSelectServiceActive()
  action=get_historial_ijumich → ServicioModel::mdlGetHistorialIjumichAlumno()
```

---

*Generado como parte de la auditoría del sistema — Universidad Montrer · Junio 2026*
