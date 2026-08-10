/**
 * reportes.js — Bloque "Reportes" del panel del administrador.
 *
 * Primer reporte disponible: Prácticas Profesionales.
 * Consume `controller/ajax/ajax.forms.php` con search: 'reportes' y arma el
 * listado en tres pasos guiados: periodo → empresa → estado de la práctica.
 * La descarga en Excel reutiliza exactamente los mismos filtros.
 */
(function () {
  "use strict";

  const ENDPOINT = "controller/ajax/ajax.forms.php";
  const EXPORT_URL = "controller/practices/export_reporte_practicas_excel.php";

  const ESTADOS = {
    en_proceso: { label: "En proceso", color: "#d97706", bg: "#fffbeb", icon: "fas fa-spinner" },
    concluida: { label: "Concluida", color: "#01643D", bg: "#f0fdf4", icon: "fas fa-circle-check" },
    baja: { label: "Baja por strikes", color: "#dc2626", bg: "#fef2f2", icon: "fas fa-user-slash" },
  };

  const KPIS = [
    { key: "", label: "Todas", icon: "fas fa-layer-group", color: "#0f172a", bg: "#f1f5f9", campo: "total" },
    { key: "en_proceso", label: "En proceso", icon: "fas fa-spinner", color: "#d97706", bg: "#fffbeb", campo: "en_proceso" },
    { key: "concluida", label: "Concluidas", icon: "fas fa-circle-check", color: "#01643D", bg: "#f0fdf4", campo: "concluida" },
    { key: "baja", label: "Bajas", icon: "fas fa-user-slash", color: "#dc2626", bg: "#fef2f2", campo: "baja" },
  ];

  const state = {
    items: [],
    resumen: { total: 0, en_proceso: 0, concluida: 0, baja: 0, horas: 0, alumnos: 0, empresas: 0 },
    filtro: { desde: "", hasta: "", campo_fecha: "inicio", empresa: "", estado: "", q: "" },
    cargando: false,
  };

  let debounceId = null;

  /* ── Utilidades ─────────────────────────────────────────────────────── */

  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c])
    );
  }

  function fmtFecha(f) {
    if (!f) return "—";
    const d = new Date(String(f).replace(" ", "T"));
    if (isNaN(d)) return String(f);
    return d.toLocaleDateString("es-MX", { day: "2-digit", month: "short", year: "numeric" });
  }

  function fmtNum(n) {
    return Number(n || 0).toLocaleString("es-MX", { maximumFractionDigits: 2 });
  }

  /** Fecha local en formato YYYY-MM-DD (sin corrimiento por zona horaria). */
  function iso(d) {
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${d.getFullYear()}-${mm}-${dd}`;
  }

  /* ── Carga de datos ─────────────────────────────────────────────────── */

  function cargarEmpresas() {
    $.ajax({
      url: ENDPOINT,
      method: "POST",
      data: { search: "reportes", action: "getCatalogoEmpresas" },
      dataType: "json",
    })
      .done(function (cat) {
        const $sel = $("#repEmpresa");
        const previo = $sel.val();
        $sel.empty().append('<option value="">Todas las empresas y áreas</option>');

        const grupo = (titulo, lista) => {
          if (!lista || !lista.length) return;
          const $g = $("<optgroup>").attr("label", titulo);
          lista.forEach((e) => {
            $g.append(
              $("<option>")
                .val(e.empresa_key)
                .text(`${e.label} (${e.total} ${e.total === 1 ? "alumno" : "alumnos"})`)
            );
          });
          $sel.append($g);
        };

        grupo("Empresas y organismos externos", cat.externas);
        grupo("Áreas internas de la universidad", cat.internas);

        if (previo) $sel.val(previo);
      })
      .fail(function () {
        $("#repEmpresa").html('<option value="">No se pudo cargar el listado de empresas</option>');
      });
  }

  function cargar() {
    state.cargando = true;
    renderTabla();

    $.ajax({
      url: ENDPOINT,
      method: "POST",
      data: {
        search: "reportes",
        action: "getReportePracticas",
        desde: state.filtro.desde,
        hasta: state.filtro.hasta,
        campo_fecha: state.filtro.campo_fecha,
        empresa: state.filtro.empresa,
        q: state.filtro.q,
        // El estado se filtra en pantalla para que los indicadores sigan
        // mostrando el total del periodo elegido.
        estado: "",
      },
      dataType: "json",
    })
      .done(function (res) {
        state.cargando = false;
        if (!res || res.success === false) {
          state.items = [];
          state.resumen = { total: 0, en_proceso: 0, concluida: 0, baja: 0, horas: 0, alumnos: 0, empresas: 0 };
          Swal.fire({
            icon: "error",
            title: "No se pudo generar el reporte",
            text: (res && res.message) || "Intenta de nuevo en unos segundos.",
            confirmButtonColor: "#01643D",
          });
        } else {
          state.items = res.items || [];
          state.resumen = res.resumen || state.resumen;
        }
        renderKpis();
        renderTabla();
      })
      .fail(function () {
        state.cargando = false;
        state.items = [];
        renderKpis();
        renderTabla();
        Swal.fire({
          icon: "error",
          title: "Error de conexión",
          text: "No fue posible obtener la información del reporte.",
          confirmButtonColor: "#01643D",
        });
      });
  }

  function cargarConEspera() {
    clearTimeout(debounceId);
    debounceId = setTimeout(cargar, 350);
  }

  /* ── Render ─────────────────────────────────────────────────────────── */

  function visibles() {
    if (!state.filtro.estado) return state.items;
    return state.items.filter((i) => i.estado === state.filtro.estado);
  }

  function renderKpis() {
    const html = KPIS.map((k) => {
      const activo = state.filtro.estado === k.key ? " active" : "";
      return `
        <button type="button" class="rep-kpi${activo}" data-estado="${k.key}"
                style="--kc:${k.color};--kbg:${k.bg};">
          <span class="rep-kpi-lbl"><i class="${k.icon}"></i> ${esc(k.label)}</span>
          <span class="rep-kpi-val">${fmtNum(state.resumen[k.campo] || 0)}</span>
        </button>`;
    }).join("");
    $("#repKpis").html(html);
  }

  function badgeEstado(estado) {
    const e = ESTADOS[estado] || { label: estado, color: "#475569", bg: "#f1f5f9", icon: "fas fa-circle" };
    return `<span class="rep-badge" style="background:${e.bg};color:${e.color};"><i class="${e.icon}"></i>${esc(e.label)}</span>`;
  }

  function renderTabla() {
    const $wrap = $("#repTabla");

    if (state.cargando) {
      $wrap.html('<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><span>Generando el reporte…</span></div>');
      $("#repResumenBar").empty();
      return;
    }

    const rows = visibles();

    $("#repResumenBar").html(`
      <div class="rep-resumen">
        <span><i class="fas fa-list-ol"></i> <b>${fmtNum(rows.length)}</b> ${rows.length === 1 ? "registro" : "registros"} en pantalla</span>
        <span><i class="fas fa-user-graduate"></i> <b>${fmtNum(state.resumen.alumnos)}</b> ${state.resumen.alumnos === 1 ? "alumno" : "alumnos"} en el periodo</span>
        <span><i class="fas fa-building"></i> <b>${fmtNum(state.resumen.empresas)}</b> ${state.resumen.empresas === 1 ? "empresa o área" : "empresas y áreas"}</span>
        <span><i class="fas fa-clock"></i> <b>${fmtNum(state.resumen.horas)}</b> horas acreditadas</span>
      </div>`);

    if (!rows.length) {
      $wrap.html(`
        <div class="empty-state">
          <i class="fa-regular fa-folder-open"></i>
          <span>No hay alumnos que cumplan con los filtros elegidos.</span>
          <small class="text-muted">Prueba ampliando el rango de fechas o seleccionando "Todas las empresas".</small>
        </div>`);
      return;
    }

    const cuerpo = rows
      .map(
        (r) => `
        <tr>
          <td>
            <div class="rep-alumno">${esc(r.nombre_completo)}</div>
            <div class="rep-sub">Matrícula ${esc(r.matricula)} · ${esc(r.programa_academico || "Sin programa")}</div>
          </td>
          <td>
            <div class="rep-empresa">${esc(r.empresa)}</div>
            <div class="rep-sub">${r.origen === "interna" ? "Área interna" : "Organismo externo"} · ${esc(r.modalidad || "")}</div>
          </td>
          <td class="text-nowrap">${fmtFecha(r.fecha_inicio)}</td>
          <td class="text-nowrap">${fmtFecha(r.fecha_fin)}</td>
          <td class="text-center fw-bold">${fmtNum(r.horas)}</td>
          <td class="text-center">${fmtNum(r.dias)}</td>
          <td>${badgeEstado(r.estado)}</td>
        </tr>`
      )
      .join("");

    $wrap.html(`
      <div class="rep-tabla-wrap">
        <table class="rep-tabla">
          <thead>
            <tr>
              <th>Alumno</th>
              <th>Empresa / Área</th>
              <th>Inicio</th>
              <th>Conclusión</th>
              <th class="text-center">Horas</th>
              <th class="text-center">Días</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>${cuerpo}</tbody>
        </table>
      </div>`);
  }

  /* ── Rangos rápidos ─────────────────────────────────────────────────── */

  function aplicarRango(rango) {
    const hoy = new Date();
    let desde = "";
    let hasta = "";

    if (rango === "mes") {
      desde = iso(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
      hasta = iso(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0));
    } else if (rango === "trimestre") {
      desde = iso(new Date(hoy.getFullYear(), hoy.getMonth() - 2, 1));
      hasta = iso(hoy);
    } else if (rango === "anio") {
      desde = iso(new Date(hoy.getFullYear(), 0, 1));
      hasta = iso(new Date(hoy.getFullYear(), 11, 31));
    }

    state.filtro.desde = desde;
    state.filtro.hasta = hasta;
    $("#repDesde").val(desde);
    $("#repHasta").val(hasta);
    $("#repRangos .rep-quick-btn").removeClass("active");
    $(`#repRangos .rep-quick-btn[data-rango="${rango}"]`).addClass("active");
    cargar();
  }

  function marcarRangoManual() {
    $("#repRangos .rep-quick-btn").removeClass("active");
    const sinFechas = !state.filtro.desde && !state.filtro.hasta;
    if (sinFechas) $('#repRangos .rep-quick-btn[data-rango="todo"]').addClass("active");
  }

  /* ── Exportación ────────────────────────────────────────────────────── */

  function urlExcel() {
    const p = $.param({
      desde: state.filtro.desde,
      hasta: state.filtro.hasta,
      campo_fecha: state.filtro.campo_fecha,
      empresa: state.filtro.empresa,
      estado: state.filtro.estado,
      q: state.filtro.q,
    });
    return `${EXPORT_URL}?${p}`;
  }

  /* ── Eventos ────────────────────────────────────────────────────────── */

  function bind() {
    $("#repRangos").on("click", ".rep-quick-btn", function () {
      aplicarRango($(this).data("rango"));
    });

    $("#repDesde").on("change", function () {
      state.filtro.desde = this.value;
      marcarRangoManual();
      cargar();
    });

    $("#repHasta").on("change", function () {
      state.filtro.hasta = this.value;
      marcarRangoManual();
      cargar();
    });

    $("#repCampoFecha").on("change", function () {
      state.filtro.campo_fecha = this.value;
      $("#repHintFecha").toggleClass("d-none", this.value !== "fin");
      cargar();
    });

    $("#repEmpresa").on("change", function () {
      state.filtro.empresa = this.value;
      cargar();
    });

    $("#repBuscar").on("input", function () {
      state.filtro.q = this.value.trim();
      cargarConEspera();
    });

    $("#repKpis").on("click", ".rep-kpi", function () {
      state.filtro.estado = String($(this).data("estado") || "");
      renderKpis();
      renderTabla();
    });

    $("#btnRepLimpiar").on("click", function () {
      state.filtro = { desde: "", hasta: "", campo_fecha: "inicio", empresa: "", estado: "", q: "" };
      $("#repDesde, #repHasta, #repBuscar").val("");
      $("#repCampoFecha").val("inicio");
      $("#repHintFecha").addClass("d-none");
      $("#repEmpresa").val("");
      $("#repRangos .rep-quick-btn").removeClass("active");
      $('#repRangos .rep-quick-btn[data-rango="todo"]').addClass("active");
      cargar();
    });

    $("#btnRepActualizar").on("click", cargar);

    $("#btnRepExcel").on("click", function (e) {
      e.preventDefault();
      if (!visibles().length) {
        Swal.fire({
          icon: "info",
          title: "No hay nada que descargar",
          text: "Ajusta los filtros para que el reporte muestre al menos un alumno.",
          confirmButtonColor: "#01643D",
        });
        return;
      }
      window.location.href = urlExcel();
    });
  }

  /* ── Arranque ───────────────────────────────────────────────────────── */

  $(function () {
    if (!document.getElementById("tab-reportes")) return;
    bind();
    cargarEmpresas();
    renderKpis();
    cargar();
  });
})();
