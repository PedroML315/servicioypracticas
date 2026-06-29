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
