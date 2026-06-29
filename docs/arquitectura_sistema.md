# Arquitectura del Sistema — Servicio Social y Prácticas Profesionales UNIMO

> Documento maestro de auditoría · Generado: Junio 2026
> Workspace: `servicioypracticas.unimontrer.edu.mx`

---

## 1. Propósito y Arquitectura

### 1.1 Lógica de negocio

El sistema gestiona el **ciclo completo** de dos procesos académicos de la Universidad Montrer (UNIMO):

1. **Servicio Social (SS)**: Registro de alumnos, asignación a eventos con puntos, flujo IJUMICH de 9 pasos (documentos, cartas auto-generadas, reportes parciales, evaluaciones, liberación).
2. **Prácticas Profesionales (PP)**: Registro de organismos externos, solicitudes de practicantes, postulaciones, asistencias, evaluaciones (parcial/final con rúbricas), reportes, constancias y cartas de presentación.

### 1.2 Stack tecnológico

| Capa | Tecnología |
|------|------------|
| Backend | PHP ≥ 8.2 (monolito, sin framework) |
| BD principal | MySQL 5.7 (`servicio_social`) vía PDO |
| BD externas | SQL Server (SIL + GES) vía `sqlsrv` PDO |
| Frontend | HTML/CSS/JS vanilla, jQuery 3.6, DataTables, Chart.js 4.4, SweetAlert2, Bootstrap grid |
| PDF | dompdf 3.1, FPDF/FPDI |
| Email | PHPMailer 6.9 + cola `email_queue` |
| Servidor web | IIS + URL Rewrite (`web.config`) |
| Sesión/Seguridad | `config/Security.php` (CSRF, timeout 30min, headers CSP, rate limiting login) |

### 1.3 Patrón de diseño — MVC Informal

```
┌──────────────────────────────────────────────────────────────┐
│  CLIENTE (Browser)                                           │
│  jQuery/JS → POST AJAX → controller/ajax/*.php               │
└──────────────┬───────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────┐
│  ROUTER + ACL                                                │
│  index.php → ControllerTemplate → view/dashboard.php         │
│            → config/whiteList.php (whitelist por rol)         │
│            → config/Security.php (init, CSRF, session)       │
└──────────────┬───────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────┐
│  CONTROLADORES                                               │
│  controller/forms.controller.php                             │
│    ├─ FormsController (SS: usuarios, eventos, alumnos)       │
│    ├─ PracticasController (PP: organismos, solicitudes)      │
│    ├─ GESController (búsqueda matrícula SQL Server)          │
│    └─ ServicioController (tipos de servicio)                 │
│  controller/ajax/ajax.forms.php (dispatcher central)         │
│  controller/organismo/forms.php (API organismo externo)      │
│  controller/practices/ (API admin PP)                        │
│  controller/emails.php (PHPMailer + plantillas HTML)         │
└──────────────┬───────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────┐
│  MODELOS                                                     │
│  model/generalModels.php → FormsModel (1452 líneas)          │
│  model/PracticasModel.php (2317 líneas)                      │
│  model/ServicioModel.php (508 líneas)                        │
│  model/GesModel.php (SQL Server GES)                         │
│  model/FormsModelPDF.php (generación PDFs)                   │
│  model/EmailsModel.php (plantillas email_templates)          │
│  model/LogModel.php (auditoría logs)                         │
│  model/notifications.php (notificaciones in-app)             │
│  model/conection.php → Conexion (MySQL + SIL + GES)          │
└──────────────┬───────────────────────────────────────────────┘
               │
┌──────────────▼───────────────────────────────────────────────┐
│  BASES DE DATOS                                              │
│  MySQL: servicio_social (Conexion::conectar)                 │
│  SQL Server SIL: datos académicos (Conexion::conectarSIL)    │
│  SQL Server GES: búsqueda alumnos (Conexion::conectarGES)    │
└──────────────────────────────────────────────────────────────┘
```

### 1.4 Flujo de una solicitud típica

