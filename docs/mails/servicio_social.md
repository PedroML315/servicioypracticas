# Reporte de Correos Automáticos — Servicio Social

**Sistema:** Servicio Social y Prácticas Profesionales — Universidad Montrer (UNIMO)
**Fecha:** 30 de junio de 2026
**Dirigido a:** Coordinación y Dirección

---

## Resumen ejecutivo

Este documento describe, en lenguaje claro, **todos los correos electrónicos que el sistema de Servicio Social envía de forma automática**. El objetivo es que cualquier persona —sin conocimientos técnicos— entienda **qué correo se envía, a quién, en qué momento y qué dice**. Incluye también el flujo paso a paso del **Servicio Social Interno (trámite IJUMICH)** y los correos del **módulo de eventos**.

**¿Cómo funcionan estos correos?**

- El sistema guarda una "plantilla" para cada tipo de correo (un asunto y un mensaje predefinidos).
- Cuando ocurre una acción (por ejemplo, el alumno sube un documento), el sistema toma la plantilla correspondiente, **rellena automáticamente los datos personales** (nombre del alumno, fechas, etc.) y envía el correo.
- En este documento, esos datos que se rellenan solos aparecen entre corchetes, por ejemplo: **[nombre del alumno]**.

**Sobre el Servicio Social Interno (IJUMICH):** es un trámite por pasos. En cada paso, el alumno sube un documento y la Coordinación lo revisa, lo firma/sella y lo aprueba o rechaza. El sistema envía avisos en ambos sentidos: al alumno y a la Coordinación.

**Panorama general:**

| Destinatario | Correos en uso |
|---|---|
| Alumnos | 17 |
| Coordinadores y administradores | 10 |
| **Total en uso** | **27** |
| Plantillas existentes que hoy **no se envían** | 1 |

> Cada ficha incluye al final una línea de *Referencia técnica* en letra cursiva, únicamente para el equipo de desarrollo; puede ignorarse en una lectura ejecutiva.

---

## Índice de correos a alumnos

| N.º | Correo | Cuándo se envía |
|---|---|---|
| 1 | Solicitud de registro recibida | Cuando el alumno se registra |
| 2 | Credenciales de acceso | Cuando se acepta su solicitud |
| 3 | Información y formatos de Servicio Social | Al registrar alumnos de tipo empresa |
| 4 | Solicitud rechazada | Cuando se deniega su solicitud |
| 5 | Baja de Servicio Social | Cuando se le da de baja |
| 6 | Nuevo evento disponible | Cuando se crea un evento |
| 7 | Evento cancelado | Cuando se cancela un evento |
| 8 | Participación en evento aprobada | Cuando se aprueba su participación |
| 9 | Participación en evento rechazada | Cuando se rechaza su participación |
| 10 | Carta de conclusión generada (Paso 6) | Cuando genera su carta de conclusión |
| 11 | Carta de presentación aprobada (Paso 1) | Cuando se aprueba el Paso 1 |
| 12 | Carta de prácticas aprobada (Paso 2) | Cuando se aprueba el Paso 2 |
| 13 | Solicitud de registro firmada (Paso 4) | Cuando se devuelve firmada |
| 14 | Reporte parcial aprobado (Paso 5) | Cuando se aprueba un reporte parcial |
| 15 | Liberación definitiva (Paso 9) | Cuando se aprueba la Evaluación Global |
| 16 | Documento rechazado | Cuando se rechaza cualquier paso |
| 17 | Servicio Social liberado (Paso 7) | (Previsto; hoy no se envía) |

---

# 1. Correos dirigidos a alumnos

### Correo No. 1 · Solicitud de registro recibida

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el alumno completa su registro/solicitud de Servicio Social. Se confirma la recepción.
**Asunto:** Solicitud recibida – Servicio Social UNIMO
**Datos personalizados:** nombre del alumno.

**Mensaje:**

> Estimado/a Estudiante [nombre del alumno],
>
> Hemos recibido tu solicitud de ingreso al sistema. Nuestro equipo la revisará y te daremos respuesta a la brevedad.
>
> Agradecemos tu interés y confianza en nosotros.
>
> Si necesitas información adicional o tienes alguna duda, no dudes en ponerte en contacto.
>
> ¡Que tengas un excelente día!

*Referencia técnica — Función `sendServiceSocialApplicationReceived()`; plantilla `social_service_application`; se dispara desde `ctrRegisterStudent()`.*

---

### Correo No. 2 · Credenciales de acceso (alumno aceptado)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador acepta la solicitud de Servicio Social del alumno. Se le entregan sus credenciales.
**Asunto:** Servicio social UNIMO - Nueva contraseña
**Datos personalizados:** correo del alumno, contraseña, enlace de acceso.

