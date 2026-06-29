# Rol: Admin Prácticas Profesionales (admin_practicas)

> Documento de permisos y accesos · Generado: Junio 2026

---

## 1. Objetivo del Rol

El **admin_practicas** es un administrador con alcance limitado al módulo de **Prácticas Profesionales**. Gestiona organismos receptores, solicitudes de practicantes, estudiantes de PP, reportes y áreas internas de prácticas.

Se determina en `config/whiteList.php` (línea 106-107) cuando:
- `$_SESSION['user']['role'] === 'admin'` Y
- `tipo_servicio['type_admin'] === 2`

---

## 2. Matriz de Permisos

### 2.1 Páginas permitidas (`whiteList.php` línea 29-37)

| Slug | Vista | Dominio |
|------|-------|---------|
| `inicio` | `selectDashboard.php` → `dashboardAdministrador.php` | Dashboard |
| `internship_companies` | `view/pages/practicas/internship_companies.php` | PP — Organismos |
| `practice_solicitud` | `view/pages/practicas/practice_solicitud.php` | PP — Solicitudes |
| `internship_students` | `view/pages/practicas/internship_students.php` | PP — Estudiantes |
| `internship_events` | `view/pages/practicas/internship_events.php` | PP — Eventos |
| `reports_practices` | `view/pages/practicas/reports_practices.php` | PP — Reportes |
| `internship_areas` | `view/pages/practicas/internship_areas.php` | PP — Áreas internas |

### 2.2 Menú lateral (`menu.php` línea 56-61)

```
📊 Tablero
🏢 Organismos receptores
👔 Estudiantes
🏛️ Áreas internas
```

### 2.3 Acciones de escritura

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| Aceptar/rechazar organismos | `ajax.forms.php` search=`organismos_externos` | |
| Aceptar/rechazar solicitudes PP | `ajax.forms.php` search=`practices` | |
| Aceptar/rechazar alumnos PP | `ajax.forms.php` search=`student` action=`acceptStudentPractice` | |
| Aprobar/rechazar reportes | `ajax.forms.php` search=`reports` | |
| Gestión de áreas internas PP | `practices/areas.php` | |
| Gestión de organismos | `practices/companies.php` | |
| Gestión estudiantes PP | `practices/students.php`, `practices/students_flow.php` | |
| Generar cartas presentación PP | `practices/generarCartaPresentacion.php` | |
| Generar constancias | `practices/generarConstanciaAcreditacion.php` | |
| Export Excel | `practices/export_companies_excel.php` | |

---

## 3. Restricciones

- **No accede** a: `users`, `configs`, `courses`, `degrees`, `events`, `event_types`, `students` (SS), `external_students`, `areas` (SS).
- No puede aprobar documentos IJUMICH.
- No puede crear administradores ni encargados.

---

## 4. Interacciones clave

### 4.1 Tablas MySQL

| Tabla | Operación |
|-------|-----------|
| `organismos_externos` | R/W (aceptar, rechazar, listar) |
| `solicitudes_practicantes` | R/W (aceptar, rechazar) |
| `students_practicas` | R/W (aceptar, rechazar, editar, dar de baja) |
| `students_in_practices` | R (ver postulaciones) |
| `asistencias_practicas` | R (supervisión) |
| `evaluaciones_parciales_practicas` | R (supervisión) |
| `evaluaciones_finales_practicas` | R (supervisión) |
| `reportes_parciales_practicas` | R/W (aprobar/rechazar) |
| `reportes_finales_practicas` | R/W (aprobar/rechazar) |
| `cartas_practicas_profesionales` | R/W (generar folios) |
| `constancias_acreditacion` | R/W (generar folios) |
| `solicitudes_capacitacion` | R/W (aprobar/rechazar) |

### 4.2 Peticiones AJAX principales

```
POST controller/ajax/ajax.forms.php
  search=organismos_externos      → PracticasModel::mdlGetExternals()
  search=practices                → PracticasModel::mdlNewSolicitudesPracticantes()
  search=reports                  → PracticasController::ctrGetParcialReportsAdmin()
  search=student&action=noAceptedStudentsPractice → FormsModel::mdlGetNoAceptedStudentsPractice()

POST controller/practices/companies.php
  action=getOrganismosConEstudiantes
  action=getStudentsByOrganismo
  action=getOrganismoStats
  action=exportOrganismos
  action=exportStudentsAll
```

---

*Generado como parte de la auditoría del sistema — Universidad Montrer · Junio 2026*