```
Browser GET /internship_companies
  → web.config rewrite → index.php?pagina=internship_companies
  → ControllerTemplate::ctrBringTemplate()
  → view/dashboard.php
  → config/whiteList.php
       · Security::init() + session_start()
       · Security::checkSessionExpiry()
       · Mapeo rol (admin → admin_servicio|admin_practicas por type_admin)
       · Whitelist de ?pagina= por rol
  → view/pages/navs/header.php + sidebar + practicas/internship_companies.php
  → JS POST a controller/ajax/ajax.forms.php
       · search=organismos_externos | practices | reports | ...
  → PracticasController → PracticasModel → Conexion::conectar() → MySQL
  → JSON response → DataTables / SweetAlert
```

### 1.5 Mapa de archivos clave

| Archivo | Función | Líneas |
|---------|---------|--------|
| `index.php` | Entry point, carga template controller | 8 |
| `config/whiteList.php` | Router + ACL por rol | 180 |
| `config/Security.php` | Seguridad centralizada | 332 |
| `config/menu.php` | Configuración de menú lateral por rol | 76 |
| `config/selectDashboard.php` | Dashboard routing por rol | 52 |
| `controller/ajax/ajax.forms.php` | **Dispatcher AJAX central** | 1482 |
| `controller/ajax/ajax.login.php` | Login multi-tipo | 29 |
| `controller/forms.controller.php` | Controladores principales | 1278 |
| `controller/organismo/forms.php` | API organismo externo | 264 |
| `controller/practices/companies.php` | API admin organismos PP | ~200 |
| `model/generalModels.php` | FormsModel (SS core) | 1452 |
| `model/PracticasModel.php` | Prácticas profesionales | 2317 |
| `model/ServicioModel.php` | Servicio social IJUMICH | 508 |
| `model/conection.php` | Conexiones BD | 74 |

---

## 2. Auditoría de Seguridad y Bugs

### 2.1 Resumen de hallazgos

| Severidad | Hallazgos | Estado |
|-----------|-----------|--------|
| 🔴 CRÍTICA | 3 | Abiertos |
| 🟠 ALTA | 5 | Abiertos |
| 🟡 MEDIA | 6 | Parcialmente mitigados |
| 🔵 BAJA | 4 | Informativos |

---

### 🔴 CRÍTICA — SEC-001: Inyección SQL por interpolación de nombre de tabla

**Archivo**: `model/generalModels.php`
**Función**: `mdlShowUser()` — Línea 201
**Función**: `mdlRegisterUser()` — Línea 10

```php
// Línea 201 — generalModels.php
$stmt = Conexion::conectar()->prepare("SELECT * FROM $table u LEFT JOIN ...");
// Línea 10 — generalModels.php
$stmt = $pdo->prepare("INSERT INTO $table (firstname, ...) VALUES (...)");
```

**Riesgo**: El parámetro `$table` se recibe directamente del controlador (`FormsController::ctrLogin` pasa `"users"` como literal), pero la firma del método acepta cualquier string. Si algún call-site futuro pasa datos de usuario, permite SQLi.

**Mitigación en PracticasModel**: `mdlShowUsersPP()` (línea 550-558) ya implementa `isSafeIdent()` con regex whitelist. **FormsModel no tiene esta protección**.

**Remediación**: Aplicar whitelist de tablas permitidas o eliminar el parámetro `$table` usando métodos específicos por tabla.

---

### 🔴 CRÍTICA — SEC-002: Falta de validación CSRF en dispatcher AJAX central

**Archivo**: `controller/ajax/ajax.forms.php`
**Líneas**: 456–1482 (todo el dispatcher)

El dispatcher principal `ajax.forms.php` **no invoca** `Security::validateCsrf()` en ningún punto. La clase `Security` tiene los métodos implementados (`validateCsrf()`, `getCsrfToken()`, `csrfField()`), pero **no se usan** en el endpoint más crítico del sistema.

**Endpoints afectados**: Todas las acciones de escritura del dispatcher: crear/editar/eliminar usuarios, eventos, estudiantes, áreas, cursos, grados, aprobar/rechazar documentos IJUMICH, cargar archivos.

