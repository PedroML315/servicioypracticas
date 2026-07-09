# Reporte de Correos Automáticos — Prácticas Profesionales

**Sistema:** Servicio Social y Prácticas Profesionales — Universidad Montrer (UNIMO)
**Fecha:** 30 de junio de 2026
**Dirigido a:** Coordinación y Dirección

---

## Resumen ejecutivo

Este documento describe, en lenguaje claro, **todos los correos electrónicos que el sistema de Prácticas Profesionales envía de forma automática**. El objetivo es que cualquier persona —sin conocimientos técnicos— entienda **qué correo se envía, a quién, en qué momento y qué dice**.

**¿Cómo funcionan estos correos?**

- El sistema guarda una "plantilla" para cada tipo de correo (un asunto y un mensaje predefinidos).
- Cuando ocurre una acción (por ejemplo, se aprueba una asistencia), el sistema toma la plantilla correspondiente, **rellena automáticamente los datos personales** (nombre del alumno, fechas, etc.) y envía el correo.
- En este documento, esos datos que se rellenan solos aparecen entre corchetes, por ejemplo: **[nombre del alumno]**.

**Panorama general:**

| Destinatario | Correos en uso |
|---|---|
| Alumnos | 19 |
| Coordinadores y administradores | 9 |
| Empresas y organismos receptores | 13 |
| **Total en uso** | **41** |
| Plantillas existentes que hoy **no se envían** (en desuso o duplicadas) | 3 |

> Cada ficha incluye al final una línea de *Referencia técnica* en letra cursiva. Esa línea es únicamente para el equipo de desarrollo y puede ignorarse en una lectura ejecutiva.

---

## Índice de correos a alumnos

| N.º | Correo | Cuándo se envía |
|---|---|---|
| 1 | Información y acceso a Prácticas | Cuando se acepta al alumno en el programa |
| 2 | Carta de presentación generada | Cuando el alumno genera su carta |
| 3 | Carta de presentación expirada | Cuando vence el plazo sin presentarse |
| 4 | Presentación confirmada | Cuando el organismo confirma su llegada |
| 5 | Aceptación a prácticas (con mensaje) | Cuando el organismo lo acepta |
| 6 | Postulación no aceptada (con motivo) | Cuando el organismo lo rechaza |
| 7 | Postulación recibida | Cuando se postula a una práctica |
| 8 | Reporte aceptado por el revisor | Cuando el organismo acepta su reporte |
| 9 | Reporte parcial aprobado | Cuando el administrador aprueba el reporte parcial |
| 10 | Reporte rechazado | Cuando se rechaza un reporte |
| 11 | Reporte final aprobado | Cuando el administrador aprueba el reporte final |
| 12 | Asistencia aprobada | Cuando se aprueba su asistencia |
| 13 | Asistencia rechazada | Cuando se rechaza su asistencia |
| 14 | Asistencia con horario ajustado | Cuando se aprueba con cambios de horario |
| 15 | Advertencia por exceso de horas (1er strike) | Cuando registra más de las horas permitidas |
| 16 | Baja por exceso de horas (2do strike) | Cuando reincide en el exceso de horas |
| 17 | Postulación a área interna aceptada | Cuando se acepta su postulación a un área |
| 18 | Postulación a área interna no aceptada | Cuando se rechaza su postulación a un área |
| 19 | Reinicio de proceso de prácticas | Cuando un administrador reinicia su proceso |

---

# 1. Correos dirigidos a alumnos

### Correo No. 1 · Información y acceso a Prácticas

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador acepta el registro del alumno en Prácticas Profesionales (o al reenviarle sus credenciales). Se le entregan sus datos de acceso e información del programa.
**Asunto:** Prácticas Profesionales UNIMO - Información y formatos
**Datos personalizados:** correo del alumno, contraseña, enlace de acceso.

**Mensaje:**

> Buen día,
>
> Las Prácticas Profesionales UNIMO están coordinadas por la Oficina de Prácticas Profesionales. Encuentra sus formatos y requisitos aquí:
>
> Debes haber acreditado el quinto semestre o sexto cuatrimestre (para licenciaturas en salud, hasta finalizar estudios).
>
> Para emitir tu **Carta de Presentación** y descargar formatos, ingresa con los siguientes datos:
>
> 🔗 Ingresar al sitio: [enlace de acceso]
>
> **Usuario (correo):** [correo del alumno]
> **Contraseña:** [contraseña]
>
> **Contacto adicional** — Prácticas Profesionales: practicasprofesionales@unimontrer.edu.mx
>
> Nota: No envíes solicitudes duplicadas; se atienden en orden de llegada.
>
> Saludos. Gracias por tu atención.

*Referencia técnica — Función `sendPracticasInfo()`; plantilla `practicas_info`; se dispara desde `ctrAcceptStudentPractice()` / `ctrResendStudentPracticeCredentials()`.*

---

### Correo No. 2 · Carta de presentación generada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el alumno genera su carta de presentación. Se le informa la fecha límite (24 horas hábiles) para presentarse en el organismo receptor.
**Asunto:** Carta de presentación generada - PP
**Datos personalizados:** nombre del alumno, fecha límite, días hábiles disponibles.

**Mensaje:**

> Hola [nombre del alumno],
>
> Tu carta de presentación ha sido generada.
>
> Tienes hasta el [fecha límite] para presentarte en el organismo externo.
>
> Cuentas con [días hábiles disponibles] días hábiles.

*Referencia técnica — Función `sendPpCartaPresentacionGenerada()`; plantilla `pp_carta_presentacion_generada`; se dispara desde `generarCartaPresentacion.php`.*

---

### Correo No. 3 · Carta de presentación expirada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando un proceso automático detecta que el alumno no confirmó su presentación dentro del plazo de 24 horas hábiles.
**Asunto:** Carta de presentación expirada - PP
**Datos personalizados:** nombre del alumno.

**Mensaje:**

