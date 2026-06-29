# Contexto Universal — Servicio Social y Prácticas Profesionales UNIMO

> Prompt de contexto para LLMs. Documento técnico conciso; no sustituye el código fuente en cambios de alto riesgo.

---

## 1. CONFIGURACIÓN E IDENTIDAD PRINCIPAL

### Nombre del proyecto
**Servicio Social y Prácticas Profesionales UNIMO** (`servicioypracticas.unimontrer.edu.mx`)

### Stack tecnológico

| Capa | Tecnología | Versión / nota |
|------|------------|----------------|
| Backend | PHP | **≥ 8.2** (`vendor/composer/platform_check.php`) |
| Servidor web | IIS + URL Rewrite | `web.config` → `index.php?pagina={R:1}` |
| BD principal | MySQL | **5.7.x** (dump `database/DB.sql`); PDO |
| BD externas | Microsoft SQL Server | SIL + GES vía `sqlsrv` PDO (`model/conection.php`) |
| Dependencias PHP | Composer | `setasign/fpdf` ^1.8, `setasign/fpdi` ^2.6, `phpmailer/phpmailer` ^6.9, `vlucas/phpdotenv` ^5.6, `dompdf/dompdf` ^3.1, `phpoffice/phpspreadsheet` ^5.7 |
| Frontend | HTML/CSS/JS vanilla | jQuery 3.6, DataTables, Chart.js 4.4, SweetAlert2 11, Bootstrap grid local |
| Node (opcional) | npm | `plyr` ^3.8.4 (`package.json`) |
| Sesión / seguridad | PHP sessions + `config/Security.php` | CSRF, timeout 30 min, headers CSP |

**Pendiente de definir:** versión exacta de PHP en producción (dump generado con PHP 8.4.19; Composer exige ≥ 8.2).

### Propósito principal
Plataforma web para gestionar el ciclo completo de **Servicio Social** (interno/externo, flujo IJUMICH) y **Prácticas Profesionales** (organismos, solicitudes, evaluaciones, documentos PDF) de la Universidad Montrer (UNIMO).

---

## 2. ARQUITECTURA Y FLUJO DE DATOS

### Patrón de diseño
**Monolito PHP** con organización **MVC informal** (no framework):
- **Vistas:** `view/pages/` (PHP embebido + HTML)
- **Controladores:** `controller/` (lógica HTTP, PDF, correo)
- **Modelos:** `model/` (`FormsModel`, `PracticasModel`, `ServicioModel`, etc.)
- **Router / ACL:** `config/whiteList.php` (no hay front controller con rutas nombradas)
- **Dispatcher AJAX central:** `controller/ajax/ajax.forms.php` (`$_POST['search']` + `action`)

### Árbol de directorios (claves)

```
/
├── index.php                 # Entrada única → ControllerTemplate → dashboard shell
├── web.config                # IIS rewrite: /{pagina} → index.php?pagina=
├── .env                      # Secretos (gitignored); vlucas/phpdotenv
├── config/                   # Router, menú, seguridad, JSON de cartas/PDF
├── controller/               # Lógica HTTP, AJAX, PDF, cola de correos
│   ├── forms.controller.php  # FormsController, PracticasController, GESController, ServicioController
│   ├── ajax/                 # Endpoints JSON (ajax.forms.php es el más grande)
│   ├── practices/            # Empresas, estudiantes PP, export Excel
│   ├── organismo/            # Formularios organismo externo
│   └── emails.php            # PHPMailer + plantillas
├── model/                    # PDO / negocio
│   ├── conection.php         # MySQL + SQL Server (SIL/GES)
│   ├── generalModels.php     # class FormsModel (~1400 líneas)
│   ├── PracticasModel.php    # Prácticas profesionales
│   └── ServicioModel.php     # Servicio social, folios, IJUMICH
├── view/
│   ├── dashboard.php         # Shell HTML (incluye whiteList)
│   ├── pages/                # Vistas por pantalla (servicio/, practicas/, configs/)
│   └── assets/js/            # Cliente: ajax/, student/, organismo/
├── database/
│   ├── DB.sql                # Esquema MySQL `servicio_social`
│   └── migrations/           # SQL incrementales (email_queue, evaluaciones, etc.)
├── storage/generated/        # Coordenadas JSON para estampado PDF evaluaciones
├── uploads/                  # Documentos subidos por usuarios
├── vendor/                   # Composer autoload
└── agent.md                  # Auditoría técnica interna (mayo 2026) — referencia extendida
```