**Mensaje:**

> **Bienvenido al servicio social de UNIMO**
>
> Estamos emocionados de que formes parte de nuestro programa.
>
> Tu email registrado es: [correo del alumno]
> Tu contraseña es: [contraseña]
>
> Para iniciar sesión en nuestra plataforma, haz clic en el botón de abajo o visita nuestro sitio web.
>
> 🔗 Iniciar sesión: [enlace de acceso]

*Referencia técnica — Función `sendPasswordToStudent()`; plantilla `password_student`; se dispara desde `ctrAcceptStudent()`.*

---

### Correo No. 3 · Información y formatos de Servicio Social

**Destinatario:** Alumno
**Cuándo se envía:** Al registrar a un alumno de tipo "empresa". Se le entrega la información, los formatos y sus credenciales de acceso.
**Asunto:** Servicio Social UNIMO - Información y formatos
**Datos personalizados:** correo del alumno, contraseña, enlace de acceso.

**Mensaje:**

> Buen día,
>
> El Servicio Social UNIMO está regulado por el Instituto de la Juventud Michoacana, ubicado en Periférico Paseo de la República 2451, C.P. 58290, Morelia, Mich. Encuentra sus formatos y requisitos aquí:
>
> 🔗 Visitar Instituto (serviciosocial.ijumich.michoacan.gob.mx)
>
> Asegúrate de ver si tu empresa, institución o dependencia cuenta con un Programa de Servicio Social registrado y vigente antes de tu desarrollo.
>
> El Servicio Social debe realizarse después de acreditar las Prácticas Profesionales y de haber concluido quinto semestre o sexto cuatrimestre (para licenciaturas en salud, hasta finalizar estudios profesionales).
>
> Para emitir tu Carta de Presentación, ingresa con los siguientes datos:
>
> 🔗 Ingresar al sitio: [enlace de acceso]
> Usuario (correo): [correo del alumno]
> Contraseña: [contraseña]
>
> Contacto adicional — Servicio Social: serviciosocial@unimontrer.edu.mx
>
> Nota: No envíes la misma solicitud más de una vez; se atienden en orden de llegada.
>
> Saludos. Gracias por tu atención.

*Referencia técnica — Función `sendServiceSocialInfo()`; plantilla `service_social_info`; se dispara desde `ctrRegisterStudent()`.*

---

### Correo No. 4 · Solicitud de Servicio Social rechazada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador deniega la solicitud de Servicio Social del alumno.
**Asunto:** Resultado de tu solicitud de servicio social
**Datos personalizados:** nombre del alumno.

**Mensaje:**

> Estimado/a Estudiante [nombre del alumno],
>
> Lamentamos informarte que tu solicitud de servicio social ha sido rechazada.
>
> Te recomendamos ponerte en contacto con el área correspondiente para conocer más detalles y aclarar cualquier duda.
>
> Agradecemos tu interés y disposición para participar en el servicio social.
>
> ¡Te deseamos mucho éxito en tus próximos proyectos!

*Referencia técnica — Función `sendRejectServiceSocialApplication()`; plantilla `reject_service_social_application`; se dispara desde `ctrDenegateStudent()`.*

---

### Correo No. 5 · Baja de Servicio Social

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador da de baja a un alumno de Servicio Social, indicando motivo y fecha.
**Asunto:** Baja de servicio social - UNIMO
**Datos personalizados:** nombre del alumno, motivo, fecha.

**Mensaje:**