> Hola [nombre del alumno],
>
> Tu carta de presentación ha expirado debido a que no confirmaste tu presentación en el organismo dentro del plazo de 24 horas hábiles.
>
> Vuelve a generar otra ingresando al sistema.

*Referencia técnica — Función `sendPpCartaPresentacionExpirada()`; plantilla `pp_carta_presentacion_expirada`; se dispara desde el proceso programado `cron_expirar_cartas.php`.*

---

### Correo No. 4 · Presentación confirmada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el organismo (o el administrador) confirma la presentación del alumno; queda habilitado para comenzar sus prácticas.
**Asunto:** Presentación confirmada - PP
**Datos personalizados:** nombre del alumno.

**Mensaje:**

> Hola [nombre del alumno], el organismo ha confirmado tu presentación. Puedes comenzar tus prácticas.

*Referencia técnica — Función `sendPpCartaPresentacionConfirmada()`; plantilla `pp_carta_presentacion_confirmada`; se dispara desde `ctrConfirmarPresentacion()`.*

---

### Correo No. 5 · Aceptación a prácticas (con mensaje del organismo)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el organismo o el administrador acepta al alumno en una solicitud de practicantes, indicando un mensaje y la fecha de inicio.
**Asunto:** ¡Has sido aceptado para tus Prácticas Profesionales!
**Datos personalizados:** nombre del alumno, nombre del organismo, fecha de inicio, mensaje del organismo.

**Mensaje:**

> Hola [nombre del alumno],
>
> Nos complace informarte que has sido aceptado en **[nombre del organismo]** para realizar tus prácticas profesionales.
>
> **Fecha de inicio:** [fecha de inicio]
>
> **Mensaje del organismo:** «[mensaje del organismo]»
>
> Por favor, ponte en contacto con ellos lo antes posible para coordinar tu horario y primeras actividades.

*Referencia técnica — Función `sendPracticasProspectAcceptedWithReason()`; plantilla `pp_prospecto_aceptado_motivo`; se dispara desde `ctrAceptarProspecto()`.*

---

### Correo No. 6 · Postulación no aceptada (con motivo)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el organismo o el administrador rechaza la postulación del alumno. También se envía automáticamente cuando se llena el cupo de la práctica (a los pendientes, con el motivo "Se llenó el cupo").
**Asunto:** Aviso sobre tu postulación a Prácticas Profesionales
**Datos personalizados:** nombre del alumno, nombre del organismo, motivo.

**Mensaje:**

> Hola [nombre del alumno],
>
> Te informamos que tu postulación en **[nombre del organismo]** ha sido revisada, pero lamentablemente no fue aceptada en esta ocasión.
>
> **Motivo indicado por el organismo:** «[motivo]»
>
> Te invitamos a buscar otras oportunidades disponibles en el sistema.

*Referencia técnica — Función `sendPracticasProspectRejectedWithReason()`; plantilla `pp_prospecto_rechazado_motivo`; se dispara desde `ctrRechazarProspecto()`.*

---

### Correo No. 7 · Postulación recibida

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el alumno se postula a una práctica publicada por un organismo. Se confirma la recepción.
**Asunto:** Postulación recibida - UNIMO
**Datos personalizados:** nombre del alumno, nombre de la práctica.

**Mensaje:**

> Hola [nombre del alumno],
>
> Hemos recibido tu postulación a la práctica **[nombre de la práctica]**.
>
> Nuestro equipo y el organismo correspondiente revisarán tu solicitud. Te notificaremos cuando se tenga una respuesta.
>
> Puedes consultar el estado de tus postulaciones en el portal.
>
> 🔗 Ir al Portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendPracticesApplicationReceived()`; plantilla `practices_application_received`; se dispara desde `ctrApplyForPractice()`.*

---

### Correo No. 8 · Reporte aceptado por el revisor

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el organismo externo (revisor) acepta un reporte de prácticas del alumno (parcial o final). El reporte aún debe ser autorizado por el administrador.
**Asunto:** Prácticas Profesionales UNIMO - Reporte Aceptado
**Datos personalizados:** nombre del alumno, tipo de revisor.

**Mensaje:**

> Hola [nombre del alumno],
>
> Tu reporte de prácticas ha sido aceptado por el [tipo de revisor].
>
> Por favor espera a que el administrador autorice tu reporte para continuar con el proceso.
>
> 🔗 Ingresar al Portal
>
> Saludos cordiales, Equipo de Prácticas Profesionales - UNIMO

*Referencia técnica — Función `sendPracticasReportAcceptedEmail()`; plantilla `practicas_report_accepted_by_reviewer`; se dispara desde `acceptReport()` / `acceptReportFinal()`.*

---

### Correo No. 9 · Reporte parcial aprobado por el administrador

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador aprueba un reporte parcial del alumno.
**Asunto:** Prácticas Profesionales UNIMO - Reporte Parcial Aprobado
**Datos personalizados:** nombre del alumno.

**Mensaje:**

> Hola [nombre del alumno],
>
> ¡Tu primer reporte parcial de prácticas profesionales ha sido aprobado por el Administrador!
>
> Ya puedes continuar registrando tus asistencias en el portal.
>
> 🔗 Ingresar al Portal
>
> Si tienes dudas, contacta a practicasprofesionales@unimontrer.edu.mx.
>
> Saludos cordiales, Equipo de Prácticas Profesionales - UNIMO

*Referencia técnica — Función `sendPracticasReportAcceptedbyAdminEmail()`; plantilla `practicas_report_accepted_by_admin`; se dispara desde `ctrAcceptReportPracticebyAdmin()`.*

---

### Correo No. 10 · Reporte rechazado

**Destinatario:** Alumno
**Cuándo se envía:** Cuando un reporte (parcial o final) es rechazado por el organismo externo o por el administrador, con comentarios para corregir.
**Asunto:** Prácticas Profesionales UNIMO - Reporte Rechazado
**Datos personalizados:** nombre del alumno, tipo de revisor, comentarios.

**Mensaje:**

> Hola [nombre del alumno],
>
> Tu reporte de prácticas ha sido rechazado por el [tipo de revisor].
>
> Comentarios: [comentarios]
>
> Por favor revisa los comentarios y realiza las correcciones necesarias.
>
> 🔗 Ingresar al Portal
>
> Saludos cordiales, Equipo de Prácticas Profesionales - UNIMO

*Referencia técnica — Función `sendPracticasReportRejectedEmail()`; plantilla `practicas_report_rejected`; se dispara desde las acciones de rechazo de reportes parciales y finales.*

---

### Correo No. 11 · Reporte final aprobado por el administrador

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador aprueba el reporte final de prácticas del alumno.
**Asunto:** Prácticas Profesionales UNIMO - Reporte Final Aprobado
**Datos personalizados:** nombre del alumno.

**Mensaje:**

> Hola [nombre del alumno],
>
> ¡Tu reporte final de prácticas profesionales ha sido aprobado por el Administrador!
>
> Recuerda descargar la constancia de prácticas en el portal.
>
> 🔗 Ingresar al Portal
>
> Si tienes dudas, contacta a practicasprofesionales@unimontrer.edu.mx.
>
> Saludos cordiales, Equipo de Prácticas Profesionales - UNIMO

*Referencia técnica — Función `sendPracticasFinalReportAcceptedbyAdminEmail()`; plantilla `practicas_final_report_accepted_by_admin`; se dispara desde `ctrAcceptReportPracticeFinalbyAdmin()`.*

---

### Correo No. 12 · Asistencia aprobada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el organismo o el administrador aprueba un registro de asistencia del alumno.
**Asunto:** Aprobación de asistencia - UNIMO
**Datos personalizados:** nombre del alumno, fecha, hora de entrada, hora de salida, actividad.

**Mensaje:**

> Hola [nombre del alumno],
>
> Tu asistencia registrada el día [fecha] ha sido **APROBADA** por tu organismo.
>
> **Hora de entrada:** [hora de entrada]
> **Hora de salida:** [hora de salida]
> **Actividad acreditada:** [actividad]
>
> Gracias por tu compromiso en tus prácticas profesionales.
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendAssistanceApprovedEmail()`; plantilla `assistance_approved`; se dispara desde `ctrAprobarAsistencia()`.*

