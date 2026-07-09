export class Utils {
  /* ---------- Fechas ---------- */
  static formatDate(dateStr) {
    if (!dateStr) return "–";
    const months = ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"];
    const [date,time] = dateStr.split(" ");
    if (!date || !time) return dateStr;

    const [y,m,d]     = date.split("-");
    const [hh,mm]     = time.split(":");
    let hour          = Number(hh);
    const ampm        = hour >= 12 ? "pm" : "am";
    hour              = hour % 12 || 12;
    return `${Number(d)}/${months[Number(m)-1]}/${y} ${hour}:${mm} ${ampm}`;
  }

  static formatDateSpanish(dateStr) {
    if (!dateStr) return "–";
    const months = ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"];
    const [y,m,d] = dateStr.split("-");
    return `${d} de ${months[Number(m)-1]} del ${y}`;
  }

  /* ---------- Horas ---------- */
  static calculateWorkingHours(startTime, endTime) {
    if (!startTime || !endTime) return 0;
    const [h1,m1] = startTime.split(":").map(Number);
    const [h2,m2] = endTime   .split(":").map(Number);
    const start   = new Date(0,0,0,h1,m1,0);
    const end     = new Date(0,0,0,h2,m2,0);
    const diffMs  = end - start;
    return Math.max(0, diffMs / 3_600_000); // ms→h
  }

  static formatWorkingHours(hours) {
    const h = Math.floor(hours);
    const m = Math.floor((hours - h) * 60);
    return `${h}:${m.toString().padStart(2,"0")}`;
  }

  /* ---------- Misceláneos ---------- */
  static debounce(fn, delay) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this,args), delay);
    };
  }

  static showMessage($container, msg, type="info") {
    $container.html(`<div class="alert alert-${type}">${msg}</div>`);
  }

  static createField(label,value) {
    return `<dt class="col-sm-4">${label}</dt><dd class="col-sm-8">${value||"–"}</dd>`;
  }

  static escape(str) {
    if (str == null) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }
}
