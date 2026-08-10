# Migraciones de base de datos

Scripts PHP **idempotentes** (re-ejecutables sin riesgo: verifican con `SHOW COLUMNS`/`CREATE TABLE IF NOT EXISTS`/inserción condicional antes de aplicar cambios). Son el historial de esquema del proyecto — `database/DB-nodata.sql` **no** se mantiene al día, así que para reconstruir una base desde cero hay que importar el dump y luego correr estas migraciones en orden.

## Ejecución

```
C:\xampp\php\php.exe database\migrations\<archivo>.php
```

## Orden cronológico

| # | Archivo | Qué hace |
|---|---|---|
| 1 | `update_fase1.php` | Fase 1 del flujo de prácticas |
| 2 | `update_fase2.php` | `students_in_practices`: decision_motivo/fecha, isRejected + plantillas de correo con motivo |
| 3 | `update_fase3.php` | Strikes / bloqueos de organismos |
| 4 | `fase4_migration.php` | Fase 4 |
| 5 | `update_fase5.php` | Tabla `entrevistas_practicas` (evaluación de entrevista) |
| 6 | `update_evaluacion_integral.php` | Evaluación integral (180h/360h) |
| 7 | `update_plan_formativo.php` | Campos del plan formativo en vacantes |
| 8 | `update_rechazo_organismos.php` | Rechazo de organismos con corrección por enlace |
| 9 | `seed_convenio_config.php` | Siembra `config/convenio_config.json` desde Convenio.md |
| 10 | `update_convenio_validado.php` | Convenio validado por la institución |
| 11 | `update_convenio_flow.php` | Flujo de convenios institucionales (estados, tokens, adjuntos en email_queue) |
| 12 | `update_habilidades_vacantes.php` | Perfil de vacantes por habilidades (chips) |
| 13 | `update_no_procedente_organismos.php` | Estado "no procedente" de organismos |
| 14 | `update_solicitud_practicantes_no_autorizada.php` | Vacantes no autorizadas |
| 15 | `update_fase6_postulacion.php` | **Fase 6**: nuevo flujo de postulación (prepostulaciones, estado, entrevistas_programadas, bloqueo de vacante, carta con pdf_path) |
| 16 | `update_fase6_correos.php` | **Fase 6**: plantillas de correo del nuevo flujo (5 tkeys `pp_*`) |
| 17 | `update_reportes_incidencias.php` | Tabla `reportes_incidencias` + plantillas `pp_reporte_incidencia_admin` / `pp_reporte_incidencia_confirmacion` |
| 18 | `update_incidencias_seguimiento.php` | Seguimiento admin de incidencias: fechas/solución en `reportes_incidencias`, tablas `incidencia_mensajes` e `incidencia_juntas`, plantillas `pp_incidencia_mensaje` / `_junta` / `_cierre` |
| 19 | `update_incidencias_mensajes_personalizados.php` | Sustituye `pp_incidencia_mensaje` por `pp_incidencia_mensaje_alumno` y `pp_incidencia_mensaje_empresa` (redacción ejecutiva por destinatario) |

> Nota: en `database/` quedan solo los dumps (`DB.sql`, `DB-nodata.sql`) y las utilerías (`test_evaluacion_simulation.php`, `describe_email.php`).