---

### Correo No. 13 · Asistencia rechazada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el organismo o el administrador rechaza un registro de asistencia del alumno.
**Asunto:** Rechazo de asistencia - UNIMO
**Datos personalizados:** nombre del alumno, fecha, hora de entrada, hora de salida, actividad.

**Mensaje:**

> Hola [nombre del alumno],
>
> Tu asistencia registrada el día [fecha] ha sido **RECHAZADA** por tu organismo.
>
> **Hora de entrada:** [hora de entrada]
> **Hora de salida:** [hora de salida]
> **Actividad reportada:** [actividad]
>
> Si deseas aclaraciones o apoyo, comunícate con el Organismo externo.
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendAssistanceRejectedEmail()`; plantilla `assistance_rejected`; se dispara desde `ctrRechazarAsistencia()`.*

---

### Correo No. 14 · Asistencia aprobada con horario ajustado

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el organismo aprueba la asistencia, pero corrige los horarios de entrada o salida.
**Asunto:** Actualización de horarios de asistencia - UNIMO
**Datos personalizados:** nombre del alumno, fecha, hora de entrada, hora de salida, actividad.

**Mensaje:**

> Hola [nombre del alumno],
>
> Tu asistencia registrada el día [fecha] ha sido **APROBADA**, pero con ajustes en los horarios realizados por tu organismo.
>
> **Hora de entrada ajustada:** [hora de entrada]
> **Hora de salida ajustada:** [hora de salida]
> **Actividad acreditada:** [actividad]
>
> Por favor revisa tus registros en el portal para confirmar los cambios.
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendAssistanceUpdatedEmail()`; plantilla `assistance_updated`; se dispara desde `ctrActualizarHorarios()`.*

---

### Correo No. 15 · Advertencia por exceso de horas (1er strike)

**Destinatario:** Alumno
**Cuándo se envía:** Al aprobar una asistencia, el sistema detecta que el alumno registró 5 o más horas en un día (el máximo permitido es 4). Se genera el primer "strike" como advertencia oficial.
**Asunto:** Advertencia: Incumplimiento de horario de prácticas
**Datos personalizados:** nombre del alumno, fecha, horas registradas, número de strike.

**Mensaje:**

> Estimado(a) [nombre del alumno],
>
> Te informamos que el día [fecha] has registrado una asistencia de [horas registradas] horas. El máximo permitido por día es de 4 horas.
>
> Esto ha generado un **1er Strike** (incumplimiento). Este correo sirve como advertencia oficial. Si acumulas un segundo strike, serás dado de baja automáticamente de tus prácticas profesionales.
>
> Por favor, respeta los lineamientos del programa.

*Referencia técnica — Función `sendStrikeAdvertenciaAlumno()`; plantilla `pp_strike_advertencia_alumno`; se dispara desde `ctrAprobarAsistencia()`.*

---

### Correo No. 16 · Baja por exceso de horas (2do strike)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el alumno acumula su segundo "strike" por exceso de horas; el sistema lo da de baja oficialmente de sus prácticas con la empresa actual.
**Asunto:** Baja Oficial de Prácticas Profesionales por Incumplimiento
**Datos personalizados:** nombre del alumno, fecha, horas registradas, número de strike.

**Mensaje:**

> Estimado(a) [nombre del alumno],
>
> Te informamos que el día [fecha] has vuelto a exceder el límite de horas permitidas, registrando [horas registradas] horas.
>
> Al ser tu **2do Strike**, el sistema te ha dado de **BAJA OFICIALMENTE** de tus prácticas profesionales con la empresa actual, de acuerdo con el reglamento vigente.
>
> Deberás reiniciar el proceso de prácticas con otro organismo receptor.

