# Rol: Admin Servicio Social (admin_servicio)

> Documento de permisos y accesos · Generado: Junio 2026

---

## 1. Objetivo del Rol

El **admin_servicio** es un administrador con alcance limitado al módulo de **Servicio Social**. Gestiona eventos, estudiantes internos/externos del SS, y áreas. No tiene acceso al módulo de Prácticas Profesionales ni a la configuración del sistema.

Se determina en `config/whiteList.php` (línea 104-105) cuando:
- `$_SESSION['user']['role'] === 'admin'` Y
- `tipo_servicio['type_admin'] === 1`

---

## 2. Matriz de Permisos

### 2.1 Páginas permitidas (`whiteList.php` línea 22-28)

| Slug | Vista | Dominio |
|------|-------|---------|
| `inicio` | `selectDashboard.php` → `dashboardAdministrador.php` | Dashboard |
| `events` | `view/pages/events.php` | SS — Eventos |
| `students` | `view/pages/students.php` | SS — Alumnos internos |
| `external_students` | `view/pages/external_students.php` | SS — Alumnos externos |
| `areas` | `view/pages/areas.php` | SS — Áreas |

### 2.2 Menú lateral (`menu.php` línea 49-55)

```
📊 Tablero
🏀 Áreas
📅 Eventos
👨‍🎓 Estudiantes internos
👔 Estudiantes externos
```

### 2.3 Acciones de escritura

| Acción | Endpoint | Descripción |
|--------|----------|-------------|
| CRUD áreas | `ajax.forms.php` search=`areas` | |
| CRUD eventos | `ajax.forms.php` search=`event` | |
| Aceptar/rechazar alumnos SS | `ajax.forms.php` search=`student` action=`accept*`/`denegateSt*` | |
| Dar de baja alumno | `ajax.forms.php` search=`student` action=`dropStudent` | |
| Aprobar/rechazar IJUMICH | `ajax.forms.php` search=`ijumich_requests` | ⚠️ **Verificación**: solo `role === 'admin'`, no incluye `admin_servicio` |

---

## 3. Restricciones

- **No accede** a: `users`, `configs`, `courses`, `degrees`, `event_types`, ni a ninguna página de PP.
- **Problema detectado**: Las acciones AJAX en `ajax.forms.php` no verifican granularidad de rol → un `admin_servicio` podría invocar acciones de `admin_practicas` o de `admin` completo (ver SEC-004).
- La aprobación IJUMICH (`ijumich_requests`) verifica `role === 'admin'` exacto → un `admin_servicio` **NO puede aprobar** documentos IJUMICH a nivel de endpoint (el `role` en sesión sigue siendo `'admin'` pero el `$role` recalculado en whiteList no se propaga al dispatcher).

---

## 4. Interacciones clave

### 4.1 Tablas MySQL

| Tabla | Operación |
|-------|-----------|
| `events` | CRUD |
| `event_types` | Lectura (para filtrar por `typeService`) |
| `students_events` | R/W (aprobar candidatos) |
| `student` | R/W (aceptar, rechazar, baja) |
| `areas` | CRUD |
| `areas_users` | R/W (asignar usuarios a áreas) |
| `servicio_social_ijumich` | Lectura (ver historial alumnos) |

### 4.2 Peticiones AJAX principales

```
POST controller/ajax/ajax.forms.php
  search=student                  → Lista y gestión de alumnos SS
  search=event                    → Gestión de eventos
  search=areas                    → Gestión de áreas
  search=students_history         → Historial con documentos IJUMICH
  search=AllDataEvents            → Datos consolidados (usuarios, áreas, servicios)
```

---

*Generado como parte de la auditoría del sistema — Universidad Montrer · Junio 2026*