**Endpoints parcialmente protegidos**:
- `controller/upload-image.php` (línea 27-31): ✅ Valida CSRF via header `X-CSRF-TOKEN`
- `controller/organismo/forms.php`: ❌ Sin CSRF
- `controller/practices/companies.php`: ❌ Sin CSRF

**Remediación**: Agregar `Security::validateCsrf()` después de la verificación de sesión en `ajax.forms.php` línea 469.

---

### 🔴 CRÍTICA — SEC-003: Generación insegura de contraseñas con `rand()`

**Archivo**: `controller/forms.controller.php`
**Función**: `generateRandomPassword()` — Líneas 13-21

```php
function generateRandomPassword($length = 10) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)]; // ← rand() NO es CSPRNG
    }
    return $password;
}
```

**Riesgo**: `rand()` es predecible. Las contraseñas generadas por esta función se envían por email a **todos los nuevos usuarios, estudiantes y organismos** tras su aceptación.

**Nota**: `Security::generatePassword()` (Security.php línea 290) ya usa `random_int()` pero **no se usa en ningún call-site**. La función global `generateRandomPassword()` es la que se invoca en todo el código.

**Remediación**: Reemplazar todos los call-sites de `generateRandomPassword()` por `Security::generatePassword()`.

---

### 🟠 ALTA — SEC-004: Falta de verificación de rol granular en acciones AJAX

**Archivo**: `controller/ajax/ajax.forms.php`
**Líneas**: 469-476 (guard de sesión), 478-976 (switch de search)

El dispatcher verifica que `$_SESSION['logged']` exista (línea 469), pero **no valida el rol** del usuario para la mayoría de acciones. Ejemplo:

- Un `organismo_externo` autenticado podría invocar `search=users` (línea 478) y obtener la lista completa de administradores.
- Un `alumno_practicas` podría invocar `search=student&action=dropStudent` (línea 586) para dar de baja a cualquier estudiante.
- Un `student` podría invocar `search=ijumich_requests&action=approveRequest` (línea 808) — aunque este caso particular verifica `role === 'admin'` en línea 798.

**Excepciones bien protegidas**:
- `ijumich_requests` (línea 797-801): Verifica `role === 'admin'` ✅
- Registro de usuario vía POST directo (línea 1461): Verifica `role === 'admin'` ✅
- Agregar grado (línea 1471): Verifica `role === 'admin'` ✅

**Remediación**: Agregar `Security::requireRole(...)` al inicio de cada `case` del switch según la matriz de permisos definida en `whiteList.php`.

---

### 🟠 ALTA — SEC-005: Exposición de mensajes de error de BD al cliente

**Archivo**: `model/conection.php`
**Funciones**: `conectarSIL()` línea 49, `conectarGES()` línea 70

```php
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage(); // ← expone info sensible
}
```

**Riesgo**: Revela nombres de servidor, base de datos, credenciales parciales al navegador del usuario si la conexión SQL Server falla.

**Archivo adicional**: `controller/ajax/ajax.registroOrganismos.php` línea 191-194 expone `$e->getMessage()` completo.

**Remediación**: Registrar en `error_log()` y devolver mensaje genérico al cliente.

---

### 🟠 ALTA — SEC-006: Falta de validación de propiedad (IDOR) en endpoints de alumno

**Archivo**: `controller/ajax/ajax.forms.php`
**Acciones afectadas** (dentro de `search=student`):
- `acceptStudent` (línea 569): Acepta `$_POST['idStudent']` sin verificar que el admin tiene permiso sobre ese alumno.
- `dropStudent` (línea 587): Usa `$_POST['idStudent']` y `$_POST['reason']` directamente.

**Archivo**: `controller/organismo/forms.php`
**Acciones parcialmente protegidas**: `getSolicitudById`, `updateSolicitud`, `deleteSolicitud`, `aceptarProspecto`, `rechazarProspecto` — todas verifican `organismo_externo_id === $_SESSION['user']['id']` ✅

**Acciones NO protegidas**: `aprobarAsistencia` (línea 147), `rechazarAsistencia` (línea 152), `actualizarHorarios` (línea 156) — aceptan IDs sin verificar que la asistencia pertenece al organismo autenticado.

---

### 🟠 ALTA — SEC-007: Falta de autenticación en endpoint de registro de organismos

