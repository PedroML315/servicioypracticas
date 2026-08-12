# CKEditor 5 (auto-hospedado)

Editor de texto enriquecido usado por el **Gestor de envío masivo de correos**
(`view/pages/configs/mail_bulk.php`).

## Origen

| Archivo | Descargado de |
|---|---|
| `ckeditor.js` | `https://cdn.ckeditor.com/ckeditor5/39.0.0/super-build/ckeditor.js` |
| `translations/es.js` | `https://cdn.ckeditor.com/ckeditor5/39.0.0/super-build/translations/es.js` |

Versión: **39.0.0**, variante **super-build**.

## Por qué está aquí y no se carga del CDN

1. **La CSP del sitio lo bloqueaba.** `config/Security.php::sendSecurityHeaders()`
   solo permite scripts de `self` y de una lista corta de CDNs; `cdn.ckeditor.com`
   no está en ella. Servirlo desde el propio dominio evita tener que abrir la
   política de seguridad de todo el sistema para un solo módulo.
2. **La prevención de rastreo del navegador** (Edge/Firefox) marcaba el CDN como
   tercero y mostraba avisos en consola.
3. Deja de depender de que un servicio externo esté disponible.

## Por qué la variante "super-build" y no "classic"

El build `classic` **no incluye** los plugins `Underline`, `Strikethrough`,
`Alignment`, `HorizontalLine` ni `RemoveFormat`; al pedirlos en la barra de
herramientas lanza avisos `toolbarview-item-unavailable` y los botones no
aparecen. El `super-build` sí los trae precompilados.

⚠️ **Ojo con el nombre global:** el `super-build` expone
`window.CKEDITOR.ClassicEditor`, **no** `window.ClassicEditor` (que es lo que
expone el build `classic`). Si se cambia de variante hay que ajustar
`getEditorClass()` en `view/assets/js/configs/mail-bulk.js`.

El `super-build` también incluye funciones **de pago** (colaboración en tiempo
real, exportar a PDF/Word, revisión ortográfica, MathType…). Se desactivan con
`removePlugins` en `mail-bulk.js`; sin eso ensucian la consola con avisos de
licencia.

## Modificaciones aplicadas al archivo descargado

Si algún día se actualiza la versión, hay que **volver a aplicar estos dos
parches** sobre el archivo recién descargado:

1. **Se eliminó el comentario final `//# sourceMappingURL=ckeditor.js.map`.**
   El `.map` no se descargó, así que el navegador pedía un archivo inexistente
   (404 en consola al abrir las herramientas de desarrollo).

2. **Se neutralizó la función `inquire` de protobufjs.** Venía así:

   ```js
   function inquire(moduleName){try{var mod=eval("quire".replace(/^/,"re"))(moduleName);if(mod&&(mod.length||Object.keys(mod).length))return mod}catch(t){}return null}
   ```

   Usa un `eval` ofuscado para esconder un `require()` de los empaquetadores. En
   el navegador **siempre falla** (no existe `require`) y termina devolviendo
   `null`, pero el `eval` disparaba un bloqueo de CSP visible en consola
   (`script-src -> eval`). Se sustituyó por su resultado real:

   ```js
   function inquire(moduleName){return null}
   ```

   Comportamiento idéntico en el navegador, sin violar la CSP. **No** se añadió
   `'unsafe-eval'` a la política de seguridad a propósito: habría debilitado la
   protección de todo el sitio para silenciar un aviso.

Quedan dos `eval(` en el archivo, dentro de `js.Lib.eval=function(code){return eval(code)}`
(runtime de Haxe que trae MathType). Son solo **definiciones** que nunca se
ejecutan porque MathType está desactivado en `removePlugins`; verificado que no
producen ningún aviso en consola.