### Flujo de una solicitud típica (página autenticada)

```
Browser GET /internship_companies
  → web.config rewrite
  → index.php
  → ControllerTemplate::ctrBringTemplate()
  → view/dashboard.php
  → config/whiteList.php
       · Security::init() + session
       · Mapeo rol (admin → admin_servicio | admin_practicas por type_admin)
       · Whitelist de ?pagina= por rol
  → view/pages/navs/header.php + sidebar + internship_companies.php
  → JS (view/assets/js/...) POST a controller/ajax/ajax.forms.php
       · search=practices | student | organismos_externos | ...
       · PracticasController / FormsModel / PracticasModel
  → Conexion::conectar() → MySQL (servicio_social)
  → JSON response → actualización UI (DataTables / SweetAlert)
```

### Flujo AJAX login (ejemplo)

```
POST controller/ajax/ajax.login.php { user_type, email, password }
  → user_type: administrativo | alumno_servicio | alumno_practicas | organismo_externo
  → FormsController / PracticasController
  → $_SESSION['logged'], $_SESSION['user']
```

### Flujo documento PDF (ejemplo carta)

```
POST controller/ajax/generarCartaConclusionServicio.php
  → Validación sesión (role=student), CSRF, prerequisitos BD
  → ServicioModel + config/carta_conclusion_config.json
  → dompdf / FPDI (firma/sello desde JSON + imágenes en view/assets/images/)
  → stream PDF (sin persistir archivo en disco en algunos flujos)
```

### Bases de datos

| Conexión | Método | Uso |
|----------|--------|-----|
| MySQL `servicio_social` | `Conexion::conectar()` | Sistema principal (estudiantes, eventos, prácticas, correos, logs) |
| SQL Server SIL | `Conexion::conectarSIL()` | Integración académica SIL |
| SQL Server GES | `Conexion::conectarGES()` | Consulta alumnos por matrícula (`GESModel`) |

---

## 3. PUNTOS DE ENTRADA Y ARCHIVOS CRÍTICOS

### Entrada HTTP
| Archivo | Función |
|---------|---------|
| `index.php` | Bootstrap mínimo; carga template |
| `web.config` | Regla IIS: URLs amigables → `?pagina=` |
| `view/dashboard.php` | Layout base; incluye `config/whiteList.php` |

### Configuración
| Archivo | Función |
|---------|---------|
| `.env` | Variables de entorno (no versionado). Ver lista abajo |
| `config/whiteList.php` | **Router + control de acceso por rol** |
| `config/selectDashboard.php` | Dashboard por rol en `/inicio` |
| `config/Security.php` | Sesión, CSRF, headers, `requireRole()` |
| `config/menu.php` | Menú lateral por rol |
| `config/general_settings.json` | Emails institucionales (SS/PP) |
| `config/*_config.json` | Plantillas editables: cartas, constancias, presentación |
| `config/carta_conclusion_config.json` | Carta conclusión SS interno |

### Variables `.env` (nombres; valores locales en servidor)

```
DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
SIL_DB_HOST, SIL_DB_DATABASE, SIL_DB_DATABASE_GES, SIL_DB_USERNAME, SIL_DB_PASSWORD
SMTP_HOST, SMTP_USER, SMTP_PASS, SMTP_PORT, FROM_EMAIL, FROM_NAME
Current_ID_ADMIN, Current_Email
EMAIL_SS, EMAIL_PP
EMAIL_QUEUE_SECRET
```

### Rutas públicas (sin login)
Definidas en `config/whiteList.php` → `$navWithoutLogin`:
- `login` → `view/pages/login.php`
- `inscripcionServicio` → `RegisterStudent.php`
- `inscripcionPracticas` → `practicas/RegisterPracticas.php`
- `inscripcionEmpresas` → `practicas/RegisterEmpresas.php`