*Referencia técnica — Función `sendStrikeBajaAlumno()`; plantilla `pp_strike_baja_alumno`; se dispara desde `ctrAprobarAsistencia()`.*

---

### Correo No. 17 · Postulación a área interna aceptada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando se acepta la postulación del alumno a un área interna de Prácticas Profesionales (por el docente encargado o el administrador). A partir de la fecha de inicio podrá registrar asistencias.
**Asunto:** Tu postulación fue aceptada – Área [nombre del área]
**Datos personalizados:** nombre del alumno, nombre del área, fecha de inicio.

**Mensaje:**

> **¡Postulación aceptada!**
>
> Hola [nombre del alumno],
>
> Nos complace informarte que tu postulación al área interna de prácticas profesionales **[nombre del área]** ha sido **aceptada**.
>
> **Fecha de inicio:** [fecha de inicio]
>
> A partir de esta fecha podrás registrar tus asistencias en el sistema. El encargado del área aprobará cada registro.
>
> 🔗 Ir al sistema

*Referencia técnica — Función `sendAreaPostulacionAceptada()` (mensaje definido directamente en el código); se dispara desde `teacher.php` y `areas.php`.*

---

### Correo No. 18 · Postulación a área interna no aceptada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando se rechaza la postulación del alumno a un área interna de Prácticas Profesionales.
**Asunto:** Resultado de tu postulación – Área [nombre del área]
**Datos personalizados:** nombre del alumno, nombre del área.

**Mensaje:**

> **Postulación no aceptada**
>
> Hola [nombre del alumno],
>
> Lamentamos informarte que tu postulación al área **[nombre del área]** **no fue aceptada** en esta ocasión.
>
> Si tienes dudas, comunícate con la coordinación de prácticas profesionales para más información.
>
> 🔗 Ir al sistema

*Referencia técnica — Función `sendAreaPostulacionRechazada()` (mensaje definido directamente en el código); se dispara desde `teacher.php` y `areas.php`.*

---

### Correo No. 19 · Reinicio de proceso de prácticas

**Destinatario:** Alumno
**Cuándo se envía:** Cuando un administrador reinicia por completo el proceso de prácticas de un alumno (acción no reversible), indicando el motivo.
**Asunto:** Reinicio de Proceso de Prácticas Profesionales
**Datos personalizados:** motivo.

**Mensaje:**

> Estimado Alumno,
>
> Le informamos que su proceso de prácticas ha sido reiniciado. Motivo: **[motivo]**
>
> Por favor, inicie su proceso nuevamente.

*Referencia técnica — Plantilla `pp_hard_reset_alumno`; se envía desde `PracticasModel.php` (función de hard reset).*

---

## Índice de correos a coordinadores y administradores

| N.º | Correo | Cuándo se envía |
|---|---|---|
| 1 | Aviso de carta de presentación expirada | Cuando un alumno no se presentó a tiempo |
| 2 | Nueva solicitud de practicantes | Cuando un organismo solicita practicantes |
| 3 | Solicitud de practicantes aceptada | Cuando se aprueba una solicitud de practicantes |
| 4 | Solicitud de practicantes rechazada | Cuando se rechaza una solicitud de practicantes |
| 5 | Nueva solicitud de capacitación | Cuando un organismo solicita una capacitación |
| 6 | Alerta de strike generado | Cuando un practicante excede sus horas |
| 7 | Empresa bloqueada automáticamente | Cuando una empresa acumula 2 strikes |
| 8 | Organismo corrigió su solicitud | Cuando un organismo corrige su registro |
| 9 | Nueva postulación en tu área | Cuando un alumno se postula a un área a cargo de un docente |

---

# 2. Correos dirigidos a coordinadores y administradores

### Correo No. 1 · Aviso de carta de presentación expirada

**Destinatario:** Administrador / Coordinador de Prácticas Profesionales
**Cuándo se envía:** Cuando el proceso automático detecta una carta de presentación expirada y avisa al administrador que el alumno no se presentó a tiempo.
**Asunto:** Carta de presentación expirada (Admin) - PP
**Datos personalizados:** nombre del alumno.

**Mensaje:**

> El alumno [nombre del alumno] no se presentó a tiempo y su carta de presentación ha expirado.

*Referencia técnica — Función `sendPpCartaPresentacionExpiradaAdmin()`; plantilla `pp_carta_presentacion_expirada_admin`; se dispara desde `cron_expirar_cartas.php`.*

---

### Correo No. 2 · Nueva solicitud de practicantes

**Destinatario:** Administrador / Coordinador de Prácticas Profesionales
**Cuándo se envía:** Cuando un organismo externo registra una nueva solicitud de practicantes.
**Asunto:** Solicitud de Prácticantes - UNIMO
**Datos personalizados:** nombre del organismo, actividades a realizar, dirección de la práctica.

**Mensaje:**