> Hola [nombre del alumno],
>
> Lamentamos informarte que tu participación en el Servicio Social UNIMO ha sido dada de baja el día [fecha].
>
> **Motivo:** [motivo]
>
> Si requieres apoyo o deseas aclarar esta situación, por favor comunícate al correo: serviciosocial@unimontrer.edu.mx, donde con gusto te atenderemos.
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendDropStudentEmail()`; plantilla `service_social_dropped`; se dispara desde `ctrDropStudent()`.*

---

### Correo No. 6 · Nuevo evento disponible

**Destinatario:** Alumno
**Cuándo se envía:** Cuando se crea un nuevo evento. Se avisa a cada alumno activo que aún no alcanza el mínimo de puntos requeridos y cuyo tipo de servicio coincide con el evento.
**Asunto:** Nuevo evento disponible: [nombre del evento]
**Datos personalizados:** nombre del evento, descripción del evento.

**Mensaje:**

> Estimado/a Estudiante,
>
> Se ha creado un nuevo evento en el que puede participar:
>
> **[nombre del evento]**
>
> [descripción del evento]
>
> Te recomendamos que te inscribas para continuar con tu servicio social.
>
> 🔗 Inscribirme al Evento
>
> Si tienes alguna pregunta adicional o necesitas más asistencia, no dudes en contactarnos.
>
> ¡Que tengas un excelente día!

*Referencia técnica — Función `sendNewEvent()`; plantilla `new_event`; se dispara desde `mdlAddEvent()`.*

---

### Correo No. 7 · Evento cancelado

**Destinatario:** Alumno
**Cuándo se envía:** Cuando un administrador cancela (elimina) un evento. Se avisa a todos los alumnos del mismo tipo de servicio.
**Asunto:** Evento cancelado: [nombre del evento]
**Datos personalizados:** nombre del evento, descripción del evento.

**Mensaje:**

> Estimado/a Estudiante,
>
> **Importante:** El siguiente evento ha sido cancelado:
>
> **[nombre del evento]**
>
> [descripción del evento]
>
> Si ya te habías inscrito, no necesitas realizar ninguna acción adicional. En caso de reprogramación, te enviaremos una nueva notificación por este medio.
>
> 🔗 Ver otros eventos disponibles

*Referencia técnica — Función `cancelEvent()`; plantilla `cancel_event`; se dispara desde `ajax.deleteEvent.php`.*

---

### Correo No. 8 · Participación en evento aprobada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el organizador/administrador aprueba la participación del alumno en un evento y se le asignan los puntos correspondientes.
**Asunto:** Aprobación de participación en evento - UNIMO
**Datos personalizados:** nombre del alumno, nombre del evento, fecha del evento, puntos.

**Mensaje:**

> Hola [nombre del alumno],
>
> ¡Felicidades! Tu participación en el evento **[nombre del evento]** realizado el día [fecha del evento] ha sido **APROBADA**.
>
> Se te han asignado **[puntos] puntos** por tu asistencia.
>
> Estos puntos ya fueron sumados a tu historial dentro de la plataforma de Servicio Social.
>
> Consulta tu avance en el portal.
>
> 🔗 Ir al Portal
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendAproveEventEmail()`; plantilla `event_participation_approved`; se dispara desde `ctrApproveEvent()`.*

---

### Correo No. 9 · Participación en evento rechazada

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el organizador/administrador rechaza la candidatura o la participación del alumno en un evento.
**Asunto:** Rechazo de participación en evento - UNIMO
**Datos personalizados:** nombre del alumno, nombre del evento, fecha del evento.

**Mensaje:**