**Archivo**: `controller/ajax/ajax.registroOrganismos.php`
**Líneas**: 1-196

Este endpoint es público intencionalmente (registro de nuevos organismos), pero:
- El rate limiting es **por sesión** (línea 10-23), no por IP real — un atacante puede evadir simplemente no enviando cookies de sesión.
- No hay CAPTCHA ni honeypot para prevenir spam masivo de registros.
- Acepta archivos `docs[]` (línea 136) que se guardan sin verificación de tamaño individual (solo se valida MIME y extensión).

---

### 🟠 ALTA — SEC-008: `session_start()` llamado múltiples veces

**Archivo**: `controller/ajax/ajax.forms.php`
**Líneas**: 13, 769, 797, 942, 1014, 1030, 1039, 1088, 1139, 1190, 1198, 1240, 1287, 1296, 1338, 1378, 1418, 1460, 1470

`session_start()` se llama una vez al inicio (línea 13) y luego **17 veces más** dentro de distintos `case` del switch. Si bien PHP ignora las llamadas duplicadas si la sesión ya está activa, genera notices/warnings en configuraciones estrictas y es indicador de código desordenado.

---

### 🟡 MEDIA — SEC-009: Configuración PDO sin modo de error en MySQL

**Archivo**: `model/conection.php`
**Función**: `conectar()` — Líneas 19-30

```php
static public function conectar() {
    $link = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $link->exec("set names utf8");
    return $link;  // Sin ATTR_ERRMODE → modo silencioso por defecto
}
```

**Contraste**: `PracticasModel::db()` (PracticasModel.php línea 34-55) sí configura `ERRMODE_EXCEPTION`, `FETCH_ASSOC` y deshabilita `EMULATE_PREPARES`. `FormsModel` usa `Conexion::conectar()` sin estas protecciones.

**Remediación**: Agregar en `conectar()`:
```php
$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$link->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
```

---

### 🟡 MEDIA — SEC-010: `mdlActivateCourse` ejecuta multi-statement sin transacción

**Archivo**: `model/generalModels.php`
**Función**: `mdlActivateCourse()` — Líneas 464-481

```php
$sql = "UPDATE courses SET active = 0;";
$sql .= "UPDATE courses SET active = 1 WHERE idCourse = :idCourse;";
```

**Riesgo**: Dos statements en un solo `prepare()`. PDO por defecto no ejecuta multi-queries; esto puede fallar silenciosamente o ejecutar solo el primer UPDATE.

**Remediación**: Separar en dos queries o usar una transacción.

---

### 🟡 MEDIA — SEC-011: Ausencia de `SameSite=Strict` para cookies sensibles

**Archivo**: `config/Security.php`
**Función**: `init()` — Línea 37

Se usa `SameSite=Lax`, que protege solo parcialmente contra CSRF. Dado que el sistema no tiene formularios cross-origin legítimos, `Strict` sería más apropiado.

---

### 🟡 MEDIA — SEC-012: Folio `generateFolio` en ServicioModel sin protección de race condition

**Archivo**: `model/ServicioModel.php`
**Función**: `generateFolio()` — Líneas 6-49

A diferencia de `PracticasModel::generateFolioGeneric()` (que usa `GET_LOCK()`), `ServicioModel::generateFolio()` no tiene lock. Dos requests simultáneos pueden generar el mismo número de folio.

**La misma vulnerabilidad existe en**: `generateFolioAceptacion()` (línea 278-310).

---

### 🟡 MEDIA — SEC-013: CSP permite `unsafe-inline` para scripts y estilos

**Archivo**: `config/Security.php`
**Función**: `sendSecurityHeaders()` — Líneas 62-63

```
script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net ...
style-src  'self' 'unsafe-inline' ...
```

`unsafe-inline` anula gran parte de la protección CSP contra XSS. Es un compromiso necesario por el uso masivo de JS inline en las vistas, pero debería documentarse como deuda técnica.

---

### 🟡 MEDIA — SEC-014: No se regenera sesión tras login

**Archivo**: `controller/forms.controller.php`
**Funciones**: `ctrLogin()` (línea 26-51), `ctrLoginStudentService()` (línea 53-71)