> El organismo **[nombre del organismo]** ha generado una solicitud de practicantes.
>
> **Actividades a realizar:** [actividades]
> **Dirección de prácticas:** [dirección de la práctica]
>
> Puedes revisar y gestionar esta solicitud en el portal.
>
> 🔗 Abrir portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendSolicitudPracticas()`; plantilla `solicitud_practicantes`; se dispara desde `solicitarPracticas()`.*

---

### Correo No. 3 · Solicitud de practicantes aceptada

**Destinatario:** Administrador / Coordinador de Prácticas Profesionales
**Cuándo se envía:** Cuando el administrador acepta una solicitud de practicantes de un organismo.
**Asunto:** Solicitud de Prácticantes - UNIMO
**Datos personalizados:** nombre del contacto, carrera/licenciatura.

**Mensaje:**

> Hola [nombre del contacto],
>
> La Universidad Montrer ha **APROBADO** su solicitud de practicantes para la carrera de **[carrera]**.
>
> En breve, se asignarán estudiantes que podrán cubrir esta práctica y se pondrán en contacto con usted para dar inicio al proceso.
>
> Gracias por confiar en nuestra institución.
>
> Saludos cordiales, Universidad Montrer - Área de Prácticas Profesionales

*Nota: el texto está redactado para el contacto del organismo, pero el sistema entrega este aviso al buzón administrativo de Prácticas Profesionales (ver "Observaciones" al final).*

*Referencia técnica — Función `sendSolicitudPracticasAceptada()`; plantilla `solicitud_practicantes_aceptada`; se dispara desde `ctrAcceptSolicitudPracticante()`.*

---

### Correo No. 4 · Solicitud de practicantes rechazada

**Destinatario:** Administrador / Coordinador de Prácticas Profesionales
**Cuándo se envía:** Cuando el administrador rechaza una solicitud de practicantes de un organismo.
**Asunto:** Solicitud de Prácticantes - UNIMO
**Datos personalizados:** nombre del contacto, carrera/licenciatura.

**Mensaje:**

> Hola [nombre del contacto],
>
> Lamentamos informarle que la solicitud de practicantes para la carrera de **[carrera]** no ha sido aprobada en esta ocasión.
>
> Agradecemos su interés en colaborar con la Universidad Montrer y lo invitamos a enviar futuras solicitudes para próximas generaciones de estudiantes.
>
> Si requiere más información o apoyo, comuníquese al correo: practicasprofesionales@unimontrer.edu.mx.
>
> Saludos cordiales, Universidad Montrer - Área de Prácticas Profesionales

*Referencia técnica — Función `sendSolicitudPracticasRechazada()`; plantilla `solicitud_practicantes_rechazada`; se dispara desde `ctrRejectSolicitudPracticante()`.*

---

### Correo No. 5 · Nueva solicitud de capacitación

**Destinatario:** Administrador / Coordinador (correo configurado para capacitaciones)
**Cuándo se envía:** Cuando un organismo solicita una capacitación para un practicante.
**Asunto:** Nueva solicitud de capacitación de [nombre del alumno]
**Datos personalizados:** nombre del alumno, matrícula, detalle de la solicitud.

**Mensaje:**

> **Nueva solicitud de capacitación**
>
> **Alumno(a):** [nombre del alumno]
> **Matrícula:** [matrícula]
>
> Se recibió la siguiente solicitud: [detalle de la solicitud]
>
> Para gestionarla, ingresa al portal.
>
> 🔗 Abrir portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendSolicitudCapacitacion()`; plantilla `solicitud_capacitacion`; se dispara desde `solicitarCapacitacion()`.*

---

### Correo No. 6 · Alerta de strike generado

**Destinatario:** Administrador / Coordinador de Prácticas Profesionales
**Cuándo se envía:** Cuando un practicante excede el límite de horas y se genera un "strike".
**Asunto:** Alerta de sistema: Strike generado en Prácticas Profesionales
**Datos personalizados:** nombre del alumno, nombre de la empresa, fecha, horas registradas, número de strikes.

**Mensaje:**

> Se ha generado un strike en el sistema de Prácticas Profesionales.
>
> - Alumno: [nombre del alumno]
> - Empresa: [nombre de la empresa]
> - Fecha de asistencia: [fecha]
> - Horas registradas: [horas registradas]
> - Strikes acumulados de la empresa: [número de strikes]
>
> Por favor, revise el panel de administración si desea ver más detalles.

*Referencia técnica — Función `sendStrikeAdmin()`; plantilla `pp_strike_admin`; se dispara desde `ctrAprobarAsistencia()`.*

---

### Correo No. 7 · Empresa bloqueada automáticamente

**Destinatario:** Administrador / Coordinador de Prácticas Profesionales
**Cuándo se envía:** Cuando una empresa acumula 2 strikes y el sistema la bloquea automáticamente.
**Asunto:** Alerta: Empresa bloqueada automáticamente
**Datos personalizados:** nombre de la empresa, nombre del alumno.

**Mensaje:**

> El sistema ha bloqueado automáticamente a la empresa **[nombre de la empresa]** debido a la acumulación de 2 strikes.
>
> El practicante que generó el segundo strike fue: [nombre del alumno].
>
> La empresa ya no podrá solicitar nuevos practicantes a menos que un administrador la desbloquee manualmente.

*Referencia técnica — Función `sendOrganismoBloqueadoAdminNotif()`; plantilla `pp_organismo_bloqueado_admin_notif`; se dispara desde `ctrAprobarAsistencia()`.*

---

### Correo No. 8 · Organismo corrigió su solicitud

**Destinatario:** Administrador / Coordinador de Prácticas Profesionales
**Cuándo se envía:** Cuando un organismo previamente rechazado corrige su solicitud de registro mediante el enlace temporal; el sistema avisa que está lista para una nueva revisión.
**Asunto:** Organismo corregido pendiente de revisión — [empresa]
**Datos personalizados:** empresa, número de organismo, fecha de corrección, lista de campos corregidos.

**Mensaje:**

