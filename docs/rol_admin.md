# Rol: Admin (Administrador General)

> Documento de permisos y accesos · Generado: Junio 2026

---

## 1. Objetivo del Rol

El **admin** es el super-usuario del sistema. Tiene acceso total a ambos módulos (Servicio Social y Prácticas Profesionales), gestión de usuarios administrativos, configuraciones del sistema, y supervisión de todos los flujos de documentos y evaluaciones.

Se determina en `config/whiteList.php` cuando:
- `$_SESSION['user']['role'] === 'admin'` Y
- `tipo_servicio['type_admin']` **NO** es 1 ni 2 (es `null` o no existe en `admin_type`)

Si `type_admin === 1` → se reasigna a `admin_servicio`
Si `type_admin === 2` → se reasigna a `admin_practicas`

---

## 2. Matriz de Permisos

### 2.1 Páginas permitidas (`whiteList.php` línea 38-56)

| Slug | Vista | Dominio |
|------|-------|---------|
| `inicio` | `selectDashboard.php` → `dashboardAdministrador.php` | General |
| `users` | `view/pages/users.php` | Gestión usuarios |
| `events` | `view/pages/events.php` | SS — Eventos |
| `event_types` | `view/pages/event_types.php` | SS — Tipos evento |
| `students` | `view/pages/students.php` | SS — Alumnos internos |
| `external_students` | `view/pages/external_students.php` | SS — Alumnos externos |
| `register_event` | `view/pages/register_event.php` | SS — Registro evento |
| `courses` | `view/pages/courses.php` | SS — Ciclos escolares |
| `areas` | `view/pages/areas.php` | SS — Áreas |
| `degrees` | `view/pages/degrees.php` | SS — Licenciaturas |
| `internship_companies` | `view/pages/practicas/internship_companies.php` | PP — Organismos |
| `practice_solicitud` | `view/pages/practicas/practice_solicitud.php` | PP — Solicitudes |
| `internship_students` | `view/pages/practicas/internship_students.php` | PP — Estudiantes PP |
| `internship_events` | `view/pages/practicas/internship_events.php` | PP — Eventos PP |
| `reports_practices` | `view/pages/practicas/reports_practices.php` | PP — Reportes |
| `internship_areas` | `view/pages/practicas/internship_areas.php` | PP — Áreas internas |
| `configs` | `view/pages/configs.php` | Configuraciones |

### 2.2 Menú lateral (`menu.php`)

```
📊 Tablero
📁 Prácticas profesionales ▼
    🏢 Organismos receptores
    🏛️ Áreas internas
📁 Servicio social ▼
    🏫 Ciclo escolar
    🎓 Licenciaturas
    🏀 Áreas
    📅 Eventos
    👨‍🎓 Estudiantes internos
    👔 Estudiantes externos
⚙️ Configuraciones
👥 Admin y encargados
```

### 2.3 Acciones de escritura

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| Crear usuario admin/teacher | `ajax.forms.php` POST directo (L1457-1467) | Requiere `role === 'admin'` ✅ |
| Editar usuario | `ajax.updateUser.php` | |
| Eliminar usuario | `ajax.deleteUser.php` | |
| CRUD eventos | `ajax.forms.php` search=`event` | |
| CRUD tipos de evento | `ajax.forms.php` search=`event_types` | |
| CRUD ciclos escolares | `ajax.forms.php` search=`courses` | |
| CRUD licenciaturas | `ajax.forms.php` search=`degrees` | Crear: POST directo (L1469-1481) |
| CRUD áreas | `ajax.forms.php` search=`areas` | |
| Aceptar/rechazar alumnos SS | `ajax.forms.php` search=`student` action=`acceptStudent`/`denegateStudent` | |
| Aceptar/rechazar alumnos PP | `ajax.forms.php` search=`student` action=`acceptStudentPractice`/`denegateStudentPractice` | |
| Dar de baja alumno | `ajax.forms.php` search=`student` action=`dropStudent` | |
| Aprobar/rechazar IJUMICH | `ajax.forms.php` search=`ijumich_requests` | Verificado `role === 'admin'` ✅ |
| Aceptar/rechazar organismos | `ajax.forms.php` search=`organismos_externos` | |
| Aceptar/rechazar solicitudes PP | `ajax.forms.php` search=`practices` | |
| Aprobar/rechazar reportes PP | `ajax.forms.php` search=`reports` | |
| Subir imagen configuración | `upload-image.php` | Con CSRF ✅ |
| Guardar configs cartas/plantillas | `carta-config.php`, `carta-aceptacion-config.php`, etc. | |
| Generar cartas PP | `practices/generarCartaPresentacion.php` | |
| Generar constancias | `practices/generarConstanciaAcreditacion.php` | |
| Export Excel | `practices/export_companies_excel.php` | |

---

## 3. Restricciones

- No puede acceder a rutas no listadas en `$navWithLogin['admin']` → redirige a error 404.
- `session_timeout`: 30 minutos de inactividad (Security.php).
- Sujeto a rate limiting de login: máx 5 intentos, bloqueo 15 min.
- Las acciones de escritura **no validan CSRF** en el dispatcher central (ver SEC-002 en `arquitectura_sistema.md`).

---

## 4. Interacciones clave

### 4.1 Tablas MySQL

| Tabla | Operación | Contexto |
|-------|-----------|----------|
| `users` | CRUD | Gestión de administradores y encargados |
| `admin_type` | R/W | Tipo de admin (SS/PP) |
| `events` | CRUD | Crear y gestionar eventos |
| `event_types` | CRUD | Catálogo de tipos |
| `students_events` | R/W | Aprobar/rechazar candidatos a eventos |
| `student` | R/W | Aceptar, rechazar, dar de baja alumnos SS |
| `students_practicas` | R/W | Aceptar, rechazar alumnos PP |
| `servicio_social_ijumich` | R/W | Aprobar/rechazar documentos del flujo IJUMICH |
| `organismos_externos` | R/W | Aceptar/rechazar organismos |
| `solicitudes_practicantes` | R/W | Gestionar solicitudes de practicantes |
| `courses` | CRUD | Ciclos escolares |
| `degrees` | CRUD | Licenciaturas |
| `areas` | CRUD | Áreas internas |
| `email_queue` | R | Supervisión de cola de correos |
| `logs` | R | Auditoría de acciones |
| `notifications` | R/W | Notificaciones del sistema |

### 4.2 Peticiones AJAX principales

```
POST controller/ajax/ajax.forms.php
  search=users                    → FormsModel::mdlSearchUsers()
  search=student&action=*         → FormsController::ctr*()
  search=ijumich_requests         → ServicioModel::mdl*Ijumich()
  search=organismos_externos      → PracticasModel::mdlGetExternals()
  search=reports                  → PracticasController::ctrGetParcialReportsAdmin()
  search=AllDataEvents            → Consolidación eventos+áreas+usuarios

POST controller/practices/companies.php
  action=getOrganismosConEstudiantes → PracticasModel::mdlGetOrganismosConEstudiantes()
  action=getStudentsByOrganismo      → PracticasModel::mdlGetStudentsByOrganismo()
  action=exportOrganismos            → PracticasModel::mdlExportOrganismos()
```

---

*Generado como parte de la auditoría del sistema — Universidad Montrer · Junio 2026*