> Hola [nombre del alumno],
>
> Lamentamos informarte que tu participación en el evento **[nombre del evento]**, programado para el día [fecha del evento], ha sido **RECHAZADA**.
>
> Si deseas más información o apoyo, comunícate con el área correspondiente de Servicio Social en el correo: serviciosocial@unimontrer.edu.mx.
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendDeclineEventEmail()`; plantilla `event_participation_rejected`; se dispara desde `ctrAcceptCandidate()` y `ctrApproveEvent()`.*

---

### Correo No. 10 · Carta de conclusión generada (Servicio Social Interno · Paso 6)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el alumno genera (por primera vez) su carta de conclusión de Servicio Social Interno.
**Asunto:** Tu Carta de Conclusión de Servicio Social ha sido generada — Folio [folio]
**Datos personalizados:** nombre del alumno, folio, fecha de inicio, fecha de fin, horas, meses.

**Mensaje:**

> **✅ Carta de Conclusión de Servicio Social**
>
> Hola [nombre del alumno],
>
> Tu Carta de Conclusión de Servicio Social ha sido generada exitosamente en el sistema.
>
> **Folio asignado:** [folio]
>
> - Fecha de inicio: [fecha de inicio]
> - Fecha de conclusión: [fecha de fin]
> - Total de horas: [horas] horas
> - Total de meses: [meses] meses
>
> **⚠️ Siguiente paso:** Ingresa al portal y sube la Carta de Liberación del IJUMICH (Paso 7) para concluir tu proceso de Servicio Social.
>
> Puedes descargar tu carta en cualquier momento desde el portal usando el botón del Paso 6.
>
> Atentamente, Coordinación de Servicio Social — Universidad Montrer (UNIMO)

*Referencia técnica — Función `sendCartaConclusionAlumno()`; plantilla `ss_interno_carta_conclusion_alumno`; se dispara desde `generarCartaConclusionServicio.php`.*

---

### Correo No. 11 · Carta de presentación aprobada (Paso 1)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador aprueba la solicitud de carta de presentación (Paso 1).
**Asunto:** ✅ Tu Carta de Presentación de Servicio Social ha sido aprobada
**Datos personalizados:** nombre del alumno, comentarios.

**Mensaje:**

> **✅ Carta de Presentación Aprobada**
>
> Hola [nombre del alumno],
>
> **Tu solicitud de Carta de Presentación ha sido aprobada.**
>
> Puedes descargar tu Carta de Presentación desde el portal (Paso 1) en cualquier momento.
>
> [comentarios]
>
> **Siguiente paso:** Presenta la carta al organismo receptor. Una vez que concluyas tus prácticas, sube la Carta de Finalización (Paso 2).
>
> Atentamente, Coordinación de Servicio Social · UNIMO

*Referencia técnica — Función `sendSsCartaPresentacionAprobada()`; plantilla `ss_interno_carta_presentacion_aprobada`; se dispara al aprobar el Paso 1.*

---

### Correo No. 12 · Carta de prácticas aprobada (Paso 2)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador aprueba la carta de finalización de prácticas (Paso 2). El correo informa que la Carta de Aceptación (Paso 3) se generó automáticamente.
**Asunto:** Tu Carta de Finalización de Prácticas ha sido aprobada — Paso 2
**Datos personalizados:** nombre del alumno, comentarios.

**Mensaje:**

> **✅ Carta de Finalización de Prácticas Aprobada**
>
> Hola [nombre del alumno],
>
> **Tu Carta de Finalización de Prácticas ha sido aprobada.**
>
> [comentarios]
>
> **Siguiente paso:** Tu Carta de Aceptación de Servicio Social (Paso 3) ha sido generada automáticamente. Puedes descargarla desde el portal.
>
> Atentamente, Coordinación de Servicio Social · UNIMO

*Referencia técnica — Función `sendSsCartaPracticasAprobada()`; plantilla `ss_interno_carta_practicas_aprobada`; se dispara al aprobar el Paso 2.*

---

### Correo No. 13 · Solicitud de registro firmada (Paso 4)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador devuelve firmada y sellada la solicitud de registro IJUMICH (Paso 4).
**Asunto:** Tu Solicitud de Registro IJUMICH ha sido firmada y está disponible — Paso 4
**Datos personalizados:** nombre del alumno, comentarios.

**Mensaje:**

> **Solicitud de Registro Firmada**
>
> Hola [nombre del alumno],
>
> **Tu Solicitud de Registro IJUMICH ha sido firmada y sellada por la Coordinación.**
>
> El documento firmado ya está disponible para descarga en el portal (Paso 4).
>
> [comentarios]
>
> **Siguiente paso:** Entrega la Solicitud de Registro firmada al IJUMICH para activar tu Servicio Social. Una vez iniciado, deberás subir tus Reportes Parciales (Paso 5) cada 2 meses.
>
> Atentamente, Coordinación de Servicio Social · UNIMO

*Referencia técnica — Función `sendSsSolicitudRegistroFirmada()`; plantilla `ss_interno_solicitud_registro_firmada`; se dispara al aprobar el Paso 4.*

---

### Correo No. 14 · Reporte parcial aprobado (Paso 5)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador aprueba un reporte parcial (#1, #2 o #3). Incluye una indicación del siguiente paso según el número de reporte.
**Asunto:** ✅ Tu Reporte Parcial #[número de reporte] ha sido aprobado
**Datos personalizados:** nombre del alumno, número de reporte, comentarios, mensaje del siguiente paso.

**Mensaje:**

> **✅ Reporte Parcial #[número de reporte] Aprobado**
>
> Hola [nombre del alumno],
>
> Tu **Reporte Parcial #[número de reporte]** ha sido revisado y aprobado por la Coordinación.
>
> El reporte firmado digitalmente ya está disponible para descarga en el portal.
>
> [comentarios]
>
> [mensaje del siguiente paso]
>
> Atentamente, Coordinación de Servicio Social · UNIMO

> El "mensaje del siguiente paso" cambia según el reporte:
> - Reporte 1: "Siguiente paso: sube tu Reporte Parcial #2 en 2 meses."
> - Reporte 2: "Siguiente paso: sube tu Reporte Parcial #3 en 2 meses."
> - Reporte 3: "¡Todos los reportes aprobados! Ya puedes generar tu Carta de Conclusión (Paso 6)."

*Referencia técnica — Función `sendSsReporteParcialAprobado()`; plantilla `ss_interno_reporte_parcial_aprobado`; se dispara al aprobar el Paso 5.*

---

### Correo No. 15 · Liberación definitiva del Servicio Social (Paso 9)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador aprueba la Evaluación Global (Paso 9); el alumno queda liberado definitivamente del Servicio Social.
**Asunto:** 🎓 ¡Tu Servicio Social ha sido liberado! — UNIMO
**Datos personalizados:** nombre del alumno, nombre del coordinador, fecha de liberación, comentario.

**Mensaje:**

> **🎓 ¡Servicio Social Liberado!**
>
> Estimado/a [nombre del alumno],
>
> Tu **Evaluación Global** fue aprobada y tu Servicio Social ha quedado formalmente acreditado por **Universidad MONTRER**.
>
> Ingresa al portal para descargar tu Evaluación Global firmada y sellada por el área administrativa.
>
> **Responsable:** [nombre del coordinador]
> **Fecha de liberación:** [fecha de liberación]
>
> [comentario]
>
> 🔗 Descargar documento
>
> ¡Felicitaciones! Coordinación de Servicio Social · UNIMO

*Referencia técnica — Función `sendSsEvaluacionGlobalAlumno()`; plantilla `ss_interno_evaluacion_global_alumno`; se dispara al aprobar el Paso 9.*

---

### Correo No. 16 · Documento rechazado (requiere correcciones)

**Destinatario:** Alumno
**Cuándo se envía:** Cuando el administrador rechaza cualquier paso o documento del Servicio Social Interno, indicando el tipo de documento y los comentarios.
**Asunto:** ❌ Tu documento de Servicio Social requiere correcciones — [tipo de documento]
**Datos personalizados:** nombre del alumno, tipo de documento, nombre del coordinador, comentario.

**Mensaje:**

> **❌ Documento Rechazado — Correcciones Requeridas**
>
> Hola [nombre del alumno],
>
> Tu documento *[tipo de documento]* ha sido revisado y requiere correcciones.
>
> El coordinador [nombre del coordinador] dejó el siguiente comentario:
>
> [comentario]
>
> **Acción requerida:** Corrige el documento y vuelve a subirlo desde el portal para continuar con tu proceso de Servicio Social.
>
> Si tienes dudas, comunícate directamente con la Coordinación.
>
> Atentamente, Coordinación de Servicio Social · UNIMO

*Referencia técnica — Función `sendSsSolicitudRechazada()`; plantilla `ss_interno_solicitud_rechazada`; se dispara al rechazar cualquier paso.*

---

### Correo No. 17 · Servicio Social liberado (Paso 7) · ⚠️ Hoy no se envía

**Destinatario:** Alumno
**Cuándo se envía:** Estaba previsto para felicitar al alumno cuando se aprueba la Carta de Liberación IJUMICH (Paso 7). **Actualmente este correo no se envía**, porque el paso que lo dispararía quedó sin activar. La plantilla existe y está lista.
**Asunto:** ¡Tu Servicio Social ha sido liberado! — Universidad Montrer (UNIMO)
**Datos personalizados:** nombre del alumno, nombre del coordinador, folio, fecha de liberación, comentario.

**Mensaje:**

> **¡Servicio Social Liberado!**
>
> Hola [nombre del alumno],
>
> **¡Felicidades! Tu Servicio Social ha sido liberado oficialmente.**
>
> El coordinador [nombre del coordinador] ha aprobado tu Carta de Liberación del IJUMICH, completando todos los requisitos de tu Servicio Social.
>
> - Folio de conclusión: [folio]
> - Fecha de liberación: [fecha de liberación]
> - Aprobado por: [nombre del coordinador]
>
> Guarda este correo como comprobante. Si necesitas documentación oficial, comunícate con la Coordinación.
>
> ¡Muchas felicidades! Atentamente, Coordinación de Servicio Social · UNIMO

*Referencia técnica — Función `sendSsLiberacionCompleta()` (definida pero no invocada; el paso correspondiente quedó vacío); plantilla `ss_interno_liberacion_completa`.*

---

## Índice de correos a coordinadores y administradores

| N.º | Correo | Cuándo se envía |
|---|---|---|
| 1 | Datos de acceso de cuenta nueva | Cuando se crea un usuario administrativo |
| 2 | Solicitud de ingreso a evento | Cuando un alumno se inscribe a un evento |
| 3 | Solicitud de carta de presentación (Paso 1) | Cuando el alumno solicita el Paso 1 |
| 4 | Carga de carta de prácticas (Paso 2) | Cuando el alumno sube el Paso 2 |
| 5 | Carga de solicitud de registro (Paso 4) | Cuando el alumno sube el Paso 4 |
| 6 | Carga de reporte parcial (Paso 5) | Cuando el alumno sube un reporte parcial |
| 7 | Carga de carta de liberación (Paso 7) | Cuando el alumno sube el Paso 7 |
| 8 | Carga de Evaluación de la Unidad (Paso 8) | Cuando el alumno sube el Paso 8 |
| 9 | Carga de Evaluación Global (Paso 9) | Cuando el alumno sube el Paso 9 |
| 10 | Carta de conclusión generada (Paso 6) | Cuando el alumno genera su carta de conclusión |

---

# 2. Correos dirigidos a coordinadores y administradores

### Correo No. 1 · Datos de acceso de cuenta nueva

**Destinatario:** Administrador / Coordinador (usuario administrativo recién creado)
**Cuándo se envía:** Cuando se registra un nuevo usuario administrativo. *(Aplica a personal de ambos módulos; el asunto está rotulado para Servicio Social.)*
**Asunto:** Bienvenido a Servicio Social UNIMO - Tus datos de acceso
**Datos personalizados:** nombre, rol, correo, contraseña, enlace de acceso.

**Mensaje:**

> Hola [nombre],
>
> Se ha creado una nueva cuenta en el sistema de **Servicio Social UNIMO**.
>
> **Rol asignado:** [rol]
> **Email:** [correo]
> **Contraseña:** [contraseña]
>
> Puedes iniciar sesión directamente haciendo clic en el siguiente enlace.
>
> 🔗 Iniciar sesión: [enlace de acceso]
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendDataToNewUser()`; plantilla `new_user_data`; se dispara desde `ctrRegisterUser()`.*