> El organismo **[empresa]** (ID #[número de organismo]) ha corregido su solicitud de registro y está listo para ser revisado nuevamente.
>
> **Fecha de corrección:** [fecha de corrección]
>
> **Campos corregidos:** [lista de campos corregidos, con el valor anterior y el nuevo]
>
> Ingresa al panel de administración para revisar y aprobar o rechazar la solicitud.

*Referencia técnica — Función `sendOrganismoCorregidoAdmin()`; plantilla `pp_organismo_corregido_admin`; se dispara desde `correccion-organismo.php`.*

---

### Correo No. 9 · Nueva postulación en tu área (docente encargado)

**Destinatario:** Coordinador / Docente encargado de un área interna de Prácticas Profesionales
**Cuándo se envía:** Cuando un alumno se postula a un área interna a cargo de un docente.
**Asunto:** Nueva postulación en tu área – [nombre del área]
**Datos personalizados:** nombre del docente, nombre del alumno, nombre del área.

**Mensaje:**

> **Nueva postulación recibida**
>
> Hola [nombre del docente],
>
> El alumno **[nombre del alumno]** se ha postulado al área de prácticas profesionales **[nombre del área]** a tu cargo.
>
> Ingresa al sistema para revisar y gestionar la postulación.
>
> 🔗 Ir al sistema

*Referencia técnica — Función `sendNuevaPostulacionArea()` (mensaje definido directamente en el código); se dispara desde `students_flow.php`.*

---

## Índice de correos a empresas y organismos receptores

| N.º | Correo | Cuándo se envía |
|---|---|---|
| 1 | Acreditación y datos de acceso | Cuando se acredita al organismo |
| 2 | Solicitud de registro rechazada | Cuando se rechaza el registro del organismo |
| 3 | Código de verificación (OTP) | Cuando el organismo pide acceso para corregir |
| 4 | Enlace de corrección expirado | (Previsto; hoy no se envía) |
| 5 | Nueva postulación a práctica | Cuando un alumno se postula a su práctica |
| 6 | Alumno asignado (prospecto aceptado) | Cuando se le asigna un practicante |
| 7 | Solicitud de capacitación aceptada | Cuando se acepta su solicitud de capacitación |
| 8 | Solicitud de capacitación rechazada | Cuando se rechaza su solicitud de capacitación |
| 9 | Registro de asistencia de practicante | Cuando un alumno registra asistencia |
| 10 | Notificación de strike | Cuando su practicante excede horas |
| 11 | Bloqueo automático de solicitudes | Cuando acumula 2 strikes |
| 12 | Bloqueo manual de solicitudes | Cuando un administrador lo bloquea |
| 13 | Desbloqueo de solicitudes | Cuando un administrador lo desbloquea |

---

# 3. Correos dirigidos a empresas y organismos receptores

### Correo No. 1 · Acreditación y datos de acceso del organismo

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando el administrador acredita (acepta) el registro de un organismo externo. Se le entregan sus credenciales de acceso.
**Asunto:** Organismo Externo UNIMO - Acreditación y acceso al portal
**Datos personalizados:** correo, contraseña, enlace de acceso.

**Mensaje:**

> Buen día,
>
> Gracias por tu paciencia. Nos complace informarte que tu institución ha sido **acreditada** como **Organismo Externo de Prácticas Profesionales** en Universidad Montrer.
>
> A partir de ahora podrás revisar y aprobar las solicitudes de alumnos que desean realizar sus prácticas profesionales en tu organización.
>
> 🔗 Acceder al Portal de Organismos Externos: [enlace de acceso]
>
> Tus credenciales de acceso son:
> **Usuario (correo):** [correo]
> **Contraseña:** [contraseña]
>
> No responder a este correo; para cualquier consulta o soporte, escribe a: practicasprofesionales@unimontrer.edu.mx
>
> Nota: Las acreditaciones y solicitudes se procesan en orden de llegada.
>
> Saludos cordiales, Equipo de Prácticas Profesionales - UNIMO

*Referencia técnica — Función `sendPracticasOrganismoExternoInfo()`; plantilla `practicas_organismo_externo_info`; se dispara desde `ctrAcceptExternal()`.*

---

### Correo No. 2 · Solicitud de registro de organismo rechazada

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando el administrador rechaza la solicitud de registro de un organismo, detallando el motivo y los campos con errores. Incluye un enlace temporal (válido 72 horas) para corregir.
**Asunto:** Tu solicitud de registro como Organismo Receptor ha sido rechazada — UNIMO
**Datos personalizados:** empresa, motivo general, lista de campos con error, enlace de corrección, fecha de expiración, correo de Prácticas Profesionales.

**Mensaje:**

> Estimado(a) **[empresa]**,
>
> Tras revisar tu solicitud de registro como Organismo Receptor en el programa de Prácticas Profesionales de la Universidad Montrer (UNIMO), el equipo administrativo ha detectado información que requiere corrección.
>
> **Motivo del rechazo:** «[motivo general]»
>
> **Campos con errores identificados:** [lista de campos con su observación]
>
> Para corregir tu información, utiliza el siguiente enlace temporal y seguro (válido por 72 horas):
>
> 🔗 Corregir mi solicitud: [enlace de corrección]
>
> Este enlace expira el [fecha de expiración]. No compartas este enlace con nadie.
>
> Si tienes dudas, contáctanos en [correo de Prácticas Profesionales].

*Referencia técnica — Función `sendOrganismoRechazado()`; plantilla `pp_organismo_rechazado`; se dispara desde `ctrRejectExternalWithReasons()`.*

---

### Correo No. 3 · Código de verificación (OTP) para corrección

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando el organismo abre el enlace de corrección y solicita un código de un solo uso para autenticarse.
**Asunto:** Tu código de verificación — UNIMO Prácticas Profesionales
**Datos personalizados:** empresa, código de verificación.

**Mensaje:**

> Estimado(a) **[empresa]**,
>
> Tu código de verificación para acceder al formulario de corrección es:
>
> **[código de verificación]**
>
> Este código es válido por 10 minutos y solo puede usarse una vez.
>
> Si no solicitaste este código, ignora este correo. Nadie de UNIMO te pedirá este código por teléfono.

*Nota: el texto dice 10 minutos; el sistema fija la vigencia en 15 minutos.*

*Referencia técnica — Función `sendOrganismoOtp()`; plantilla `pp_organismo_otp`; se dispara desde `correccion-organismo.php`.*

---

### Correo No. 4 · Enlace de corrección expirado · ⚠️ Hoy no se envía

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Estaba previsto para avisar que el enlace de corrección expiró. **Actualmente este correo no se envía**, porque el sistema no tiene activado el punto que lo dispararía. La plantilla existe y está lista.
**Asunto:** Tu enlace de corrección ha expirado — UNIMO
**Datos personalizados:** empresa, correo de Prácticas Profesionales.

**Mensaje:**

> Estimado(a) **[empresa]**,
>
> El enlace que recibiste para corregir tu solicitud de registro ha **expirado**.
>
> Por favor, contacta al equipo de Prácticas Profesionales de UNIMO en [correo de Prácticas Profesionales] para que te envíen un nuevo enlace.

*Referencia técnica — Función `sendOrganismoTokenExpirado()` (definida pero no invocada); plantilla `pp_organismo_token_expirado`.*

---

### Correo No. 5 · Nueva postulación a práctica

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando un alumno se postula a una práctica publicada por el organismo.
**Asunto:** Nueva postulación a práctica - UNIMO
**Datos personalizados:** nombre del contacto, nombre del alumno, nombre de la práctica.

**Mensaje:**

> Hola [nombre del contacto],
>
> El alumno **[nombre del alumno]** se ha postulado para participar en la práctica **[nombre de la práctica]**.
>
> Por favor ingrese al portal para revisar la solicitud y tomar una decisión.
>
> 🔗 Ingresar al Portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendPracticesApplicationReceivedOrg()`; plantilla `practices_application_received_org`; se dispara desde `ctrApplyForPractice()`.*

---

### Correo No. 6 · Alumno asignado (prospecto aceptado)

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando se acepta a un alumno (prospecto) en una solicitud de practicantes; se avisa al contacto del organismo con la fecha de inicio.
**Asunto:** Prospecto aceptado - UNIMO
**Datos personalizados:** nombre del contacto, nombre del alumno, fecha de inicio.

**Mensaje:**

> Hola [nombre del contacto],
>
> Le informamos que el alumno **[nombre del alumno]** ha sido asignado a su organismo para la realización de prácticas profesionales.
>
> **Fecha de inicio:** [fecha de inicio]
>
> Por favor, ingrese al portal para consultar más información y dar seguimiento al proceso.
>
> 🔗 Ingresar al Portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendPracticasProspectAcceptedOrg()`; plantilla `practicas_prospect_accepted_org`; se dispara desde `ctrAceptarProspecto()`.*

---

### Correo No. 7 · Solicitud de capacitación aceptada

**Destinatario:** Empresa / Organismo Receptor (contacto solicitante)
**Cuándo se envía:** Cuando el administrador acepta una solicitud de capacitación.
**Asunto:** Solicitud de Capacitaciones Aceptada - UNIMO
**Datos personalizados:** nombre del contacto, nombre del alumno, comentario, fecha de creación.

**Mensaje:**

> Hola [nombre del contacto],
>
> La solicitud de capacitación enviada por **[nombre del alumno]** el [fecha de creación] ha sido **ACEPTADA**.
>
> [comentario del administrador]
>
> Puedes gestionar el proceso desde el portal.
>
> 🔗 Abrir portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendSolicitudCapacitacionAceptada()`; plantilla `solicitud_capacitacion_aceptada`; se dispara desde `ctrAcceptSolicitudCapacitacion()`.*

---

### Correo No. 8 · Solicitud de capacitación rechazada

**Destinatario:** Empresa / Organismo Receptor (contacto solicitante)
**Cuándo se envía:** Cuando el administrador rechaza una solicitud de capacitación.
**Asunto:** Solicitud de Capacitaciones Rechazada - UNIMO
**Datos personalizados:** nombre del contacto, nombre del alumno, fecha de creación, comentarios.

**Mensaje:**

> Hola [nombre del contacto],
>
> La solicitud de capacitación enviada por **[nombre del alumno]** el [fecha de creación] ha sido **RECHAZADA**.
>
> **Motivo/Comentarios:** [comentarios]
>
> 🔗 Abrir portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendSolicitudCapacitacionRechazada()`; plantilla `solicitud_capacitacion_rechazada`; se dispara desde `ctrRejectSolicitudCapacitacion()`.*

---

### Correo No. 9 · Registro de asistencia de practicante

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando un alumno registra una asistencia en su práctica; se avisa al organismo para validarla.
**Asunto:** Registro de asistencia - UNIMO
**Datos personalizados:** nombre del organismo, nombre del alumno, fecha, hora de entrada, hora de salida, actividad.

**Mensaje:**

> Hola [nombre del organismo],
>
> El alumno **[nombre del alumno]** registró asistencia el día [fecha].
>
> **Hora de entrada:** [hora de entrada]
> **Hora de salida:** [hora de salida]
> **Actividad reportada:** [actividad]
>
> Por favor, ingrese al portal para acreditar o rechazar este reporte de asistencia.
>
> 🔗 Abrir portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendAssistanceRegisteredEmail()`; plantilla `assistance_registered`; se dispara desde `ctrRegisterAttendance()`.*

---

### Correo No. 10 · Notificación de strike (a la empresa)

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando un practicante excede el límite de horas y genera un "strike"; se informa a la empresa el conteo acumulado (máximo 2).
**Asunto:** Notificación de incumplimiento de horario de practicante
**Datos personalizados:** nombre de la empresa, nombre del alumno, horas registradas, fecha, número de strikes.

**Mensaje:**

> Estimado(a) [nombre de la empresa],
>
> Le informamos que el practicante [nombre del alumno] ha registrado [horas registradas] horas el día [fecha], excediendo el límite de 4 horas diarias permitido por la Universidad.
>
> Esto genera un "strike" en el historial de su empresa. Actualmente su empresa tiene **[número de strikes] strike(s)** acumulados de un máximo de 2.
>
> Le recordamos que si su empresa acumula 2 strikes, el sistema bloqueará automáticamente la posibilidad de solicitar nuevos practicantes en el futuro.

*Referencia técnica — Función `sendStrikeOrganismo()`; plantilla `pp_strike_organismo`; se dispara desde `ctrAprobarAsistencia()`.*

---

### Correo No. 11 · Bloqueo automático de solicitudes

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando la empresa acumula 2 strikes y el sistema bloquea automáticamente la creación de nuevas solicitudes de practicantes.
**Asunto:** Bloqueo automático de nuevas solicitudes de practicantes
**Datos personalizados:** nombre de la empresa, motivo.

**Mensaje:**

> Estimado(a) [nombre de la empresa],
>
> Le informamos que debido a que ha acumulado 2 strikes por incumplimiento en el límite de horas diarias de sus practicantes, el sistema ha **bloqueado automáticamente** la creación de nuevas solicitudes de practicantes para su empresa.
>
> Motivo: [motivo]
>
> Nota: Los practicantes que actualmente se encuentran en su empresa no se ven afectados por este bloqueo y podrán continuar hasta finalizar su periodo.
>
> Si desea apelar esta decisión, por favor póngase en contacto con el administrador de Prácticas Profesionales de la Universidad.

*Referencia técnica — Función `sendOrganismoBloqueadoAuto()`; plantilla `pp_organismo_bloqueado_auto`; se dispara desde `ctrAprobarAsistencia()`.*

---

### Correo No. 12 · Bloqueo manual de solicitudes

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando un administrador bloquea manualmente la recepción de nuevas solicitudes de un organismo, indicando el motivo.
**Asunto:** Notificación de Bloqueo de Solicitudes - UNIMO
**Datos personalizados:** motivo.

**Mensaje:**

> Estimado Organismo,
>
> Le informamos que se ha bloqueado la recepción de nuevas solicitudes de practicantes por el siguiente motivo: **[motivo]**
>
> Los alumnos activos no se verán afectados. Para más información comuníquese con administración.

*Referencia técnica — Plantilla `pp_organismo_bloqueado_manual`; se envía desde `PracticasModel.php` (bloqueo manual).*

---

### Correo No. 13 · Desbloqueo de solicitudes

**Destinatario:** Empresa / Organismo Receptor
**Cuándo se envía:** Cuando un administrador restaura (desbloquea) la capacidad de un organismo para solicitar nuevos practicantes.
**Asunto:** Notificación de Desbloqueo de Solicitudes - UNIMO
**Datos personalizados:** ninguno.

**Mensaje:**

> Estimado Organismo,
>
> Le informamos que su capacidad de solicitar nuevos practicantes ha sido restaurada.

*Referencia técnica — Plantilla `pp_organismo_desbloqueado`; se envía desde `PracticasModel.php` (desbloqueo).*

---

# 4. Plantillas que existen pero hoy no se envían

> Las siguientes plantillas están guardadas y listas en el sistema, pero **actualmente no se envían**: o no tienen un proceso que las dispare, o fueron reemplazadas por versiones más nuevas. Se incluyen para tener el inventario completo.

### Adicional 1 · Nuevo organismo registrado (aviso al administrador) · Sin uso

**Destinatario:** Administrador de Prácticas
**Situación:** Previsto para avisar al administrador cuando se registra un nuevo organismo. **No hay un proceso que lo envíe**; solo aparece en una herramienta interna de pruebas.
**Asunto:** [Prácticas] Nuevo organismo registrado: [nombre del organismo]

**Mensaje:**

> **Nuevo Organismo Receptor Registrado**
>
> Administrador de Prácticas,
>
> Se ha registrado un nuevo organismo receptor el [fecha de registro].
>
> **Datos del organismo:**
> Nombre: [nombre del organismo]
> Contacto: [contacto del organismo]
> Correo: [correo del organismo]
> Teléfono: [teléfono del organismo]
>
> **Acción requerida:** Revisa los datos en el panel de administración y actívalo si todo es correcto.

*Referencia técnica — Plantilla `nuevo_organismo_practicas`; sin función de envío.*

---

### Adicional 2 · Aceptación a prácticas (versión anterior) · Reemplazada

**Destinatario:** Alumno
**Situación:** Versión anterior del correo de aceptación al alumno. Hoy el sistema usa el **Correo No. 5** de alumnos, por lo que esta plantilla ya no se envía.
**Asunto:** Aceptación a prácticas - UNIMO

**Mensaje:**

> Hola [nombre del alumno],
>
> Nos complace informarte que has sido **ACEPTADO** para realizar tus prácticas profesionales en el organismo: **[nombre del organismo]**
>
> **Fecha de inicio:** [fecha de inicio]
>
> Te invitamos a ingresar al portal para consultar los siguientes pasos y descargar tus formatos.
>
> 🔗 Ir al Portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Plantilla `practicas_prospect_accepted_student`; reemplazada por `pp_prospecto_aceptado_motivo`.*

---

### Adicional 3 · Postulación rechazada (versión anterior) · Reemplazada

**Destinatario:** Alumno
**Situación:** Versión anterior del correo de rechazo al alumno. Hoy el sistema usa el **Correo No. 6** de alumnos, por lo que esta plantilla ya no se envía.
**Asunto:** Resultado de postulación - UNIMO

**Mensaje:**

> Hola [nombre del alumno],
>
> Lamentamos informarte que tu postulación para realizar prácticas profesionales no ha sido aceptada.
>
> Agradecemos tu interés y disposición. Te invitamos a continuar buscando otras opciones dentro de la plataforma.
>
> Si requieres apoyo o tienes dudas, comunícate al correo: practicasprofesionales@unimontrer.edu.mx.
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Plantilla `practicas_prospect_rejected`; reemplazada por `pp_prospecto_rechazado_motivo`.*

---

## Observaciones y recomendaciones

- **Total de correos en uso:** 41 (19 a alumnos, 9 a coordinadores/administradores, 13 a empresas/organismos).
- **Plantillas que existen pero no se envían:** 3 (un aviso de nuevo organismo sin proceso, y dos versiones anteriores ya reemplazadas).
- **Punto de mejora detectado:** los correos de "Solicitud de practicantes aceptada/rechazada" están redactados para el contacto del organismo, pero el sistema los entrega al buzón administrativo de Prácticas Profesionales. Se recomienda revisar a quién deben llegar realmente.
- **Punto de mejora detectado:** el correo "Enlace de corrección expirado" (No. 4 de empresas) está listo pero no se envía; conviene decidir si se activa.
- **Dato verificable:** todos los textos de este reporte provienen de las plantillas reales almacenadas en el sistema; no se inventó ningún contenido.
