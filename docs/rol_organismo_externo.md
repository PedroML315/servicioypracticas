# Rol: Organismo Externo (organismo_externo)

> Documento de permisos y accesos · Generado: Junio 2026

---

## 1. Objetivo del Rol

El **organismo_externo** es una empresa u organismo receptor que ofrece plazas para prácticas profesionales. Puede publicar solicitudes de practicantes, aceptar/rechazar postulantes, gestionar asistencias, evaluar participantes y supervisar reportes.

Se registra públicamente en `RegisterEmpresas.php` → `ajax.registroOrganismos.php` → `PracticasModel::saveOrganismoExterno()`. Tras ser aprobado por un admin, recibe credenciales por email.

Se determina en login vía:
- `controller/ajax/ajax.login.php` → `PracticasController::ctrLoginOrganismoReceptor()` → `$_SESSION['user']['role'] === 'organismo_externo'`

La tabla de datos es `organismos_externos`.

---

## 2. Matriz de Permisos

### 2.1 Páginas permitidas (`whiteList.php` línea 75-80)

| Slug | Vista | Dominio |
|------|-------|---------|
| `inicio` | `selectDashboard.php` → `dashboardOrganismo.php` | Dashboard |
| `students_in_practices` | `view/pages/practicas/students_in_practices.php` | Practicantes |

### 2.2 Menú lateral (`menu.php` línea 70-73)

```
📊 Tablero
👨‍🎓 Practicantes
```

---

## 3. Flujo Completo del Organismo

### 3.1 Registro (público)

1. Accede a `RegisterEmpresas.php` (sin login).
2. Llena formulario con datos de la empresa, contacto, representante legal.
3. Puede adjuntar documentos (`docs[]`).
4. Se envía a `ajax.registroOrganismos.php` con rate limiting (5/hora por sesión).
5. Queda en estado `isAcepted = 0` (pendiente).
6. Admin lo aprueba → se genera contraseña → se envía por email.

### 3.2 Publicar solicitudes de practicantes

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| Crear solicitud | `organismo/forms.php` action=`solicitarPracticas` | Licenciatura, # practicantes, actividades, horario, etc. |
| Editar solicitud | `organismo/forms.php` action=`updateSolicitud` | Verificación de propiedad ✅ |
| Eliminar solicitud | `organismo/forms.php` action=`deleteSolicitud` | Soft delete (`activo = 0`) ✅ |
| Ver solicitudes propias | `organismo/forms.php` action=`getSolicitudes` | Filtrado por `$_SESSION['user']['id']` |
| Ver solicitud por ID | `organismo/forms.php` action=`getSolicitudById` | Verificación de propiedad ✅ |

### 3.3 Gestionar postulantes

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| Aceptar prospecto | `organismo/forms.php` action=`aceptarProspecto` | Asigna fecha de inicio ✅ |
| Rechazar prospecto | `organismo/forms.php` action=`rechazarProspecto` | Envía email al alumno ✅ |
| Ver todos los practicantes | `organismo/forms.php` action=`getAllPractices` | Datos sin password ✅ |
| Datos de un practicante | `organismo/forms.php` action=`getDataPracticesStudent` | |

### 3.4 Gestión de asistencias

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| Ver asistencias | `organismo/forms.php` action=`getAssistancesPractices` | Filtrado por organismo |
| Aprobar asistencia | `organismo/forms.php` action=`aprobarAsistencia` | ⚠️ Sin verificación de propiedad |
| Rechazar asistencia | `organismo/forms.php` action=`rechazarAsistencia` | ⚠️ Sin verificación de propiedad |
| Actualizar horarios | `organismo/forms.php` action=`actualizarHorarios` | ⚠️ Sin verificación de propiedad |

### 3.5 Evaluaciones

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| Evaluación parcial | `organismo/forms.php` action=`evaluarParticipanteParcial` | 6 rubros + 10 actitudes + fortalezas/debilidades |
| Evaluación final | `organismo/forms.php` action=`evaluarParticipanteFinal` | Misma estructura |

**Rúbricas de evaluación** (definidas en `PracticasModel` constantes):

**Rubros** (6):
1. Asistencia y puntualidad
2. Aplicación de conocimientos
3. Contribución a la solución de problemas
4. Iniciativa
5. Responsabilidad
6. Logro de objetivos planteados

