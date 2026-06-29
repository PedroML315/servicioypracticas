# Rol: Alumno Prácticas (alumno_practicas)

> Documento de permisos y accesos · Generado: Junio 2026

---

## 1. Objetivo del Rol

El **alumno_practicas** es un estudiante registrado exclusivamente en el módulo de **Prácticas Profesionales**. Su flujo es independiente del Servicio Social: busca organismos, se postula a prácticas, registra asistencia, sube reportes (parcial/final) y finalmente obtiene su constancia de acreditación.

Se determina en login vía:
- `controller/ajax/ajax.login.php` → `PracticasController::ctrLoginAlumnoPracticas()` → `$_SESSION['user']['role'] === 'alumno_practicas'`

La tabla de datos es `students_practicas` (no `student` que es para SS).

---

## 2. Matriz de Permisos

### 2.1 Páginas permitidas (`whiteList.php` línea 69-74)

| Slug | Vista | Dominio |
|------|-------|---------|
| `inicio` | `selectDashboard.php` → `dashboardStudent.php` | Dashboard PP |

### 2.2 Menú lateral (`menu.php` línea 69)

```
📊 Tablero
```

(El alumno opera enteramente desde su tablero, con tabs y modales)

---

## 3. Flujo Completo del Alumno PP

### 3.1 Registro (público — sin autenticación)

1. El alumno ingresa a la página de registro PP (sin login).
2. Se busca su matrícula en SQL Server GES vía `checkMatricula` → `GESModel::mdlSearchStudentGES()`.
3. Se autocompletan datos académicos y se registra vía `registerStudentPracticas` → `FormsModel::mdlRegisterStudentPracticas()`.
4. Queda en estado `isAcepted = 0` (pendiente).
5. Admin lo aprueba → se genera contraseña → se envía por email → alumno puede iniciar sesión.

### 3.2 Postulación a prácticas

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| Ver prácticas disponibles | `ajax.forms.php` search=`practices_available` | Filtrado por `tipo_practica` |
| Postularse | `ajax.forms.php` search=`student` action=`applyForPractice` | Crea registro en `students_in_practices` |
| Ver estado postulación | Dashboard | Estado: pendiente/aceptado/rechazado |

### 3.3 Registro de asistencia

Una vez aceptado por un organismo, el alumno registra asistencia diaria:

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| Registrar asistencia | `practices/students.php` action=`registerAttendance` | Fecha, hora entrada/salida, actividad |
| Ver asistencias | Dashboard → tab de asistencias | `PracticasModel::mdlCheckAssistance()` |

### 3.4 Reportes

| Documento | Tipo | Endpoint |
|-----------|------|----------|
| Reporte parcial | Upload PDF | `practices/students_flow.php` action=`uploadPartialReport` |
| Reporte final | Upload PDF | `practices/students_flow.php` action=`uploadFinalReport` |

### 3.5 Documentos generados (descargables)

| Documento | Condición | Generado por |
|-----------|-----------|--------------|
| Carta de presentación | Tras aceptación del organismo | Admin (PDF) |
| Constancia de acreditación | Tras finalización y aprobación | Admin (PDF + folio) |

---

## 4. Restricciones

- Solo accede a la página `inicio`.
- No puede ver datos de otros alumnos PP.
- No puede interactuar con el módulo de Servicio Social.
- El `idStudent` se toma de la sesión (`$_SESSION['user']['id']`).
- `session_timeout`: 30 minutos.
- Los archivos subidos se validan por MIME y tamaño.

---

## 5. Interacciones clave

### 5.1 Tablas MySQL

| Tabla | Operación |
|-------|-----------|
| `students_practicas` | R (datos propios) |
| `solicitudes_practicantes` | R (ver prácticas disponibles) |
| `students_in_practices` | R/W (postulación, estado) |
| `asistencias_practicas` | R/W (registrar/ver asistencias) |
| `reportes_parciales_practicas` | R/W (subir reportes) |
| `reportes_finales_practicas` | R/W (subir reporte final) |
| `organismos_externos` | R (datos del organismo asignado) |
| `cartas_practicas_profesionales` | R (carta generada por admin) |
| `constancias_acreditacion` | R (constancia generada por admin) |

### 5.2 Peticiones AJAX principales

```
POST controller/ajax/ajax.forms.php
  search=practices_available        → Solicitudes activas filtradas
  search=student&action=applyForPractice → Postularse a práctica
  action=checkMatricula             → Búsqueda en SQL Server GES

POST controller/practices/students.php
  action=registerAttendance         → Registrar asistencia

POST controller/practices/students_flow.php
  action=uploadPartialReport        → Subir reporte parcial
  action=uploadFinalReport          → Subir reporte final
```

### 5.3 Dashboard (`dashboardStudent.php`)

El dashboard muestra:
- Datos personales del alumno
- Estado de la postulación (si está pendiente, aceptado, rechazado)
- Horas acumuladas de asistencia
- Reportes subidos y su estado
- Evaluaciones del organismo
- Botón para descargar carta de presentación / constancia

---

*Generado como parte de la auditoría del sistema — Universidad Montrer · Junio 2026*
