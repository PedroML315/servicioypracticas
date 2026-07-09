/* ============================================================
   Selector de habilidades para vacantes (catálogo + personalizadas)
   Usado por el wizard "Nueva Vacante" y el modal "Editar Vacante".
   El catálogo se carga solo (action getHabilidadesCatalogo) y también
   puede llegar por getDashboardSummary vía window.setHabilidadesCatalogo().
   Al enfocar el campo se despliega el catálogo completo por área;
   al escribir se filtra y se ofrece agregar la habilidad como personalizada.
   ============================================================ */
(function () {
  let catalogo = [];

  window.setHabilidadesCatalogo = function (list) {
    if (Array.isArray(list) && list.length) {
      catalogo = list;
    }
  };

  function quitaAcentos(s) {
    return s
      .normalize("NFD")
      .split("")
      .filter(function (ch) {
        const c = ch.charCodeAt(0);
        return c < 768 || c > 879; // rango de diacríticos combinantes
      })
      .join("");
  }

  function normaliza(s) {
    return quitaAcentos((s == null ? "" : s).toString().trim().toLowerCase());
  }

  function escapeHtml(s) {
    return $("<div>").text(s == null ? "" : s).html();
  }

  window.initSkillsPicker = function (opts) {
    const $search = $(opts.search);
    const $chips = $(opts.chips);
    const $sugg = $(opts.sugg);
    const $hidden = $(opts.hidden);

    let seleccion = [];   // [{id: int|null, nombre: string}]
    let sugerencias = []; // opciones visibles del dropdown, en orden

    function sync() {
      $hidden.val(JSON.stringify(seleccion));
      $chips.html(
        seleccion
          .map(
            (h, i) => `
        <span class="skill-chip ${h.id ? "" : "custom"}" title="${h.id ? "Habilidad del catálogo" : "Habilidad personalizada"}">
          ${escapeHtml(h.nombre)}
          <i class="fas fa-times chip-remove" data-idx="${i}"></i>
        </span>`
          )
          .join("")
      );
    }

    function agregar(h) {
      const nombre = (h.nombre || "").trim();
      if (nombre === "") return;
      const repetida = seleccion.some((x) => normaliza(x.nombre) === normaliza(nombre));
      if (!repetida) {
        seleccion.push({ id: h.id || null, nombre: nombre });
        sync();
      }
      $search.val("").focus();
      renderSugerencias(""); // vuelve a mostrar el catálogo completo
    }

    function noSeleccionada(c) {
      return !seleccion.some((x) => normaliza(x.nombre) === normaliza(c.nombre));
    }

    /* term vacío → catálogo completo agrupado; con texto → filtrado + opción personalizada */
    function renderSugerencias(term) {
      const t = normaliza(term);
      sugerencias = [];

      const disponibles = catalogo.filter(
        (c) => noSeleccionada(c) && (!t || normaliza(c.nombre).includes(t))
      );

      let html = "";
      let ultimaArea = null;
      disponibles.forEach((c) => {
        if (c.area !== ultimaArea) {
          html += `<div class="sugg-area">${escapeHtml(c.area || "Otras")}</div>`;
          ultimaArea = c.area;
        }
        html += `<div class="sugg-item" data-idx="${sugerencias.length}">${escapeHtml(c.nombre)}</div>`;
        sugerencias.push({ id: parseInt(c.id, 10), nombre: c.nombre });
      });

      if (t) {
        const exacta = catalogo.some((c) => normaliza(c.nombre) === t);
        const yaSeleccionada = seleccion.some((x) => normaliza(x.nombre) === t);
        if (!exacta && !yaSeleccionada) {
          html += `<div class="sugg-item custom-add" data-idx="${sugerencias.length}">
                     <i class="fas fa-plus me-1"></i> Agregar "<strong>${escapeHtml(term.trim())}</strong>" como habilidad personalizada
                   </div>`;
          sugerencias.push({ id: null, nombre: term.trim() });
        }
      } else if (catalogo.length === 0) {
        html += `<div class="sugg-area">Escribe la habilidad y presiona Enter para agregarla</div>`;
      }

      if (html === "") {
        $sugg.hide();
      } else {
        $sugg.html(html).show();
      }
    }

    // Desplegar el catálogo al enfocar o hacer clic, sin necesidad de escribir
    $search.on("focus click", function () {
      renderSugerencias(this.value);
    });

    $search.on("input", function () {
      renderSugerencias(this.value);
    });

    $search.on("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        if (this.value.trim() === "") return;
        if (sugerencias.length > 0 && $sugg.is(":visible")) {
          agregar(sugerencias[0]);
        } else {
          agregar({ id: null, nombre: this.value.trim() });
        }
      } else if (e.key === "Escape") {
        $sugg.hide();
      }
    });

    // mousedown para ganar al blur del input
    $sugg.on("mousedown", ".sugg-item", function (e) {
      e.preventDefault();
      const opcion = sugerencias[parseInt($(this).data("idx"), 10)];
      if (opcion) agregar(opcion);
    });

    $search.on("blur", function () {
      setTimeout(() => $sugg.hide(), 180);
    });

    $chips.on("click", ".chip-remove", function () {
      seleccion.splice(parseInt($(this).data("idx"), 10), 1);
      sync();
    });

    const api = {
      get: () => seleccion.slice(),
      set: (list) => {
        seleccion = (list || [])
          .map((h) => ({
            id: h.id || h.habilidad_id || null,
            nombre: (h.nombre || "").trim(),
          }))
          .filter((h) => h.nombre !== "");
        sync();
      },
      clear: () => {
        seleccion = [];
        sync();
      },
      validate: () => {
        if (seleccion.length === 0) {
          const el = $search[0];
          el.setCustomValidity("Agrega al menos una habilidad para el perfil del estudiante.");
          el.reportValidity();
          el.setCustomValidity("");
          return false;
        }
        return true;
      },
    };

    sync();
    return api;
  };

  $(function () {
    // Carga propia del catálogo (independiente del resumen del dashboard)
    $.post("controller/organismo/forms.php", { action: "getHabilidadesCatalogo" }, null, "json")
      .done(function (list) {
        window.setHabilidadesCatalogo(list);
      })
      .fail(function () {
        console.warn("No se pudo cargar el catálogo de habilidades.");
      });

    if (document.getElementById("buscarHabilidad")) {
      window.skillsPickerNueva = window.initSkillsPicker({
        search: "#buscarHabilidad",
        chips: "#chipsHabilidades",
        sugg: "#suggHabilidades",
        hidden: "#habilidades",
      });
    }
    if (document.getElementById("buscarHabilidadEditar")) {
      window.skillsPickerEditar = window.initSkillsPicker({
        search: "#buscarHabilidadEditar",
        chips: "#chipsHabilidadesEditar",
        sugg: "#suggHabilidadesEditar",
        hidden: "#habilidadesEditar",
      });
    }
  });
})();