---

### Correo No. 2 · Solicitud de ingreso a evento

**Destinatario:** Coordinador / Organizador del evento (quien creó el evento)
**Cuándo se envía:** Cuando un alumno se inscribe (postula) a un evento.
**Asunto:** Confirmación de solicitud de evento - UNIMO
**Datos personalizados:** nombre del organizador, nombre del alumno, correo del alumno, nombre del evento, fecha del evento.

**Mensaje:**

> Hola [nombre del organizador],
>
> Se ha recibido una nueva solicitud de participación para tu evento.
>
> **Alumno:** [nombre del alumno] ([correo del alumno])
> **Evento:** [nombre del evento]
> **Fecha del evento:** [fecha del evento]
>
> Por favor revisa la información y gestiona la asistencia desde el portal.
>
> Saludos cordiales, Equipo UNIMO

*Referencia técnica — Función `sendEventApplicationReceived()`; plantilla `student_event_application`; se dispara desde `ctrApplyEvent()`.*

---

### Correo No. 3 · Solicitud de carta de presentación (Paso 1)

**Destinatario:** Administrador de Servicio Social
**Cuándo se envía:** Cuando el alumno solicita su carta de presentación IJUMICH (Paso 1). Se avisa al administrador con los datos del organismo y responsable.
**Asunto:** [SS] [nombre del alumno] solicita Carta de Presentación — Organismo: [nombre del organismo]
**Datos personalizados:** nombre del alumno, correo del alumno, matrícula, nombre del organismo, responsable, fecha de envío.