Tras un login exitoso se establece `$_SESSION['logged']` y `$_SESSION['last_activity']`, pero **no se llama a** `Security::regenerateSession()` (que existe en Security.php línea 152). Esto deja vulnerable a session fixation.

**Mismo problema en**: `PracticasController::ctrLoginOrganismoReceptor()` (línea 514-532) y `ctrLoginAlumnoPracticas()` (línea 534-552).

---

### 🔵 BAJA — SEC-015: Falta de sanitización en salida de datos en vistas

**Contexto general**: Las vistas en `view/pages/` generan HTML con datos de sesión y respuestas AJAX. La mayoría del renderizado se hace vía JavaScript (`innerHTML`, DataTables), donde jQuery/DataTables escapan por defecto. Sin embargo, algunas vistas PHP embeben datos directamente con `<?= $variable ?>` sin `htmlspecialchars()`.

**Mitigación existente**: El header `X-XSS-Protection: 1; mode=block` (Security.php línea 54) ofrece protección parcial en navegadores legacy.

---

### 🔵 BAJA — SEC-016: Archivos de upload accesibles sin autenticación

**Directorio**: `uploads/`

Los archivos subidos por usuarios (PDFs de documentos IJUMICH, evaluaciones, documentos de organismos) son accesibles directamente vía URL si se conoce la ruta (`/uploads/{idStudent}/{filename}`). No hay `.htaccess`/`web.config` que restrinja acceso.

---

### 🔵 BAJA — SEC-017: `composer.lock` y `package.json` expuestos

Los archivos `composer.lock`, `package.json`, `package-lock.json` son accesibles públicamente y revelan versiones exactas de dependencias, facilitando la identificación de vulnerabilidades conocidas.

---

### 🔵 BAJA — SEC-018: Cola de correos protegida solo por token estático

**Archivo**: `controller/process_email_queue.php`
**Protección**: Token `EMAIL_QUEUE_SECRET` en query string.

El token es estático y viaja en la URL. Si el cron job se configura con logs visibles, el token queda expuesto.

---

## 3. Optimización y Deuda Técnica

### 3.1 Consultas MySQL — Cuellos de botella

#### OPT-001: Consulta N+1 en creación de eventos

**Archivo**: `model/generalModels.php`
**Función**: `mdlAddEvent()` — Líneas 269-311

```php
$students = self::mdlSearchStudents(null); // Carga TODOS los alumnos
foreach ($students as $student) {
    $points = self::mdlStudentPoints($student['idStudent']); // ← N+1 query por alumno
    // ... envía email ...
}
```

**Impacto**: Si hay 500 alumnos activos, se ejecutan **501 queries** (1 + N) al crear un evento. Además, se intenta enviar un email por cada alumno elegible de forma síncrona.

**Remediación**: Consolidar en una sola query con `JOIN` + subquery de puntos.

---

#### OPT-002: `mdlGetStudentsWithHistory()` — Consulta compleja sin índices

**Archivo**: `model/generalModels.php`
**Función**: `mdlGetStudentsWithHistory()` — Líneas 835-881

Usa `JSON_ARRAYAGG`, `GROUP_CONCAT`, y dos subqueries con `UNION ALL` sobre `servicio_social_ijumich` y `cartas_conclusion_servicio`. Sin índice compuesto en `servicio_social_ijumich(student_id, tipo, status, created_at)`, esta query será lenta con volumen.

---

#### OPT-003: Conexiones PDO no reutilizadas en FormsModel

**Archivo**: `model/generalModels.php`
**Problema**: Cada método estático llama `Conexion::conectar()`, que crea una **nueva instancia PDO** cada vez (no hay cache/singleton).

**Contraste**: `PracticasModel` tiene cache con `private static $pdo = null` (línea 10) y método `db()` que reutiliza la conexión. `FormsModel` no tiene equivalente.

**Impacto**: En un request que invoca múltiples métodos de `FormsModel`, se abren N conexiones MySQL innecesarias.

---

#### OPT-004: `mdlStudentEventsPoints()` — Subquery correlacionada