**Actitudes** (10):
1. Interés en identificar y subsanar necesidades de aprendizaje
2. Cooperación eficiente en equipo
3. Tolerancia ante diferencias
4. Expresión verbal y escrita clara
5. Búsqueda de soluciones dentro de normas
6. Organización y priorización de actividades
7. Búsqueda de información adicional
8. Adaptación a situaciones nuevas
9. Automotivación y dedicación
10. Receptividad ante la autoridad

### 3.6 Reportes

| Acción | Endpoint |
|--------|----------|
| Ver reportes parciales/finales | `organismo/forms.php` action=`getReportsPractices` |
| Aceptar reporte parcial | `organismo/forms.php` action=`acceptReport` |
| Rechazar reporte parcial | `organismo/forms.php` action=`rejectReport` |
| Aceptar reporte final | `organismo/forms.php` action=`acceptReportFinal` |
| Rechazar reporte final | `organismo/forms.php` action=`rejectReportFinal` |

### 3.7 Capacitaciones

| Acción | Endpoint |
|--------|----------|
| Solicitar capacitación | `organismo/forms.php` action=`solicitarCapacitacion` |
| Ver solicitudes | `organismo/forms.php` action=`buscarSolicitudesCapacitacion` |

### 3.8 Dashboard

| Acción | Endpoint |
|--------|----------|
| Resumen del dashboard | `organismo/forms.php` action=`getDashboardSummary` |
| Prospectos recientes | `organismo/forms.php` action=`getRecentProspectos` |

---

## 4. Restricciones

- Solo accede a `inicio` y `students_in_practices`.
- No puede ver otros organismos ni datos de administradores.
- La verificación de propiedad existe en CRUD de solicitudes ✅ pero **falta** en asistencias y evaluaciones (SEC-006).
- El password se elimina de las respuestas JSON antes de enviarlas ✅ (línea 123-125).
- `session_timeout`: 30 minutos.

---

## 5. Interacciones clave

### 5.1 Tablas MySQL

| Tabla | Operación |
|-------|-----------|
| `organismos_externos` | R (datos propios) |
| `solicitudes_practicantes` | CRUD (sus propias solicitudes) |
| `students_in_practices` | R/W (aceptar/rechazar postulantes) |
| `students_practicas` | R (ver datos de practicantes asignados) |
| `asistencias_practicas` | R/W (aprobar/rechazar/editar horarios) |
| `evaluaciones_parciales_practicas` | W (crear evaluación parcial) |
| `evaluaciones_finales_practicas` | W (crear evaluación final) |
| `reportes_parciales_practicas` | R/W (aprobar/rechazar) |
| `reportes_finales_practicas` | R/W (aprobar/rechazar) |
| `solicitudes_capacitacion` | R/W (solicitar, ver estado) |
| `notifications` | R (notificaciones recibidas) |

### 5.2 Peticiones AJAX principales

Todas van a `controller/organismo/forms.php`:

```
POST controller/organismo/forms.php
  action=getSolicitudes
  action=solicitarPracticas
  action=updateSolicitud
  action=deleteSolicitud
  action=aceptarProspecto
  action=rechazarProspecto
  action=getAllPractices
  action=getAssistancesPractices
  action=evaluarParticipanteParcial
  action=evaluarParticipanteFinal
  action=getReportsPractices
  action=acceptReport / rejectReport
  action=acceptReportFinal / rejectReportFinal
  action=getDashboardSummary
  action=getRecentProspectos
  action=solicitarCapacitacion
  action=buscarSolicitudesCapacitacion
```

### 5.3 Seguridad del endpoint

| Aspecto | Estado |
|---------|--------|
| Autenticación (sesión) | ✅ `session_start()` al inicio |
| Verificación de propiedad en solicitudes | ✅ `organismo_externo_id === $_SESSION['user']['id']` |
| Verificación de propiedad en asistencias | ❌ Falta (SEC-006) |
| Verificación de propiedad en evaluaciones | ❌ No verifica que el alumno pertenezca al organismo |
| CSRF | ❌ No implementado |
| Protección de password en respuestas | ✅ `unset($practica['password'])` |

---

*Generado como parte de la auditoría del sistema — Universidad Montrer · Junio 2026*