**Mensaje:**

> **Paso 1 — Solicitud de Carta de Presentación**
>
> Coordinación,
>
> El alumno **[nombre del alumno]** ha enviado su solicitud de Carta de Presentación de Servicio Social el [fecha de envío].
>
> Datos del alumno — Nombre: [nombre del alumno] · Correo: [correo del alumno] · Matrícula: [matrícula]
> Datos del organismo receptor — Organismo: [nombre del organismo] · Responsable: [responsable]
>
> Ingresa al panel de administración para revisar y aprobar la solicitud.

*Referencia técnica — Función `sendSsCartaPresentacionAdmin()`; plantilla `ss_interno_carta_presentacion_admin`; se dispara al solicitar la carta de presentación.*

---

### Correo No. 4 · Carga de carta de prácticas (Paso 2)

**Destinatario:** Administrador de Servicio Social
**Cuándo se envía:** Cuando el alumno sube su carta de finalización de prácticas (Paso 2).
**Asunto:** [SS] [nombre del alumno] subió su Carta de Finalización de Prácticas — Paso 2
**Datos personalizados:** nombre del alumno, correo del alumno, nombre del archivo, fecha de envío.

**Mensaje:**

> **Paso 2 — Carta de Finalización de Prácticas**
>
> Coordinación,
>
> El alumno **[nombre del alumno]** ha subido su Carta de Finalización de Prácticas el [fecha de envío].
>
> Nombre: [nombre del alumno] · Correo: [correo del alumno] · Archivo: [nombre del archivo]
>
> Ingresa al panel de administración para revisar y aprobar el documento.

*Referencia técnica — Función `sendSsCartaPracticasAdmin()`; plantilla `ss_interno_carta_practicas_admin`; se dispara al subir el Paso 2.*

---

### Correo No. 5 · Carga de solicitud de registro IJUMICH (Paso 4)

**Destinatario:** Administrador de Servicio Social
**Cuándo se envía:** Cuando el alumno sube su solicitud de registro IJUMICH (Paso 4). El administrador debe firmarla y sellarla.
**Asunto:** [SS] [nombre del alumno] subió su Solicitud de Registro IJUMICH — Paso 4
**Datos personalizados:** nombre del alumno, correo del alumno, nombre del archivo, fecha de envío.