**Archivo**: `model/generalModels.php`
**Función**: `mdlStudentEventsPoints()` — Líneas 1205-1220

```sql
SELECT se.points, se.idEvent, e.eventName,
    (SELECT d.minPoints FROM degrees d LEFT JOIN student s ON s.idDegree = d.idDegree
     WHERE s.idStudent = :idStudent) as minPoints
FROM students_events se ...
```

La subquery se ejecuta por **cada fila** del resultado. Debería ser un JOIN o CTE.

---

### 3.2 Generación de reportes — PDF/Excel

#### OPT-005: PDFs generados de forma síncrona

Los endpoints de generación de PDF (`generarCartaConclusionServicio.php`, `generarCartaPresentacion.php`, etc.) procesan el PDF en el mismo request HTTP. Para documentos con imágenes grandes (firma, sello), esto puede superar los timeouts del servidor.

**Propuesta**: Implementar generación asíncrona con cola de trabajo (similar a `email_queue`).

---

#### OPT-006: Export Excel con toda la data en memoria

**Archivo**: `controller/practices/export_companies_excel.php` (11,796 bytes)

La exportación carga todos los organismos y alumnos en memoria antes de escribir el Excel con PhpSpreadsheet. Sin paginación ni streaming.

**Propuesta**: Usar `PhpSpreadsheet\Writer\Xlsx` en modo streaming o implementar paginación server-side.

---

### 3.3 Deuda técnica — Código

#### DEBT-001: `ajax.forms.php` es un God Object (1,482 líneas)

El dispatcher central maneja **todas** las acciones del sistema en un solo archivo con un switch masivo. Esto dificulta:
- Testing unitario
- Revisión de seguridad por acción
- Mantenimiento por múltiples desarrolladores

**Propuesta**: Separar en archivos por dominio (`ajax.students.php`, `ajax.events.php`, `ajax.practices.php`, `ajax.ijumich.php`).

---

#### DEBT-002: Duplicación de lógica de upload de archivos

Las acciones `cargar_carta_practicas_ijumich`, `cargar_carta_liberacion_ijumich`, `cargar_reporte_parcial`, `cargar_solicitud_registro`, `cargar_carta_liberacion_interno`, `cargar_evaluacion_unidad_productiva`, `cargar_evaluacion_global` (ajax.forms.php líneas 1087-1453) tienen **bloques casi idénticos** de:
1. Verificar sesión
2. Verificar `$_FILES`
3. Validar tamaño
4. Validar MIME
5. Generar nombre seguro
6. `move_uploaded_file()`
7. Guardar en BD

**Propuesta**: Extraer a función helper `uploadIjumichDocument($fileKey, $tipo, $studentId)`.

---

#### DEBT-003: Tres funciones `stampPdf*` casi idénticas

**Archivo**: `controller/ajax/ajax.forms.php` — Funciones:
- `stampPdfWithFirmaYSello()` (línea 22)
- `stampEvaluacionUnidadProductiva()` (línea 118)
- `stampEvaluacionGlobal()` (línea 247)
- `stampSolicitudRegistroBotDer()` (línea 366)

Cuatro funciones de ~100 líneas cada una con ~80% de código duplicado. La única diferencia son las posiciones de firma/sello y los campos JSON.

---

#### DEBT-004: No hay pruebas automatizadas

No existen archivos de test (`tests/`, `phpunit.xml`, etc.) en el workspace. Todo el testing se hace manualmente.

---

#### DEBT-005: Falta `.env.example`

No hay archivo de plantilla de variables de entorno. Nuevos desarrolladores no saben qué variables configurar.

---

### 3.4 Índices MySQL recomendados

Basado en las queries más frecuentes del codebase:

```sql
-- Para mdlGetStudentsWithHistory / mdlGetExternalStudentsWithHistory
CREATE INDEX idx_ssi_student_tipo ON servicio_social_ijumich (student_id, tipo, status, created_at);
CREATE INDEX idx_ccs_student ON cartas_conclusion_servicio (student_id, created_at);

-- Para mdlStudentPoints (query más frecuente del dashboard alumno)
CREATE INDEX idx_se_student_status ON students_events (idStudent, statusEvent, points);

-- Para mdlGetPractices (dashboard alumno PP)
CREATE INDEX idx_sip_practica_student ON students_in_practices (idPractica, idStudent, isAcepted);

-- Para mdlGetAssistancesPractices
CREATE INDEX idx_ap_student_practica ON asistencias_practicas (idStudent, idPractica, status);

-- Para login_attempts (rate limiting)
CREATE INDEX idx_la_identifier ON login_attempts (identifier, ip, blocked_until);
```

---

## 4. Inventario de tablas MySQL principales

| Tabla | Dominio | Uso principal |
|-------|---------|---------------|
| `users` | Core | Administradores, encargados (teachers) |
| `admin_type` | Core | Tipo de admin (1=SS, 2=PP) |
| `users_to_tipo_servicio` | Core | Relación admin↔tipo servicio |
| `student` | SS | Alumnos de servicio social |
| `events` | SS | Eventos con puntos |
| `event_types` | SS | Catálogo de tipos de evento |
| `students_events` | SS | Inscripciones alumno↔evento |
| `courses` | SS | Ciclos escolares |
| `degrees` | SS | Licenciaturas con puntos mínimos |
| `areas` | SS | Áreas administrativas |
| `areas_users` | SS | Relación usuario↔área |
| `tipo_servicio` | SS | Tipos de servicio (interno/externo) |
| `servicio_social_ijumich` | SS | Flujo IJUMICH de documentos |
| `cartas_servicio_social` | SS | Folios de cartas SS |
| `cartas_aceptacion_servicio` | SS | Folios de cartas de aceptación |
| `cartas_conclusion_servicio` | SS | Cartas de conclusión generadas |
| `organismos_externos` | PP | Organismos receptores |
| `solicitudes_practicantes` | PP | Solicitudes de practicantes |
| `students_practicas` | PP | Alumnos de prácticas profesionales |
| `students_in_practices` | PP | Relación alumno↔práctica |
| `asistencias_practicas` | PP | Registros de asistencia |
| `evaluaciones_parciales_practicas` | PP | Evaluaciones parciales (rúbricas) |
| `evaluaciones_finales_practicas` | PP | Evaluaciones finales |
| `reportes_parciales_practicas` | PP | Reportes parciales PP |
| `reportes_finales_practicas` | PP | Reportes finales PP |
| `cartas_practicas_profesionales` | PP | Folios cartas PP |
| `constancias_acreditacion` | PP | Constancias de acreditación PP |
| `solicitudes_capacitacion` | PP | Solicitudes de capacitación |
| `email_queue` | Email | Cola de correos pendientes |
| `email_templates` | Email | Plantillas de correo |
| `login_attempts` | Seguridad | Rate limiting de login |
| `logs` | Auditoría | Registro de acciones |
| `notifications` | Core | Notificaciones in-app |

---

## 5. Mapa de endpoints AJAX completo

### 5.1 Dispatcher principal: `controller/ajax/ajax.forms.php`

| `search` | `action` | Rol requerido | Operación |
|----------|----------|---------------|-----------|
| `users` | — | admin* | Listar usuarios |
| `users` | `usersToAreas` | admin | Usuarios por área |
| `users` | `updateUsersToArea` | admin | Asignar usuario a área |
| `areas` | — | admin, admin_servicio | Listar/editar/eliminar áreas |
| `event_types` | — | admin, admin_servicio | CRUD tipos de evento |
| `courses` | — | admin | CRUD ciclos escolares |
| `student` | `addStudent` | público | Registro alumno SS |
| `student` | `getStudent` | admin, teacher | Ver alumno |
| `student` | `acceptStudent` | admin | Aceptar alumno SS |
| `student` | `denegateStudent` | admin | Rechazar alumno SS |
| `student` | `dropStudent` | admin | Dar de baja alumno |
| `student` | `editStudent` | admin | Editar datos alumno |
| `degrees` | — | admin, público | CRUD licenciaturas |
| `event` | `applyEvent` | student | Postularse a evento |
| `event` | `approveEvent` | admin, teacher | Aprobar evento |
| `practices` | — | admin_practicas | Solicitudes de practicantes |
| `organismos_externos` | — | admin_practicas | CRUD organismos |
| `students_history` | — | admin | Historial con documentos |
| `reports` | — | admin_practicas | Reportes PP admin |
| `ijumich_requests` | `getPendingRequests` | admin | Solicitudes IJUMICH pendientes |
| `ijumich_requests` | `approveRequest` | admin | Aprobar documento IJUMICH |
| `ijumich_requests` | `rejectRequest` | admin | Rechazar documento IJUMICH |
| `AllDataEvents` | — | admin, admin_servicio | Datos consolidados eventos |
| `servicesTypeActives` | — | autenticado | Tipos de servicio activos |