### Rutas autenticadas (parámetro `?pagina=` o URL `/pagina`)
Ejemplos: `inicio`, `students`, `internship_companies`, `practice_solicitud`, `configs`, `area_practicas`, `students_in_practices`, etc. Lista completa en `$navWithLogin` dentro de `whiteList.php`.

### Endpoints AJAX / API críticos
| Endpoint | Uso |
|----------|-----|
| `controller/ajax/ajax.forms.php` | **Dispatcher principal** (CRUD masivo vía `search`/`action`) |
| `controller/ajax/ajax.login.php` | Login multi-tipo usuario |
| `controller/ajax/ajax.getUsers.php` | Usuarios (revisar auth — ver `agent.md`) |
| `controller/ajax/generarCartaConclusionServicio.php` | PDF carta conclusión SS |
| `controller/ajax/generarCartaAceptacionServicio.php` | PDF carta aceptación |
| `controller/ajax/generarCartaPresentacion.php` | PDF carta presentación |
| `controller/practices/companies.php` | API organismos (admin PP) |
| `controller/process_email_queue.php` | Cola correos (token `EMAIL_QUEUE_SECRET`) |
| `controller/emails.php` | Funciones PHPMailer (include, no endpoint directo típico) |

### Cron / tareas programadas (pendiente documentar en ops)
- `controller/process_email_queue.php?token=EMAIL_QUEUE_SECRET` — procesar `email_queue`

---

## 4. COMPONENTES Y LÓGICA DE NEGOCIO PRINCIPAL

### Roles de usuario

| Rol sesión | Origen | Dashboard / alcance |
|------------|--------|---------------------|
| `admin` | `users` | Acceso total; menú completo |
| `admin_servicio` | `admin` + `admin_type.type_admin=1` | Eventos, estudiantes internos/externos, áreas |
| `admin_practicas` | `admin` + `type_admin=2` | Organismos, solicitudes, estudiantes PP, reportes |
| `teacher` | `users.role=teacher` | Estudiantes, áreas de prácticas asignadas |
| `student` | `student` (servicio social) | `dashboardEstudianteInterno` o `Externo` según `type` |
| `alumno_practicas` | `students_practicas` | `dashboardStudent.php` (flujo PP) |
| `organismo_externo` | `organismos_externos` | Practicantes, asistencias, evaluaciones |

Alumnos `student` / `alumno_practicas` en `whiteList` se mapean a rol `other` para páginas admin; su UI vive en `inicio` vía `selectDashboard.php`.

### Módulos backend

| Componente | Responsabilidad |
|------------|-----------------|
| **FormsModel** (`generalModels.php`) | Usuarios, eventos, cursos, áreas, tipos de evento, estudiantes SS, grados, inscripciones |
| **ServicioModel** | Folios cartas SS, flujo IJUMICH interno, reportes parciales, historial alumno |
| **PracticasModel** | Organismos externos, solicitudes practicantes, postulaciones áreas, asistencias, evaluaciones rubros/actitudes, reportes PP |
| **GESModel** | Búsqueda alumno por matrícula en SQL Server GES |
| **FormsModelPDF** | Generación/armado PDFs compartidos |
| **EmailsModel** | Plantillas `email_templates`, cola `email_queue` |
| **Notifications** (`notifications.php`) | Notificaciones in-app |
| **LogModel** | Auditoría `logs` |
| **FormsController** | Login admin/alumno SS, registro usuarios, orquestación SS |
| **PracticasController** | Login PP/organismo, aceptación organismos, solicitudes |
| **controller/emails.php** | Envío SMTP, render HTML plantillas, helpers `EMAIL_SS` / `EMAIL_PP` |

### Módulos frontend (por dominio)

| Ruta JS | Dominio |
|---------|---------|
| `view/assets/js/ajax/inicio.js` | Stepper SS interno (IJUMICH), modales documentos |
| `view/assets/js/student/practicesApp.js` | Alumno prácticas |
| `view/assets/js/organismo/*.js` | Dashboard organismo receptor |
| `view/assets/js/ajax/practices/companies_admin.js` | Admin empresas/organismos |

### Dominios de negocio

