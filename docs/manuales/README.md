# Manuales de la plataforma

Manuales en PDF para cada perfil de **Prácticas Profesionales** (Servicio Social: pendiente).

## Qué hay aquí

| Archivo | Para quién | Contenido |
|---|---|---|
| `practicas/manual_empresa.pdf` | Empresa (Organismo Externo) | 25 pasos: registro, convenio, vacantes, selección, asistencias, reportes, incidencias y capacitaciones |
| `practicas/manual_alumno.pdf` | Alumno de Prácticas | 17 pasos: inscripción; prácticas en una empresa o en un área interna |
| `practicas/manual_encargado_area.pdf` | Encargado de área interna | 6 pasos: postulaciones, asistencias, avance, reportes y evaluaciones |
| `practicas/manual_coordinacion.pdf` | Coordinación de Prácticas | 17 pasos: tablero, revisiones, reportes, áreas internas, incidencias y capacitaciones |
| `practicas/tutorial_completo.pdf` | Coordinación | Las 10 partes (62 pasos) con todos los perfiles |
| `fuentes/practicas/` | Mantenimiento | Textos de cada parte, definición de los manuales y scripts para regenerarlos |

## Cómo los abre cada quien

- La carpeta `docs/manuales/` **no se puede abrir por URL**: la bloquean la regla `Protect Manuales` de `web.config` (producción, IIS) y `docs/manuales/.htaccess` (Apache local).
- `controller/manuales.php` entrega el PDF solo con sesión: cada perfil recibe el suyo según `config/manuales.php` y el rol `admin` (Coordinación) puede abrir todos.
- La empresa, el alumno de prácticas y el encargado tienen el botón **«Manual»** en su barra superior (`view/pages/navs/sidebar.php`).
- La Coordinación tiene la página **«Manuales»** en su menú (`view/pages/manuales.php`).
- Para agregar un manual: copiar el PDF a `docs/manuales/...` y darlo de alta en `config/manuales.php` con sus roles.

## De dónde salen

1. **Partes con capturas (Word).** Las 10 partes del tutorial están en `C:\Users\Desarrollo\Desktop\SP\PRÁCTICAS PROFESIONALES PASO N.docx`. **No van al repositorio**: son ~25 MB de capturas y cada cambio duplicaría el historial. Hay que respaldarlas en otro lugar, como OneDrive o una carpeta compartida.
2. **Textos de cada parte** en `fuentes/practicas/partes/pN.txt`. Hay un renglón por elemento, con este formato:
   - `H1|` es el título de la parte y `H|` el de un paso (`Paso N · ...`).
   - `Q|` es la línea «Lo hace: ...» y `T|` un texto antes de una captura.
   - `B|` es una viñeta y `F|` un texto final o de introducción.
   - `I|imageN.png` es una captura, con el nombre que tiene dentro del Word.
   - Los textos de pantalla van entre «comillas latinas» y salen en negritas.
3. **Manuales por perfil** en `fuentes/practicas/manuales.json`. Define qué pasos del tutorial lleva cada manual, en qué partes se agrupan y los textos de portada, introducción y cierre.
4. **Tutorial completo** en `fuentes/practicas/tutorial.json`, con la portada, la nota del índice y los títulos de las 10 partes.

Los scripts (`fuentes/practicas/scripts/`) se corren en Windows con Word instalado, en PowerShell 5.1:

| Script | Qué hace |
|---|---|
| `armar_parte.ps1 -Parte N` | Vuelve a dar formato a `PASO N.docx` con `partes/pN.txt` (respalda antes el Word) |
| `auditar.ps1` | Revisa el formato de las 10 partes: estilos, numeración, capturas centradas y con borde, renglones vacíos |
| `generar_tutorial.ps1` | Une las 10 partes en el tutorial completo (Word y PDF) y copia el PDF aquí |
| `generar_manuales.ps1 [-Id empresa\|alumno\|encargado\|coordinacion]` | Genera los manuales por perfil (Word y PDF en `Desktop\SP\Manuales por perfil`) y copia los PDF aquí |