**Mensaje:**

> **Paso 4 — Solicitud de Registro IJUMICH**
>
> Coordinación,
>
> El alumno **[nombre del alumno]** ha subido su Solicitud de Registro IJUMICH el [fecha de envío].
>
> Nombre: [nombre del alumno] · Correo: [correo del alumno] · Archivo: [nombre del archivo]
>
> **⚠️ Acción requerida:** Descarga el PDF, fírmalo y séllalo, luego sube el documento firmado desde el panel de administración para que el alumno pueda descargarlo.

*Referencia técnica — Función `sendSsSolicitudRegistroAdmin()`; plantilla `ss_interno_solicitud_registro_admin`; se dispara al subir el Paso 4.*

---

### Correo No. 6 · Carga de reporte parcial (Paso 5)

**Destinatario:** Administrador de Servicio Social
**Cuándo se envía:** Cuando el alumno sube un reporte parcial (#1, #2 o #3).
**Asunto:** [SS] [nombre del alumno] subió su Reporte Parcial #[número de reporte] — Paso 5
**Datos personalizados:** nombre del alumno, correo del alumno, número de reporte, nombre del archivo, fecha de envío.

**Mensaje:**

> **Paso 5 — Reporte Parcial de Servicio Social**
>
> Coordinación,
>
> El alumno **[nombre del alumno]** ha subido su Reporte Parcial #[número de reporte] el [fecha de envío].
>
> Nombre: [nombre del alumno] · Correo: [correo del alumno] · Reporte: Parcial #[número de reporte] · Archivo: [nombre del archivo]
>
> Ingresa al panel de administración para revisar el reporte y firmarlo digitalmente.

*Referencia técnica — Función `sendSsReporteParcialAdmin()`; plantilla `ss_interno_reporte_parcial_admin`; se dispara al subir el Paso 5.*

---

### Correo No. 7 · Carga de carta de liberación (Paso 7)

**Destinatario:** Administrador de Servicio Social
**Cuándo se envía:** Cuando el alumno sube su carta de liberación IJUMICH (Paso 7). Su aprobación completa el Servicio Social.
**Asunto:** [SS] [nombre del alumno] subió su Carta de Liberación IJUMICH — Paso 7
**Datos personalizados:** nombre del alumno, correo del alumno, nombre del archivo, fecha de envío.

**Mensaje:**

> **Paso 7 — Carta de Liberación IJUMICH**
>
> Coordinación,
>
> El alumno **[nombre del alumno]** ha subido su Carta de Liberación del IJUMICH el [fecha de envío].
>
> Nombre: [nombre del alumno] · Correo: [correo del alumno] · Archivo: [nombre del archivo]
>
> **✅ Último paso:** Si apruebas este documento, el alumno completará su Servicio Social (Paso 8). Se le enviará notificación automática de liberación.

*Referencia técnica — Función `sendSsCartaLiberacionAdmin()`; plantilla `ss_interno_carta_liberacion_admin`; se dispara al subir el Paso 7.*

---

### Correo No. 8 · Carga de Evaluación de la Unidad Productiva (Paso 8)

**Destinatario:** Administrador de Servicio Social
**Cuándo se envía:** Cuando el alumno sube la Evaluación de la Unidad Productiva (Paso 8).
**Asunto:** [SS] [nombre del alumno] subió su Evaluación de la Unidad Productiva
**Datos personalizados:** nombre del alumno, correo del alumno, matrícula, nombre del archivo, fecha de envío.

**Mensaje:**

> **📋 Evaluación de la Unidad Productiva — Pendiente**
>
> Estimado coordinador,
>
> ⚠️ El alumno **[nombre del alumno]** ha subido su Evaluación de la Unidad Productiva y requiere tu revisión para continuar con la liberación del Servicio Social.
>
> Datos del alumno — Nombre: [nombre del alumno] · Correo: [correo del alumno] · Matrícula: [matrícula] · Archivo: [nombre del archivo] · Fecha de envío: [fecha de envío]
>
> Ingresa al panel administrativo para revisar el documento, completar el llenado automático de campos, colocar firma y sello institucional, y aprobar o rechazar el paso.
>
> 🔗 Ir al panel admin
>
> Atentamente, Sistema de Gestión SS/PP · UNIMO

*Referencia técnica — Función `sendSsEvaluacionUnidadProductivaAdmin()`; plantilla `ss_interno_evaluacion_unidad_admin`; se dispara al subir el Paso 8.*

---

### Correo No. 9 · Carga de Evaluación Global (Paso 9)

**Destinatario:** Administrador de Servicio Social
**Cuándo se envía:** Cuando el alumno sube la Evaluación Global (Paso 9, último antes de la liberación definitiva).
**Asunto:** [SS] [nombre del alumno] subió su Evaluación Global
**Datos personalizados:** nombre del alumno, correo del alumno, matrícula, nombre del archivo, fecha de envío.

**Mensaje:**

> **🌐 Evaluación Global — Pendiente**
>
> Estimado coordinador,
>
> ⚠️ El alumno **[nombre del alumno]** ha subido su Evaluación Global (último paso antes de la liberación definitiva).
>
> Datos del alumno — Nombre: [nombre del alumno] · Correo: [correo del alumno] · Matrícula: [matrícula] · Archivo: [nombre del archivo] · Fecha de envío: [fecha de envío]
>
> Ingresa al panel administrativo para revisar, firmar y sellar la Evaluación Global. Al aprobarla, el alumno quedará formalmente liberado de Servicio Social.
>
> 🔗 Ir al panel admin
>
> Atentamente, Sistema de Gestión SS/PP · UNIMO

*Referencia técnica — Función `sendSsEvaluacionGlobalAdmin()`; plantilla `ss_interno_evaluacion_global_admin`; se dispara al subir el Paso 9.*

---

### Correo No. 10 · Carta de conclusión generada (aviso al administrador · Paso 6)

**Destinatario:** Administrador de Servicio Social
**Cuándo se envía:** Cuando el alumno genera su carta de conclusión (Paso 6).
**Asunto:** [SS] [nombre del alumno] generó su Carta de Conclusión — Folio [folio]
**Datos personalizados:** nombre del alumno, correo del alumno, folio, fecha de inicio, fecha de fin, horas, fecha de generación.

**Mensaje:**

> **Paso 6 — Carta de Conclusión Generada**
>
> Coordinación,
>
> El alumno **[nombre del alumno]** generó su Carta de Conclusión de Servicio Social el [fecha de generación].
>
> Datos — Alumno: [nombre del alumno] · Correo: [correo del alumno] · Folio: [folio] · Periodo: [fecha de inicio] — [fecha de fin] · Horas: [horas] hrs
>
> El alumno avanza al Paso 7: Carga de Carta de Liberación IJUMICH. No se requiere acción por su parte en este momento.

*Referencia técnica — Función `sendSsCartaConclusionAdmin()`; plantilla `ss_interno_carta_conclusion_admin`; se dispara desde `generarCartaConclusionServicio.php`.*

---

# 3. Plantillas que existen pero hoy no se envían

### Adicional 1 · Horas de Servicio Social completadas (aviso al alumno) · Sin uso

**Destinatario:** Alumno
**Situación:** Estaba prevista para avisar al alumno (mediante un proceso automático) que completó todas sus horas. **No hay un proceso que la envíe**; solo aparece en una herramienta interna de pruebas. La plantilla existe y está lista.
**Asunto:** ✅ Has completado tus [horas completadas] horas de Servicio Social — UNIMO
**Datos personalizados:** nombre del alumno, horas completadas, horas requeridas, fecha de detección.

**Mensaje:**

> **✅ ¡Horas de Servicio Social Completadas!**
>
> Hola [nombre del alumno],
>
> Has acumulado **[horas completadas] de [horas requeridas] horas** de Servicio Social.
>
> Nuestro sistema detectó el [fecha de detección] que has completado todas las horas requeridas.
>
> **Próximos pasos para finalizar:**
> - Genera tu Carta de Conclusión (Paso 6) desde el portal.
> - Sube la Carta de Liberación del IJUMICH (Paso 7).
>
> Atentamente, Coordinación de Servicio Social · UNIMO

*Referencia técnica — Plantilla `servicio_completado`; sin función de envío.*

---

## Observaciones y recomendaciones

- **Total de correos en uso:** 27 (17 a alumnos, 10 a coordinadores/administradores).
- **Plantillas que existen pero no se envían:** 1 (aviso de horas completadas, sin proceso que lo dispare).
- **Punto de mejora detectado (importante):** al aprobar el **Paso 7 (Carta de Liberación)** y el **Paso 8 (Evaluación de la Unidad Productiva)**, el sistema **no envía un correo de aprobación al alumno**. El alumno sí es notificado en los pasos 1, 2, 4, 5 y 9, pero no en estos dos. Se recomienda activarlos para no dejar al alumno sin confirmación.
- **Punto de mejora detectado:** el correo "Servicio Social liberado (Paso 7)" (No. 17 de alumnos) está listo pero no se envía; conviene decidir si se activa.
- **Detalle menor:** el correo de baja de Servicio Social tiene un campo de "motivo" que, en la práctica, no siempre se completa con el motivo real.
- **Dato verificable:** todos los textos de este reporte provienen de las plantillas reales almacenadas en el sistema; no se inventó ningún contenido.
