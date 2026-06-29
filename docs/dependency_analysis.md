# Reporte de Análisis de Dependencias (FASE 1)

> Entorno: desarrollo local — `http://syp.local`
> Fecha: 2026-06-29
> Estado: **ANÁLISIS — sin cambios aplicados. Pendiente de aprobación.**

## Resumen ejecutivo

Existen **tres** archivos `composer.json` (no dos) y **dos** `package.json`. La situación
real difiere de lo descrito en el objetivo en tres puntos importantes:

1. La carpeta `view/assets/vendor/` **no es solo de Composer**: también contiene
   librerías frontend (Datatables, plyr, chart.js, dropzone) que se sirven **por URL**
   desde las vistas. Eliminarla sin más **rompería** la interfaz.
2. El autoloader de `view/assets/vendor/autoload.php` **no se incluye en ningún punto
   del código de la aplicación**. Es decir, `minishlink/web-push` está instalado pero
   **no está conectado** (código muerto / pendiente de integrar).
3. **Ninguno de los dos `node_modules` está realmente en uso**:
   - `plyr` se sirve desde la copia estática `view/assets/vendor/plyr/`, no desde `node_modules`.
   - `fullcalendar` no se referencia en ninguna vista, JS ni plantilla.

---

# Dependencias PHP

## composer raíz — `/composer.json`  ✅ ACTIVO

Genera `/vendor`. Su autoload (`/vendor/autoload.php`) **sí** se incluye en todo el código
(`controller/`, `model/`).

| Paquete | Versión | Uso real |
|---|---|---|
| `setasign/fpdf` | ^1.8 | Generación de PDF (cartas, constancias) |
| `setasign/fpdi` | ^2.6 | Importar/superponer PDF |
| `phpmailer/phpmailer` | ^6.9 | Envío de correos (`controller/emails.php`, cola de correos) |
| `vlucas/phpdotenv` | ^5.6 | Carga de `.env` (`model/conection.php`) |
| `dompdf/dompdf` | ^3.1 | Render HTML→PDF |
| `phpoffice/phpspreadsheet` | ^5.7 | Exportación a Excel (`export_companies_excel.php`) |

Incluido por (root `/vendor/autoload.php`): `controller/*.php`, `controller/ajax/*.php`,
`controller/practices/*.php`, `controller/panelAdmin/forms.php`,
`controller/organismo/forms.php`, `model/conection.php`, `model/FormsModelPDF.php`.

## composer assets — `/view/assets/composer.json`  ⚠️ INSTALADO PERO NO CONECTADO

Genera `/view/assets/vendor` (parte Composer). Su autoload **no se incluye en ningún
archivo de la app**.

| Paquete | Versión | Estado |
|---|---|---|
| `minishlink/web-push` | ^8.0 | Instalado; **sin uso** (no hay `use Minishlink\WebPush`, ni `new WebPush`, ni inclusión del autoload) |

Dependencias transitivas instaladas por este: `guzzlehttp/*`, `web-token/*`,
`spomky-labs/*`, `paragonie/*`, `symfony/console`, `symfony/http-client`,
`symfony/string`, `brick/math`, `psr/*`, `ralouphie/getallheaders`.

## composer assets ANIDADO — `/view/assets/vendor/composer.json`  🗑️ OBSOLETO

| Paquete | Versión |
|---|---|
| `phpoffice/phpspreadsheet` | ^2.0 |
| `setasign/fpdf` | ^1.8 |
| `setasign/fpdi` | ^2.6 |

Este archivo está **dentro** de `vendor/` y **no corresponde** a lo realmente instalado
ahí (no hay `phpoffice` en la parte Composer de ese vendor). Es un residuo de una
estructura anterior. La copia manual asociada vive en `view/assets/vendor/PHP/`
(otro `autoload.php` + phpspreadsheet/fpdf/fpdi) y **tampoco se incluye** desde el código.

## ⚠️ Carpeta mixta: `view/assets/vendor/` contiene TAMBIÉN assets servidos por URL

Estas subcarpetas **no son** dependencias Composer/npm y **se sirven directamente al
navegador**. NO deben eliminarse al limpiar `vendor`:

| Carpeta | Referencia en código | Estado |
|---|---|---|
| `Datatables/` | `view/js.php:1`, `view/css.php:2` | ✅ EN USO (URL) |
| `plyr/` | `dashboardEstudianteInterno.php:14,1241`, `dashboardEstudianteExterno.php:7,1449` | ✅ EN USO (URL) |
| `chart.js/` | `view/js.php:2` carga chart.js desde **CDN jsdelivr**, no la copia local | 🟡 Copia local sin uso |
| `dropzone/` | Solo estilos `.dropzone` en `style.css`; JS no se carga desde esta ruta | 🟡 Probable carga por CDN |
| `PHP/` | `autoload.php` no incluido en ningún sitio | 🗑️ Duplicado obsoleto |

## Propuesta de fusión (PHP)

1. Añadir a `/composer.json` la única dependencia real de assets:
   `"minishlink/web-push": "^8.0"`.
2. Conjunto final en raíz: dompdf, fpdf, fpdi, phpmailer, phpdotenv, phpspreadsheet,
   web-push. Composer resolverá las transitivas compartidas (symfony/polyfill-*, psr/*,
   brick/math) a **una sola versión**.
3. Reinstalar `/vendor` con `composer install` para regenerar el autoloader unificado.
4. **Separar conceptos**: mover las librerías frontend estáticas
   (`Datatables/`, `plyr/`) fuera de cualquier carpeta llamada `vendor` — propuesta:
   `view/assets/libs/` — y actualizar las 6 referencias por URL.
5. Eliminar como obsoletos: `view/assets/composer.json`, `view/assets/composer.lock`,
   `view/assets/vendor/composer.json`, `view/assets/vendor/PHP/`, y la parte Composer
   de `view/assets/vendor/` **solo después** de migrar las carpetas estáticas.

---

# Dependencias Node

## package raíz — `/package.json`  ⚠️ NO CONECTADO

| Paquete | Versión | Estado |
|---|---|---|
| `plyr` | ^3.8.4 | Instalado en `/node_modules`, pero las vistas cargan plyr desde la copia estática `view/assets/vendor/plyr/`. El `node_modules/plyr` **no se referencia**. |

## package assets — `/view/assets/package.json`  ⚠️ NO CONECTADO

| Paquete | Versión | Estado |
|---|---|---|
| `fullcalendar` | ^6.1.11 | Instalado en `view/assets/node_modules` (`@fullcalendar/*`, `preact`), pero **no se referencia** en ninguna vista, JS ni plantilla. |

## Propuesta de fusión (Node)

- No hay dependencias Node *en uso* vía `node_modules`. Hay dos caminos:

  **Opción A (recomendada): formalizar.** `package.json` raíz declara
  `plyr` y `fullcalendar`, se ejecuta `npm install`, y se **migran las vistas** para
  servir plyr (y, si se desea, fullcalendar) desde `node_modules` con rutas estables.
  Ventaja: versiones gestionadas. Implica tocar las 4 referencias de plyr.

  **Opción B (mínimo riesgo): conservar copias estáticas.** Mantener plyr/Datatables
  como assets estáticos versionados en `view/assets/libs/`, eliminar ambos
  `node_modules`/`package.json` (no aportan nada en runtime). Cero cambios en las vistas
  salvo el reubicado de carpetas estáticas.

---

# Riesgos encontrados

| # | Riesgo | Severidad | Mitigación |
|---|---|---|---|
| R1 | Borrar `view/assets/vendor/` rompería Datatables y plyr (servidos por URL) | 🔴 Alta | Migrar `Datatables/` y `plyr/` a `view/assets/libs/` y actualizar 6 referencias antes de borrar |
| R2 | `minishlink/web-push` requiere PHP 8.1+ y arrastra symfony/http-client, guzzle, web-token | 🟡 Media | `composer install` resolverá versiones; verificar versión de PHP de XAMPP (phpspreadsheet ^5.7 ya exige 8.2+) |
| R3 | Conflicto teórico de transitivas compartidas (symfony/polyfill, psr/*) | 🟢 Baja | Composer unifica a una versión; revisar `composer.lock` resultante |
| R4 | `web-push` está sin conectar: tras la fusión seguirá sin usarse | 🟢 Baja | Decisión del usuario: migrarlo o descartarlo (¿se planea notificaciones push?) |
| R5 | Existe `view/assets/vendor/PHP/` (phpspreadsheet ^2.0 manual) duplicado de la raíz (^5.7) | 🟢 Baja | Es código muerto; eliminar tras confirmar que ningún autoload lo incluye |
| R6 | Reglas de reescritura en `web.config` redirigen rutas sin extensión a `index.php`. Las carpetas de assets deben seguir resolviéndose como archivos físicos | 🟡 Media | Verificar en `http://syp.local` que `view/assets/libs/...` devuelve 200 y no 404/redirect |

---

# Cambios necesarios en rutas e imports

## PHP (`require/include` de autoload)
- **Sin cambios** en las inclusiones existentes: todo el código ya usa `/vendor/autoload.php`
  (raíz). Al fusionar, el autoload raíz pasará a contener también `Minishlink\WebPush`,
  por lo que cualquier integración futura funcionará sin tocar rutas.
- Eliminar inclusiones a `view/assets/vendor/autoload.php` → **no existen** en el código
  de la app (solo auto-referencias internas de la librería). Nada que cambiar.

## Frontend (referencias por URL) — a actualizar si se reubican a `view/assets/libs/`

| Archivo | Línea | Ruta actual | Ruta propuesta |
|---|---|---|---|
| `view/js.php` | 1 | `view/assets/vendor/Datatables/datatables.js` | `view/assets/libs/Datatables/datatables.js` |
| `view/css.php` | 2 | `view\assets\vendor\Datatables\datatables.css` | `view/assets/libs/Datatables/datatables.css` |
| `view/pages/servicio/dashboardEstudianteInterno.php` | 14 | `view/assets/vendor/plyr/plyr.css` | `view/assets/libs/plyr/plyr.css` |
| `view/pages/servicio/dashboardEstudianteInterno.php` | 1241 | `view/assets/vendor/plyr/plyr.min.js` | `view/assets/libs/plyr/plyr.min.js` |
| `view/pages/servicio/dashboardEstudianteExterno.php` | 7 | `view/assets/vendor/plyr/plyr.css` | `view/assets/libs/plyr/plyr.css` |
| `view/pages/servicio/dashboardEstudianteExterno.php` | 1449 | `view/assets/vendor/plyr/plyr.min.js` | `view/assets/libs/plyr/plyr.min.js` |

> Nota: `view/css.php:2` usa **backslashes** (`\`) en la URL. Funciona en Windows pero es
> incorrecto para web; se corregirá a `/` en la migración.

## Node
- Si se elige Opción A, las rutas de plyr apuntarían a `node_modules/plyr/dist/...`.
- Si se elige Opción B, se eliminan `package.json`/`node_modules` (raíz y assets) y solo
  se conservan las copias estáticas reubicadas.

---

# Estructura final propuesta (resumen)

```
/composer.json        ← dompdf, fpdf, fpdi, phpmailer, phpdotenv, phpspreadsheet, web-push
/vendor/              ← único árbol Composer (autoload unificado)
/package.json         ← (Opción A) plyr, fullcalendar  |  (Opción B) eliminado
/node_modules/        ← (Opción A) gestionado  |  (Opción B) eliminado
/view/assets/
    libs/             ← NUEVO: Datatables/, plyr/ (assets estáticos servidos por URL)
    css/ js/ scss/ fonts/ images/ documents/ templates/   ← sin cambios
    (eliminados)      ← composer.json, composer.lock, package.json, package-lock.json,
                         node_modules/, vendor/
```

---

# Decisiones que necesito de ti antes de la FASE 2

1. **Estrategia Node**: ¿Opción A (formalizar plyr/fullcalendar en npm y migrar vistas)
   o **Opción B (recomendada)**: conservar copias estáticas y eliminar los `node_modules`?
2. **`minishlink/web-push`**: ¿lo conservamos en la fusión (para notificaciones push
   futuras) o lo descartamos por estar sin usar?
3. **Reubicación de assets estáticos**: ¿de acuerdo con mover `Datatables/` y `plyr/` a
   `view/assets/libs/`? (Alternativa: dejarlos donde están y NO borrar `view/assets/vendor`).
4. **Versión de PHP** del XAMPP local (para confirmar compatibilidad de web-push + phpspreadsheet 5.x).