El resto (`unir`, `saltos`, `fin_indice`, `ajuste_parte1`, `vacios`, `hojas`, `pdf`, `correr`, `proceso_word`, `armar_seccion`) son pasos internos de esos cuatro.

## Cuando algo cambia en la plataforma

Si un cambio de código modifica una pantalla, un texto o un correo que sale en los manuales:

1. Ubicar los pasos afectados en la tabla de abajo y en `partes/pN.txt`.
2. Volver a tomar las capturas en el entorno local de pruebas (`servicio_social_PRUEBAS`, correos en Mailpit), con datos ficticios:
   - empresa «Empresa Ejemplo Morelia»;
   - alumnos Carlos Ramírez López (99000001, empresa) y Ana Sofía Torres Méndez (99000002, área interna);
   - `encargado.pruebas@example.com` y `coordinacion.pp@example.com`.
3. Reemplazarlas en `PASO N.docx` y, si cambió algún texto, actualizar `partes/pN.txt`.
4. Correr `armar_parte.ps1 -Parte N`, después `auditar.ps1`, `generar_tutorial.ps1` y `generar_manuales.ps1`.
5. Subir en el mismo commit el cambio de código, los `pN.txt` y los PDF regenerados.

## Pantallas que aparecen en cada parte

| Parte (pasos del tutorial completo) | Pantallas y archivos principales |
|---|---|
| 1 · La empresa se registra (1–7) | `view/pages/login.php` («Regístrate»), `view/pages/practicas/RegisterEmpresas.php`, `view/assets/css/registro-unimo.css` |
| 2 · Revisión del registro, convenio y acceso (8–11) | `view/pages/servicio/dashboardAdministrador.php`, `view/pages/practicas/firmar_convenio.php`, correos de convenio y de accesos |
| 3 · Vacantes e inscripción del alumno (12–19) | `view/pages/practicas/dashboardOrganismo.php` (tablero y vacante), `dashboardAdministrador.php` (revisión de vacantes y alumnos), `view/pages/practicas/RegisterPracticas.php` |
| 4 · Postulación, entrevista y aceptación (20–24) | `view/pages/practicas/dashboardStudent.php` + `view/assets/js/student/practicesApp.js`, `dashboardOrganismo.php` (candidatos, entrevista, aceptación) |
| 5 · Asistencias y avisos (25–27) | `dashboardStudent.php` / `practicesApp.js` / `main.js` («Pasar asistencia»), `dashboardOrganismo.php` («Control de Asistencias»), avisos por horas |
| 6 · Reporte parcial (28–31) | `dashboardStudent.php`, `view/pages/practicas/modalEvaluacionIntegral.php`, `dashboardOrganismo.php` (reportes), `dashboardAdministrador.php` («Reportes de Prácticas») |
| 7 · Reporte final y constancia (32–38) | Lo de la parte 6, `controller/practices/constanciaHtml.php`, `internship_students.php`, `internship_companies.php` («Evaluaciones Integrales») |
| 8 · Seguimiento de la Coordinación (39–47) | `dashboardAdministrador.php` (tablero, reportes, incidencias, capacitaciones), `internship_students.php`, `internship_companies.php`, `internship_areas.php`, `dashboardOrganismo.php` (incidencia, capacitación) |
| 9 · Áreas internas: postulación y asistencias (48–53) | `practicesApp.js` (áreas), `view/pages/servicio/dashboardProfesor.php` + `view/assets/js/organismo/teacher_dashboard.js` |
| 10 · Áreas internas: reportes y constancia (54–62) | `practicesApp.js`, `modalEvaluacionIntegral.php`, `dashboardProfesor.php`, `view/pages/practicas/modalevaluaciones.php`, `dashboardAdministrador.php`, `internship_areas.php`, constancia |

Los correos salen de las plantillas en Configuraciones → Plantillas (`email_templates`) y de `controller/emails.php`.