**Servicio Social**
- Eventos con puntos (`events`, `event_types`, `students_events`)
- Estudiantes internos (`student.type=universidad`) vs externos
- Flujo IJUMICH 8 pasos: documentos, cartas auto-generadas, reportes parciales, carta conclusión, liberación
- Tablas clave: `servicio_social_interno`, `servicio_social_ijumich`, `cartas_*`, `registrations`

**Prácticas Profesionales**
- Registro organismos (`organismos_externos`, `unidades_receptoras`)
- Solicitudes de practicantes (`solicitudes_practicantes`)
- Áreas internas UNIMO (`areas_practicas`, postulaciones, asistencias)
- Prácticas en empresa (`students_in_practices`, asistencias, evaluaciones, reportes parcial/final)
- Constancias y cartas PP (`constancias_acreditacion`, `cartas_practicas_profesionales`)

### Interacción entre capas
1. Vista incluye JS que llama AJAX con `search` + `action`.
2. `ajax.forms.php` valida sesión (parcial; acciones públicas en registro).
3. Controlador estático invoca modelo (`mdl*` / métodos `PracticasModel`).
4. Modelo usa `Conexion` o PDO propio (`PracticasModel::db()`).
5. Side effects: `emails.php`, `Notifications::addNotification`, encolado en `email_queue`.

### Documentación complementaria
- `agent.md` — auditoría OWASP, flujos IJUMICH, checklist seguridad, plan de mejoras.

---

## 5. CÓMO CONSUMIR ESTE CONTEXTO

### Despliegue mínimo
1. PHP ≥ 8.2 + extensiones: `pdo_mysql`, `pdo_sqlsrv`, `mbstring`, `gd` (PDF/imágenes).
2. `composer install` en raíz.
3. Copiar `.env` desde plantilla local (no existe `.env.example` en repo — **pendiente de definir**).
4. Importar `database/DB.sql` + migraciones en `database/migrations/` por orden de fecha.
5. IIS: sitio apuntando a raíz; `web.config` activo; permisos escritura en `uploads/`.

### Convenciones al modificar código
- Respetar patrón `mdl*` en modelos y nombres de controladores existentes.
- Nuevas pantallas admin: registrar slug en `config/whiteList.php` y `config/menu.php`.
- Nuevas acciones AJAX: extender `ajax.forms.php` o crear endpoint dedicado con `Security::init()` + verificación de rol.
- PDFs editables: JSON en `config/` + editor en `view/pages/configs/`.
- No commitear `.env`, `vendor/`, `uploads/` con datos reales.

---

## 🤖 INSTRUCCIONES DE CONTEXTO PARA IA

Copia y pega el bloque siguiente al iniciar una sesión con otra IA:

```
Estás trabajando en el monolito PHP "Servicio Social y Prácticas Profesionales UNIMO"
(servicioypracticas.unimontrer.edu.mx). Lee README.md del repositorio como mapa autoritativo.

Reglas:
1. Arquitectura: MVC informal. Entrada: index.php → view/dashboard.php → config/whiteList.php (router+ACL).
   AJAX principal: controller/ajax/ajax.forms.php (POST search/action). Modelos en model/; BD MySQL servicio_social + SQL Server SIL/GES.
2. Antes de cambiar una pantalla, localiza su slug en whiteList.php y la vista en view/pages/ o view/pages/practicas/.
3. Antes de cambiar datos, identifica el modelo (FormsModel, PracticasModel, ServicioModel) y la tabla en database/DB.sql.
4. Roles: admin, admin_servicio (type_admin=1), admin_practicas (=2), teacher, student, alumno_practicas, organismo_externo.
   Dashboard alumno en config/selectDashboard.php.
5. Secretos solo en .env (vlucas/phpdotenv). No hardcodear credenciales. Usar config/Security.php para sesión/CSRF en endpoints nuevos.
6. PDF/correos: revisar config/*.json, controller/emails.php y agent.md para flujos IJUMICH y seguridad.
7. Cambios mínimos y alineados al estilo existente (PHP procedural/OOP mixto, jQuery, sin framework frontend).

Si README y código discrepan, prioriza el código y reporta la discrepancia.
```

---

*Generado como contexto para LLMs — Universidad Montrer · Junio 2026*
