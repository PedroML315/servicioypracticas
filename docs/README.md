# Documentación del sistema — Servicio Social y Prácticas Profesionales UNIMO

Índice de la documentación técnica. Los diagramas usan sintaxis Mermaid (se renderizan en GitHub/VS Code con la extensión de Markdown Preview Mermaid).

## Arquitectura y auditoría

| Documento | Contenido |
|---|---|
| [arquitectura_sistema.md](arquitectura_sistema.md) | Documento maestro: stack, patrón MVC, auditoría de seguridad (SEC-*), optimización (OPT-*), deuda técnica (DEBT-*), tablas y mapa de endpoints |
| [dependency_analysis.md](dependency_analysis.md) / [dependency_migration.md](dependency_migration.md) | Análisis y migración de dependencias |

## Flujos de negocio (diagramas)

### Prácticas Profesionales (PP)

| Documento | Flujo |
|---|---|
| [flujo_registro_organismos.md](flujo_registro_organismos.md) | Registro público del organismo → aprobación admin → credenciales → estados de bloqueo por strikes |
| [flujo_vacantes_practicantes.md](flujo_vacantes_practicantes.md) | Wizard "Nueva Vacante" (habilidades), aprobación admin, gestor master-detail, edición/eliminación, expiración |
| [flujo_postulacion_alumnos.md](flujo_postulacion_alumnos.md) | Registro alumno PP (GES) → postulación → entrevista → aceptación → carta de presentación (24h hábiles) → presentación confirmada |
| [flujo_asistencias_alumnos.md](flujo_asistencias_alumnos.md) | Registro diario de asistencia, aprobación del organismo, strikes por exceso de horas (Fase 3), flujo de áreas internas |
| [flujo_reportes_evaluaciones_practicas.md](flujo_reportes_evaluaciones_practicas.md) | Hitos 180h/360h: reportes parcial/final, evaluaciones con rúbricas, bloqueos, constancia de acreditación |

### Servicio Social (SS)

| Documento | Flujo |
|---|---|
| [flujo_servicio_social_eventos.md](flujo_servicio_social_eventos.md) | Registro alumno SS, selección de tipo de servicio, eventos con puntos, calificación, carta de conclusión |
| [flujo_ijumich.md](flujo_ijumich.md) | Flujo documental IJUMICH: 9 pasos interno, flujo externo, aprobación admin, folios y sellado de PDFs |

### Transversales

| Documento | Flujo |
|---|---|
| [flujo_correos.md](flujo_correos.md) | Plantillas `email_templates`, cola `email_queue`, procesador cron con reintentos |

## Roles y permisos

| Documento | Rol |
|---|---|
| [rol_admin.md](rol_admin.md) | Administrador general |
| [rol_admin_servicio.md](rol_admin_servicio.md) | Admin de Servicio Social |
| [rol_admin_practicas.md](rol_admin_practicas.md) | Admin de Prácticas Profesionales |
| [rol_teacher.md](rol_teacher.md) | Encargado de área |
| [rol_student.md](rol_student.md) | Alumno de Servicio Social |
| [rol_alumno_practicas.md](rol_alumno_practicas.md) | Alumno de Prácticas Profesionales |
| [rol_organismo_externo.md](rol_organismo_externo.md) | Organismo receptor |

## Correos (catálogo ejecutivo)

| Documento | Contenido |
|---|---|
| [mails/practicas_profesionales.md](mails/practicas_profesionales.md) | Los 41 correos automáticos de PP: destinatario, momento y texto |
| [mails/servicio_social.md](mails/servicio_social.md) | Correos automáticos de SS |
