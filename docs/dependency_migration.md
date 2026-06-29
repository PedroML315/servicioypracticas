# Migración y Unificación de Dependencias (FASE 2 — completada)

> Entorno: desarrollo local — `http://syp.local`
> Fecha: 2026-06-29
> Estado: ✅ **Completada y verificada**
> Análisis previo: ver [`dependency_analysis.md`](dependency_analysis.md)

## Decisiones aplicadas

| Tema | Decisión |
|---|---|
| Dependencias Node | **Opción B**: conservar librerías como assets estáticos; eliminar todos los `node_modules` y `package.json` (ninguna se usaba vía npm en runtime). |
| `minishlink/web-push` | **Descartado** (estaba instalado pero sin conectar al código). |
| Assets estáticos | **Reubicados** `Datatables/` y `plyr/` a `view/assets/libs/`. |
| `composer.json` raíz | **Sin cambios**: ya contenía todas las dependencias PHP realmente usadas. |

---

## Dependencias migradas

### PHP — única ubicación: `/composer.json` → `/vendor`

Sin cambios respecto al estado previo. Conjunto final (todas en uso):

| Paquete | Versión | Uso |
|---|---|---|
| `dompdf/dompdf` | ^3.1 | HTML → PDF |
| `setasign/fpdf` | ^1.8 | Generación de PDF |
| `setasign/fpdi` | ^2.6 | Importación/superposición de PDF |
| `phpmailer/phpmailer` | ^6.9 | Envío de correos |
| `vlucas/phpdotenv` | ^5.6 | Variables de entorno (`.env`) |
| `phpoffice/phpspreadsheet` | ^5.7 | Exportación a Excel |

Autoload único: `/vendor/autoload.php` (ya referenciado en todo el código).
Verificado: cargan `Dompdf`, `PHPMailer`, `PhpSpreadsheet`, `Fpdi`. PHP local 8.2.12 (compatible).

### Node — eliminado de runtime

No quedan `package.json` ni `node_modules` en el proyecto. Las librerías frontend que el
sitio realmente usa se sirven como **assets estáticos versionados**:

| Librería | Servida desde | Cargada en |
|---|---|---|
| DataTables (+ Bootstrap 5, FixedHeader, FixedColumns) | `view/assets/libs/Datatables/` | `view/js.php`, `view/css.php` |
| Plyr (reproductor de video) | `view/assets/libs/plyr/` | dashboards interno/externo |
| Chart.js 4.4 | CDN jsdelivr | `view/js.php` |
| SweetAlert2 11 | CDN jsdelivr | `view/css.php` |

> `plyr` (npm) y `fullcalendar` (npm) se eliminaron por **no estar referenciados** en
> ninguna vista, JS ni plantilla.

---

## Conflictos resueltos

