export const CONFIG = {
  HORAS_TOTALES: 360,
  PRIMER_PARCIAL: 180,
  SEGUNDO_PARCIAL: 360,
  ENDPOINTS: {
    STUDENTS: "controller/practices/students.php",
    GENERATE_LETTER: "controller/practices/generarCartaPresentacion.php"
  },
  SELECTORS: {
    SOLICITUDES: "#student-main-container",
    SEARCH: "#searchPractices",
    SEARCHBAR: ".searchTab",
    PROGRAMA: "#programa_academico",
    APPLY_BTN: ".apply-practice",
    GENERATE_LETTER_BTN: ".generate-letter",
    NEW_ATTENDANCE_BTN: "#btn-nueva-asistencia"
  },
  MESSAGES: {
    NO_REQUESTS: "No hay solicitudes registradas.",
    LOAD_ERROR: "Error al cargar solicitudes.",
    NO_ATTENDANCE: "No hay asistencias registradas."
  }
};
