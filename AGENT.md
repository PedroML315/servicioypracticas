# Memoria del Proyecto (AGENT.md)

## Decisiones arquitectónicas importantes
- **Zona horaria**: `America/Mexico_City` (UTC-6) a usar en cada script PHP y sesión MySQL.
- **Patrón de modelo**: Métodos estáticos, cache PDO `self::db()`, `ERRMODE_EXCEPTION`.
- **Correos**: Usar `sendTemplateByKey()` + tabla `email_templates` + cola `email_queue`.
- **Logs**: Registrar acciones vía `LogModel::log()`.
- **Notificaciones**: Insertar en tabla `notifications`.
- **CRON jobs**: Scripts CLI en `controller/cron/`, protegidos con `PHP_SAPI === 'cli'`.

## Errores encontrados
- Error en Fase 1: El script de actualización asumió que existía la columna `template_key` en la tabla `email_templates`, pero la estructura de la base de datos es diferente (por verificar el nombre exacto de la tabla y columna).

## Soluciones aplicadas
- Inicialización de la memoria del proyecto (AGENT.md).
- **PDFs Dompdf – paginación**: `counter(pages)` devuelve `0` en la versión de Dompdf instalada (solo `counter(page)` funciona). Para "Página X de Y" se usa renderizado en DOS pases: primero `getCanvas()->get_page_count()` para el total, luego se re-renderiza inyectando el total como literal en el CSS. Ver `controller/convenio_render.php::convenioRenderPdf()`.
- **Convenio de Prácticas**: editable en Configuraciones → pestaña "Convenios" (`view/pages/configs/convenio-editor.php` + `convenio_editor.js`). Config en `config/convenio_config.json` (sembrada con `database/seed_convenio_config.php` desde `Convenio.md`). Al guardar (`controller/convenio-config.php` POST) se genera automáticamente el PDF en `storage/generated/convenio_practicas_profesionales.pdf`. El organismo lo descarga desde `controller/ajax/generarConvenio.php` (público) en `RegisterEmpresas.php`, lo firma de forma autógrafa, lo escanea y lo sube como `docs[convenio_firmado]`. Membrete (header/footer) en cada hoja y 4 firmas: 2 representantes + 2 testigos. El nombre del representante se inyecta con el token `{{repUniversidad}}`.
- **Convenio validado por la institución**: al aceptar a un organismo receptor, el administrador debe subir (modal SweetAlert, solo PDF) el convenio firmado por la institución. Se guarda en `uploads/{id}/convenio_validado_<hex>.pdf` y se registra en `organismos_externos.convenio_validado` + `convenio_validado_at` (migración `database/update_convenio_validado.php`). Acción `upload_convenio` en `controller/practices/companies.php` (valida MIME real `application/pdf`). Admin: botón "Ver/Cargar convenio" por tarjeta + apartado en modal de Datos + alerta global de empresas aceptadas sin convenio (`companies_admin.js`). Organismo: botón "Ver convenio" o leyenda "Convenio no cargado por Universidad Montrer" en el hero (`dashboard.js`, `orgInfo` ahora incluye `id` y `convenio_validado`). Se sirve con `controller/serve_pdf.php` (se agregó el rol `organismo_externo` al check de propietario). Advertencia también en el dashboard del administrador (`dashboardAdministrador.php`, pestaña Prácticas → `#convenioFaltanteAlert`) que consume la acción `get_convenios_faltantes` (`PracticasModel::mdlGetOrganismosSinConvenio`).
- **Nuevo flujo de Convenios Institucionales** (reemplaza la carga del convenio en el registro): el organismo ya NO sube convenio al registrarse (`RegisterEmpresas.php` ahora tiene 3 pasos). Estados en `organismos_externos.convenio_estado` ENUM (`ninguno→generado→firmado_pendiente→(firmado_rechazado)→validado`); `isAcepted=1` solo al validar (activa cuenta + credenciales). Migración idempotente `database/update_convenio_flow.php` (columnas del convenio, tabla `tokens_convenio_organismos`, `email_queue.attachments`, 4 plantillas `pp_*`). Al aprobar el registro, el admin genera el convenio con `convenioRenderPdfForOrganismo()` (tokens por-organismo `{{nombre_empresa}}`, `{{representante_legal}}`, `{{direccion_empresa}}`, `{{telefono}}`, `{{correo}}`, etc. en `controller/convenio_render.php`), se guarda en `uploads/{id}/convenio_generado_*.pdf` y se envía por correo (PDF **adjunto**) con enlace único `firmar-convenio/{token}` (OTP, un solo uso, espeja `corregir-organismo`). Vista pública `view/pages/practicas/firmar_convenio.php` + controlador `controller/practices/firma-convenio.php`. Validación final en `companies_admin.js` (botones por `convenio_estado`): aprobar (`ctrAprobarConvenioFinal`) o rechazar con motivo (`ctrRechazarConvenioFirmado`, nuevo token `recarga`). La cola de correos (`MailService::sendMail`/`sendTemplateByKey`/`process_email_queue.php`) ahora soporta adjuntos vía la columna `email_queue.attachments` (JSON).
- Se corrigió el cálculo de días hábiles en `BusinessHoursHelper::calcularVencimientoCarta()`. El usuario solicitó que "2 días hábiles" incluya el día de generación de la carta (ej. si se genera el lunes 15, debe expirar al finalizar el martes 16 a las 23:59:59).

## Restricciones del proyecto
- Nunca modificar la base de datos manualmente. Se deben crear scripts ejecutables, idempotentes y seguros en `/database`.
- Antes de marcar una fase completada, ejecutar pruebas automáticas y funcionales.

## Convenciones detectadas
- Framework custom en PHP (Modelo-Vista-Controlador de forma estructural).
- JQuery y SweetAlert2 en Frontend. AJAX para backend requests.

## Cambios que deben evitarse en el futuro
- Cambiar esquema de base de datos sin un script en `/database`.
- Modificar variables de sesión o accesos sin validar los roles correspondientes.