| Conflicto previo | Resolución |
|---|---|
| **3 archivos `composer.json`** (raíz, `view/assets/`, `view/assets/vendor/`) | Queda **uno solo** en la raíz. Los otros dos eliminados (uno sin conectar, otro obsoleto). |
| **2 árboles `vendor/`** | Queda **uno** en la raíz. El de `view/assets/vendor/` eliminado. |
| **2 árboles `node_modules/`** + 2 `package.json` | Eliminados todos (sin uso real). |
| `phpoffice/phpspreadsheet` ^2.0 (copia manual en `view/assets/vendor/PHP/`) vs ^5.7 (raíz) | Eliminada la copia ^2.0 obsoleta; se conserva la ^5.7 de la raíz. |
| Carpeta `vendor` **mixta** (Composer + assets servidos por URL) | Separados conceptos: assets estáticos → `view/assets/libs/`; parte Composer eliminada. |
| `view/css.php` usaba URL con backslashes (`\`) | Corregido a `/`. |

### Archivos/carpetas eliminados

```
/package.json                     /node_modules/
/package-lock.json
view/assets/composer.json         view/assets/vendor/   (incl. PHP/, chart.js/, dropzone/,
view/assets/composer.lock                                 minishlink/, web-token/, guzzlehttp/, etc.)
view/assets/package.json          view/assets/node_modules/   (@fullcalendar, preact)
view/assets/package-lock.json
```

> Respaldo de manifiestos (json/lock) guardado en el scratchpad de la sesión por seguridad.

### Referencias de rutas actualizadas (6)

| Archivo | Antes | Después |
|---|---|---|
| `view/js.php:1` | `view/assets/vendor/Datatables/datatables.js` | `view/assets/libs/Datatables/datatables.js` |
| `view/css.php:2` | `view\assets\vendor\Datatables\datatables.css` | `view/assets/libs/Datatables/datatables.css` |
| `dashboardEstudianteInterno.php:14` | `…/vendor/plyr/plyr.css` | `…/libs/plyr/plyr.css` |
| `dashboardEstudianteInterno.php:1241` | `…/vendor/plyr/plyr.min.js` | `…/libs/plyr/plyr.min.js` |
| `dashboardEstudianteExterno.php:7` | `…/vendor/plyr/plyr.css` | `…/libs/plyr/plyr.css` |
| `dashboardEstudianteExterno.php:1449` | `…/vendor/plyr/plyr.min.js` | `…/libs/plyr/plyr.min.js` |

---

## Estructura final del proyecto (dependencias)

```
servicioypracticas.unimontrer.edu.mx/
├── composer.json            ← ÚNICO manifiesto PHP (dompdf, fpdf, fpdi, phpmailer, phpdotenv, phpspreadsheet)
├── composer.lock
├── vendor/                  ← ÚNICO árbol Composer (autoload unificado)
│   └── autoload.php         ← incluido por controller/*, model/*
├── (sin package.json ni node_modules)
└── view/assets/
    ├── libs/                ← NUEVO: librerías frontend estáticas servidas por URL
    │   ├── Datatables/
    │   └── plyr/
    ├── css/  js/  scss/  fonts/  images/  documents/  templates/
    └── (sin composer.json, vendor/, package.json, node_modules/)
```

---

## Verificación realizada (en `http://syp.local`)

| Prueba | Resultado |
|---|---|
| `GET /view/assets/libs/Datatables/datatables.js` | ✅ 200 `text/javascript` |
| `GET /view/assets/libs/Datatables/datatables.css` | ✅ 200 `text/css` |
| `GET /view/assets/libs/plyr/plyr.min.js` | ✅ 200 `text/javascript` |
| `GET /view/assets/libs/plyr/plyr.css` | ✅ 200 `text/css` |
| `GET /view/assets/vendor/Datatables/datatables.js` (ruta vieja) | ✅ 404 (esperado, ya no existe) |
| `GET /` | ✅ 200 |
| `GET /login` | ✅ 200 |
| `vendor/autoload.php` + clases Dompdf/PHPMailer/PhpSpreadsheet/Fpdi | ✅ Cargan |
| Búsqueda de referencias residuales a `assets/vendor` en código | ✅ 0 (solo en docs) |

---

## Recomendaciones para futuras dependencias

1. **PHP** → siempre vía Composer en la **raíz**:
   `composer require vendor/paquete`. No crear `composer.json` en subcarpetas.
2. **Librerías frontend de terceros**:
   - Preferir **CDN** (como ya se hace con Chart.js y SweetAlert2), o
   - Si se requiere copia local, colocarla en **`view/assets/libs/`** (nunca en una carpeta
     llamada `vendor`, para no mezclar con Composer).
3. **No introducir `node_modules`** salvo que se adopte un *bundler* (Vite/webpack) y un
   flujo de build real. Hoy el proyecto sirve JS/CSS directamente, sin build, por lo que
   npm añadía complejidad sin beneficio.
4. **Rutas de assets**: usar siempre `/` (forward slash), nunca `\`, por compatibilidad web.
5. **`web-push`**: si en el futuro se implementan notificaciones push reales, reinstalar con
   `composer require minishlink/web-push` en la raíz **y** conectar su autoload
   (ya cubierto por `/vendor/autoload.php`) en el controlador correspondiente.
6. Tras cambios en dependencias, validar siempre en `http://syp.local`:
   páginas clave (`/`, `/login`, dashboards), assets (200, sin 404) y autoloaders.