### 5.2 Acciones sin `search` (POST `action` directo)

| `action` | Rol | Operación |
|----------|-----|-----------|
| `checkMatricula` | público | Buscar en SQL Server GES |
| `registerStudentPracticas` | público | Registro alumno PP |
| `getServicesActives` | público | Tipos de servicio activos |
| `selectServiceActive` | student | Seleccionar tipo servicio |
| `get_historial_ijumich` | student | Ver historial IJUMICH |
| `solicitar_carta_presentacion_ijumich` | student | Solicitar carta presentación |
| `cargar_carta_practicas_ijumich` | student | Subir carta prácticas |
| `cargar_carta_liberacion_ijumich` | student | Subir carta liberación |
| `get_historial_interno` | student | Ver historial interno |
| `cargar_carta_practicas_interno` | student | Subir carta prácticas interno |
| `cargar_reporte_parcial` | student | Subir reporte parcial (1-3) |
| `solicitar_carta_aceptacion_interno` | student | Solicitar carta aceptación |
| `cargar_solicitud_registro` | student | Subir solicitud registro |
| `cargar_carta_liberacion_interno` | student | Subir carta liberación |
| `cargar_evaluacion_unidad_productiva` | student | Subir evaluación unidad |
| `cargar_evaluacion_global` | student | Subir evaluación global |

### 5.3 Otros endpoints

| Endpoint | Autenticación | Uso |
|----------|--------------|-----|
| `ajax/ajax.login.php` | público | Login multi-tipo |
| `ajax/ajax.getUsers.php` | admin | Listar usuarios (protegido ✅) |
| `ajax/ajax.getUser.php` | verificar | Obtener usuario por ID |
| `ajax/ajax.updateUser.php` | verificar | Actualizar usuario |
| `ajax/ajax.deleteUser.php` | verificar | Eliminar usuario |
| `ajax/ajax.registroOrganismos.php` | público (rate limited) | Registro organismo externo |
| `ajax/generarCartaConclusionServicio.php` | student | PDF carta conclusión SS |
| `ajax/generarCartaAceptacionServicio.php` | student | PDF carta aceptación SS |
| `ajax/generarCartaPresentacion.php` | student | PDF carta presentación |
| `ajax/generarCartaIjumich.php` | student | PDF carta IJUMICH |
| `ajax/previewEvaluacionFirmada.php` | admin | Preview evaluación firmada |
| `ajax/previewEvaluacionGlobal.php` | admin | Preview evaluación global |
| `ajax/logout.php` | autenticado | Cerrar sesión |
| `organismo/forms.php` | organismo_externo | API completa del organismo |
| `practices/companies.php` | admin_practicas | API organismos admin |
| `practices/students.php` | admin_practicas | API estudiantes PP |
| `practices/students_flow.php` | admin_practicas | Flujo de estudiantes PP |
| `practices/areas.php` | admin, teacher | Áreas de prácticas |
| `practices/teacher.php` | teacher | API encargados |
| `practices/generarCartaPresentacion.php` | admin | PDF carta presentación PP |
| `practices/generarConstanciaAcreditacion.php` | admin | PDF constancia acreditación |
| `practices/export_companies_excel.php` | admin | Export Excel organismos |
| `upload-image.php` | admin (CSRF ✅) | Subir imagen de config |
| `process_email_queue.php` | token | Procesar cola de correos |

---

*Documento generado como base de conocimiento para desarrollo futuro — Universidad Montrer · Junio 2026*
