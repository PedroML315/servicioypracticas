/* ============================================================================
 * REGISTRO UNIMO · Núcleo compartido de los formularios públicos de registro
 * ----------------------------------------------------------------------------
 * Vanilla ES2019. Sin jQuery, sin Bootstrap, sin dependencias externas.
 *
 * Expone un único global: window.RG
 *
 *   RG.wizard(opts)      → motor multipaso (navegación, validación, progreso)
 *   RG.validateField(el) → validación de un control con mensaje en español
 *   RG.dropzone(el)      → zona de carga de archivo con arrastrar y soltar
 *   RG.legalDoc(opts)    → visor de PDF con lectura mínima obligatoria
 *   RG.buildReview(opts) → resumen automático del formulario para el paso final
 *   RG.draft(opts)       → borrador en sessionStorage (recupera al recargar)
 *   RG.button(btn, st)   → estado del botón: idle | loading | done
 *   RG.alert(opts)       → alerta de nivel formulario, accesible
 *
 * Convenciones de marcado que consume este núcleo:
 *   .rg-field          contenedor de un campo (recibe is-valid / is-invalid)
 *   [data-label]       nombre legible del campo (mensajes y resumen)
 *   [data-rule]        regla extra: tel | tel-ext | cp | curp | matricula | edad
 *   [data-msg]         mensaje de error personalizado
 *   [data-review]      incluir el campo en el resumen de revisión
 * ========================================================================== */
(function (window, document) {
    'use strict';

    var RG = {};

    /* ── Utilidades ──────────────────────────────────────────────────────── */

    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    function debounce(fn, wait) {
        var t;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    function digits(str) { return (str || '').replace(/\D+/g, ''); }

    function formatBytes(bytes) {
        if (!bytes) return '0 KB';
        var kb = bytes / 1024;
        if (kb < 1024) return Math.max(1, Math.round(kb)) + ' KB';
        return (kb / 1024).toFixed(1).replace('.0', '') + ' MB';
    }

    function formatDate(iso) {
        if (!iso) return '';
        var p = iso.split('-');
        if (p.length !== 3) return iso;
        var meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
            'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        var m = parseInt(p[1], 10) - 1;
        return parseInt(p[2], 10) + ' de ' + (meses[m] || p[1]) + ' de ' + p[0];
    }

    function fieldOf(input) { return input.closest ? input.closest('.rg-field') : null; }

    RG.$ = $;
    RG.$$ = $$;
    RG.debounce = debounce;
    RG.formatBytes = formatBytes;
    RG.formatDate = formatDate;

    /* ── Máscara de sólo dígitos ─────────────────────────────────────────── */

    /**
     * Deja el campo con puros dígitos y lo recorta a su maxlength.
     * Se usa con data-mask="digits": teléfonos (10), código postal (5),
     * matrícula (10). No inserta separadores a propósito — el valor viaja al
     * servidor tal cual y así queda guardado igual que siempre.
     *
     * Respeta la posición del cursor: si alguien pega "(443) 123-4567" en medio
     * del texto, el cursor queda donde le corresponde y no salta al final.
     */
    function maskDigits(el) {
        var tope = parseInt(el.getAttribute('maxlength'), 10);
        if (!tope || tope < 1) tope = 999;

        var cursor = el.selectionStart;
        var digitosAntesDelCursor = (cursor === null)
            ? null
            : el.value.slice(0, cursor).replace(/\D/g, '').length;

        var limpio = el.value.replace(/\D/g, '').slice(0, tope);
        if (limpio === el.value) return false;

        el.value = limpio;
        if (digitosAntesDelCursor !== null) {
            var pos = Math.min(digitosAntesDelCursor, limpio.length);
            try { el.setSelectionRange(pos, pos); } catch (e) { }
        }
        return true;
    }

    RG.maskDigits = maskDigits;

    /**
     * Reaplica las máscaras de un contenedor. Hace falta después de rellenar
     * campos por JS (los datos de Control Escolar llegan como "(443) 123-4567"),
     * porque asignar .value no dispara el evento input ni respeta maxlength.
     */
    RG.applyMasks = function (scope) {
        $$('[data-mask="digits"]', scope || document).forEach(function (el) {
            if (el.value) maskDigits(el);
        });
    };

    /* ── Validación ──────────────────────────────────────────────────────── */

    var RE = {
        email: /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/,
        curp: /^[A-Z]{4}\d{6}[HMX][A-Z]{5}[0-9A-Z]\d$/,
        url: /^https?:\/\/[^\s.]+\.[^\s]{2,}$/i
    };

    /* Reglas extra declaradas con data-rule. Devuelven null si todo está bien. */
    var reglas = {
        tel: function (v) {
            return digits(v).length === 10 ? null : 'Escribe los 10 dígitos del teléfono, sin espacios.';
        },
        'tel-ext': function (v) {
            var d = digits(v);
            return d.length >= 10 && d.length <= 15 ? null : 'Escribe al menos los 10 dígitos del teléfono.';
        },
        cp: function (v) {
            return /^\d{5}$/.test(v.trim()) ? null : 'El código postal debe tener 5 dígitos.';
        },
        curp: function (v) {
            return RE.curp.test(v.trim().toUpperCase()) ? null : 'La CURP debe tener 18 caracteres con el formato oficial.';
        },
        matricula: function (v) {
            return /^\d{4,10}$/.test(v.trim()) ? null : 'La matrícula sólo lleva números.';
        },
        edad: function (v) {
            if (!v) return null;
            var nace = new Date(v + 'T00:00:00');
            if (isNaN(nace.getTime())) return 'Fecha no válida.';
            var hoy = new Date();
            var edad = hoy.getFullYear() - nace.getFullYear();
            var m = hoy.getMonth() - nace.getMonth();
            if (m < 0 || (m === 0 && hoy.getDate() < nace.getDate())) edad--;
            if (edad < 15) return 'La fecha de nacimiento no parece correcta.';
            if (edad > 90) return 'La fecha de nacimiento no parece correcta.';
            return null;
        },
        'no-futuro': function (v) {
            if (!v) return null;
            var f = new Date(v + 'T00:00:00');
            return f.getTime() > Date.now() ? 'La fecha no puede ser posterior a hoy.' : null;
        }
    };

    RG.reglas = reglas;

    /**
     * Valida un control y devuelve el mensaje de error, o null si es válido.
     * No toca el DOM: eso lo hace paintField().
     */
    function checkField(input) {
        var valor = (input.type === 'checkbox' || input.type === 'radio')
            ? (input.checked ? 'on' : '')
            : (input.value || '');
        var requerido = input.hasAttribute('required') || input.dataset.required === '1';
        var etiqueta = input.dataset.label || '';

        if (input.disabled) return null;

        if (requerido && !valor.trim()) {
            if (input.dataset.msgRequired) return input.dataset.msgRequired;
            if (input.tagName === 'SELECT') return 'Elige una opción' + (etiqueta ? ' de "' + etiqueta + '"' : '') + '.';
            return 'Falta ' + (etiqueta ? '"' + etiqueta + '"' : 'este dato') + '. Es obligatorio.';
        }

        if (!valor.trim()) return null;              // opcional y vacío → válido
        if (input.dataset.msg) {
            // Mensaje único para cualquier fallo de formato del campo
            var err = formatoError(input, valor);
            return err ? input.dataset.msg : null;
        }
        return formatoError(input, valor);
    }

    function formatoError(input, valor) {
        if (input.type === 'email' && !RE.email.test(valor.trim())) {
            return 'El correo no parece válido. Ejemplo: nombre@dominio.com';
        }
        if (input.type === 'url' && !RE.url.test(valor.trim())) {
            return 'La dirección debe empezar con https:// — Ejemplo: https://www.miempresa.com';
        }
        var min = parseInt(input.getAttribute('minlength'), 10);
        if (min && valor.trim().length < min) {
            return 'Escribe al menos ' + min + ' caracteres.';
        }
        var pat = input.getAttribute('pattern');
        if (pat && !(new RegExp('^(?:' + pat + ')$')).test(valor)) {
            return 'El formato no es válido.';
        }
        var lista = (input.dataset.rule || '').split(/\s+/).filter(Boolean);
        for (var i = 0; i < lista.length; i++) {
            var fn = reglas[lista[i]];
            if (fn) {
                var msg = fn(valor, input);
                if (msg) return msg;
            }
        }
        return null;
    }

    /** Pinta el estado de un campo y devuelve true si es válido. */
    function paintField(input, mensaje, opts) {
        opts = opts || {};
        var wrap = fieldOf(input);
        if (!wrap) return !mensaje;

        var errEl = $('.rg-error', wrap);
        var tieneValor = (input.value || '').trim() !== '';

        // Se recuerda el aria-describedby original (ayudas, paneles de estado)
        // para poder restaurarlo cuando el error desaparece.
        if (input.dataset.describedbyBase === undefined) {
            input.dataset.describedbyBase = input.getAttribute('aria-describedby') || '';
        }
        var base = input.dataset.describedbyBase;

        if (mensaje) {
            wrap.classList.add('is-invalid');
            wrap.classList.remove('is-valid');
            input.setAttribute('aria-invalid', 'true');
            if (errEl) {
                errEl.textContent = mensaje;
                errEl.hidden = false;
                if (!errEl.id) errEl.id = 'rg-err-' + Math.random().toString(36).slice(2, 8);
                input.setAttribute('aria-describedby', (base ? base + ' ' : '') + errEl.id);
            }
            if (opts.shake) {
                wrap.classList.remove('is-shake');
                void wrap.offsetWidth;             // reinicia la animación
                wrap.classList.add('is-shake');
            }
            return false;
        }

        wrap.classList.remove('is-invalid', 'is-shake');
        wrap.classList.toggle('is-valid', tieneValor && !input.disabled);
        input.removeAttribute('aria-invalid');
        if (errEl) { errEl.hidden = true; errEl.textContent = ''; }
        if (base) input.setAttribute('aria-describedby', base);
        else input.removeAttribute('aria-describedby');
        return true;
    }

    RG.validateField = function (input, opts) {
        return paintField(input, checkField(input), opts);
    };

    RG.clearField = function (input) {
        var wrap = fieldOf(input);
        if (!wrap) return;
        wrap.classList.remove('is-invalid', 'is-valid', 'is-shake');
        var errEl = $('.rg-error', wrap);
        if (errEl) { errEl.hidden = true; errEl.textContent = ''; }
        input.removeAttribute('aria-invalid');
    };

    /** Controles "reales" de un contenedor (ignora los deshabilitados). */
    function controlsOf(scope) {
        return $$('input, select, textarea', scope).filter(function (el) {
            return !el.disabled && el.type !== 'hidden' && el.type !== 'file' && el.type !== 'button';
        });
    }

    /* ── Grupos de radio dibujados como tarjetas ─────────────────────────── */

    function syncChoices(scope) {
        $$('.rg-choice input', scope).forEach(function (input) {
            var card = input.closest('.rg-choice');
            if (card) card.classList.toggle('is-checked', input.checked);
        });
    }

    /* ── Motor multipaso ─────────────────────────────────────────────────── */

    /**
     * @param {Object} o
     * @param {HTMLElement} o.root      contenedor con los .rg-step
     * @param {HTMLElement} o.rail      lista del riel lateral (opcional)
     * @param {HTMLElement} o.mobar     barra móvil (opcional)
     * @param {Function}    o.onEnter   (indice, stepEl) al mostrar un paso
     * @param {Function}    o.canLeave  (indice, stepEl) → bool, validación extra
     */
    RG.wizard = function (o) {
        var pasos = $$('.rg-step', o.root);
        var total = pasos.length;
        var actual = 0;
        var maxVisitado = 0;

        var railSteps = o.rail ? $$('.rg-rail__step', o.rail) : [];
        var mobarStep = o.mobar ? $('.rg-mobar__step', o.mobar) : null;
        var mobarCount = o.mobar ? $('.rg-mobar__count', o.mobar) : null;
        var mobarFill = o.mobar ? $('.rg-mobar__fill', o.mobar) : null;
        var mobarBar = o.mobar ? $('[role="progressbar"]', o.mobar) : null;

        function pintarProgreso() {
            var pct = total > 1 ? (actual / (total - 1)) * 100 : 100;

            railSteps.forEach(function (s, i) {
                s.classList.toggle('is-active', i === actual);
                s.classList.toggle('is-done', i < actual);
                var puedeIr = i <= maxVisitado && i !== actual;
                s.dataset.clickable = puedeIr ? '1' : '0';
                s.disabled = !puedeIr;
                if (i === actual) s.setAttribute('aria-current', 'step');
                else s.removeAttribute('aria-current');
                var disco = $('.rg-rail__disc', s);
                if (disco) {
                    disco.innerHTML = i < actual
                        ? '<i class="fa-solid fa-check" aria-hidden="true"></i>'
                        : String(i + 1);
                }
            });
            if (o.rail) o.rail.style.setProperty('--rg-rail-fill', pct + '%');

            var titulo = pasos[actual].dataset.title || '';
            if (mobarStep) mobarStep.textContent = titulo;
            if (mobarCount) mobarCount.textContent = 'Paso ' + (actual + 1) + ' de ' + total;
            if (mobarFill) mobarFill.style.width = (((actual + 1) / total) * 100) + '%';
            if (mobarBar) {
                mobarBar.setAttribute('aria-valuenow', String(actual + 1));
                mobarBar.setAttribute('aria-valuetext', 'Paso ' + (actual + 1) + ' de ' + total + ': ' + titulo);
            }
        }

        function mostrar(i, opts) {
            opts = opts || {};
            actual = i;
            maxVisitado = Math.max(maxVisitado, i);
            pasos.forEach(function (p, idx) { p.classList.toggle('is-active', idx === i); });
            pintarProgreso();

            if (!opts.silent) {
                var card = o.root.closest('.rg-card') || o.root;
                var y = card.getBoundingClientRect().top + window.pageYOffset - 90;
                window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
                // El foco va al encabezado del paso: los lectores de pantalla
                // anuncian dónde quedó el usuario sin robarle el teclado.
                var h = $('h2', pasos[i]);
                if (h) { h.setAttribute('tabindex', '-1'); h.focus({ preventScroll: true }); }
            }
            if (typeof o.onEnter === 'function') o.onEnter(i, pasos[i]);
        }

        function validarPaso(i) {
            var ok = true;
            var primerFallo = null;

            controlsOf(pasos[i]).forEach(function (el) {
                if (el.type === 'radio') return;         // se validan por grupo
                if (!RG.validateField(el)) {
                    ok = false;
                    if (!primerFallo) primerFallo = el;
                }
            });

            // Grupos de radio obligatorios
            var vistos = {};
            $$('input[type="radio"][required]', pasos[i]).forEach(function (r) {
                if (vistos[r.name]) return;
                vistos[r.name] = true;
                var grupo = $$('input[name="' + r.name + '"]', pasos[i]);
                var elegido = grupo.some(function (g) { return g.checked; });
                var caja = r.closest('.rg-choices') || r.closest('.rg-field');
                var err = caja ? $('.rg-error', caja.parentNode) || $('.rg-error', caja) : null;
                if (err) { err.hidden = elegido; if (!elegido) err.textContent = r.dataset.msgRequired || 'Elige una opción para continuar.'; }
                if (!elegido) {
                    ok = false;
                    if (!primerFallo) primerFallo = r;
                }
            });

            if (typeof o.canLeave === 'function' && ok) {
                var extra = o.canLeave(i, pasos[i]);
                if (extra === false) ok = false;
            }

            if (!ok && primerFallo) {
                var wrap = fieldOf(primerFallo) || primerFallo;
                wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
                try { primerFallo.focus({ preventScroll: true }); } catch (e) { }
                if (fieldOf(primerFallo)) RG.validateField(primerFallo, { shake: true });
            }
            return ok;
        }

        /* Delegación: los botones pueden crearse dinámicamente */
        o.root.addEventListener('click', function (ev) {
            var next = ev.target.closest('.rg-next');
            if (next) {
                ev.preventDefault();
                if (validarPaso(actual) && actual < total - 1) mostrar(actual + 1);
                return;
            }
            var prev = ev.target.closest('.rg-prev');
            if (prev) {
                ev.preventDefault();
                if (actual > 0) mostrar(actual - 1);
            }
        });

        railSteps.forEach(function (s, i) {
            s.addEventListener('click', function () {
                if (s.dataset.clickable !== '1') return;
                // Hacia adelante sólo si el paso actual está completo
                if (i > actual && !validarPaso(actual)) return;
                mostrar(i);
            });
        });

        /* Enter avanza (salvo en textarea y en el paso final) */
        o.root.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Enter') return;
            var t = ev.target;
            if (t.tagName === 'TEXTAREA' || t.tagName === 'BUTTON') return;
            var btnNext = $('.rg-next', pasos[actual]);
            if (btnNext && !btnNext.disabled) {
                ev.preventDefault();
                btnNext.click();
            }
        });

        /* Validación en vivo: al salir del campo, y al corregir mientras escribe */
        o.root.addEventListener('blur', function (ev) {
            var el = ev.target;
            if (!el.matches || !el.matches('input, select, textarea')) return;
            if (el.type === 'file' || el.type === 'hidden') return;
            if (!(el.value || '').trim() && !el.hasAttribute('required')) { RG.clearField(el); return; }
            RG.validateField(el);
        }, true);

        o.root.addEventListener('input', function (ev) {
            var el = ev.target;
            if (!el.matches || !el.matches('input, select, textarea')) return;
            // La máscara va primero: valida y cuenta ya sobre el valor limpio
            if (el.dataset.mask === 'digits') maskDigits(el);
            var wrap = fieldOf(el);
            if (wrap && wrap.classList.contains('is-invalid')) RG.validateField(el);
            var cont = wrap ? $('.rg-count', wrap) : null;
            if (cont && el.maxLength > 0) cont.textContent = el.value.length + ' / ' + el.maxLength;
        });

        o.root.addEventListener('change', function (ev) {
            var el = ev.target;
            if (!el.matches) return;
            if (el.matches('.rg-choice input')) {
                syncChoices(o.root);
                // Al elegir una opción desaparece el error del grupo
                var campo = el.closest('.rg-field');
                var err = campo ? $('.rg-error', campo) : null;
                if (err) { err.hidden = true; err.textContent = ''; }
            }
            if (el.matches('select')) RG.validateField(el);
        });

        syncChoices(o.root);
        mostrar(0, { silent: true });

        return {
            get index() { return actual; },
            get total() { return total; },
            steps: pasos,
            go: mostrar,
            validate: validarPaso,
            validateAll: function () {
                for (var i = 0; i < total; i++) {
                    if (!validarPaso(i)) { mostrar(i); return false; }
                }
                return true;
            },
            refresh: pintarProgreso
        };
    };

    /* ── Zona de carga de archivos ───────────────────────────────────────── */

    /**
     * Mejora un .rg-doc que contiene un <input type="file">.
     * Valida extensión y peso en el navegador para no gastarle datos al usuario
     * en una subida que el servidor rechazaría de todos modos.
     */
    RG.dropzone = function (doc, opts) {
        opts = opts || {};
        var input = $('input[type="file"]', doc);
        if (!input) return null;

        var maxMB = opts.maxMB || 8;
        var exts = opts.exts || ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        var zona = $('.rg-drop', doc);
        var fileBox = $('.rg-file', doc);
        var nameEl = $('.rg-file__name', doc);
        var sizeEl = $('.rg-file__size', doc);
        var iconEl = $('.rg-file__icon i', doc);
        var errEl = $('.rg-error', doc);
        var btnQuitar = $('.rg-file__remove', doc);
        var btnCambiar = $('.rg-file__replace', doc);

        function error(msg) {
            doc.classList.add('is-invalid');
            doc.classList.remove('is-filled');
            if (errEl) { errEl.textContent = msg; errEl.hidden = false; }
            input.value = '';
        }

        function limpiarError() {
            doc.classList.remove('is-invalid');
            if (errEl) { errEl.hidden = true; errEl.textContent = ''; }
        }

        function aplicar(file) {
            limpiarError();
            if (!file) {
                doc.classList.remove('is-filled');
                return false;
            }
            var ext = (file.name.split('.').pop() || '').toLowerCase();
            if (exts.indexOf(ext) === -1) {
                error('Formato no aceptado. Sube un archivo ' + exts.join(', ').toUpperCase() + '.');
                return false;
            }
            if (file.size > maxMB * 1024 * 1024) {
                error('El archivo pesa ' + formatBytes(file.size) + '. El máximo es ' + maxMB + ' MB.');
                return false;
            }
            if (nameEl) nameEl.textContent = file.name;
            if (sizeEl) sizeEl.textContent = formatBytes(file.size) + ' · listo para enviar';
            if (iconEl) iconEl.className = ext === 'pdf' ? 'fa-solid fa-file-pdf' : 'fa-solid fa-file-image';
            doc.classList.add('is-filled');
            if (typeof opts.onChange === 'function') opts.onChange(file, doc);
            return true;
        }

        input.addEventListener('change', function () {
            aplicar(input.files && input.files[0]);
        });

        if (zona) {
            zona.addEventListener('click', function () { input.click(); });
            zona.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); input.click(); }
            });
        }
        if (btnCambiar) btnCambiar.addEventListener('click', function () { input.click(); });
        if (btnQuitar) {
            btnQuitar.addEventListener('click', function () {
                input.value = '';
                doc.classList.remove('is-filled');
                limpiarError();
                if (typeof opts.onChange === 'function') opts.onChange(null, doc);
                if (zona) zona.focus();
            });
        }

        ['dragenter', 'dragover'].forEach(function (ev) {
            doc.addEventListener(ev, function (e) {
                e.preventDefault(); e.stopPropagation();
                doc.classList.add('is-dragover');
            });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            doc.addEventListener(ev, function (e) {
                e.preventDefault(); e.stopPropagation();
                if (ev === 'dragleave' && doc.contains(e.relatedTarget)) return;
                doc.classList.remove('is-dragover');
            });
        });
        doc.addEventListener('drop', function (e) {
            var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
            if (!f) return;
            // DataTransfer permite inyectar el archivo en el input sin perder el
            // envío nativo por multipart/form-data.
            try {
                var dt = new DataTransfer();
                dt.items.add(f);
                input.files = dt.files;
            } catch (err) { /* navegador antiguo: se ignora y se pide clic */ }
            aplicar(input.files && input.files[0] ? input.files[0] : null);
        });

        return {
            get filled() { return doc.classList.contains('is-filled'); },
            markMissing: function (scroll) {
                error('Falta adjuntar este documento.');
                if (scroll !== false) doc.scrollIntoView({ behavior: 'smooth', block: 'center' });
            },
            input: input
        };
    };

    /* ── Diálogo legal con lectura mínima obligatoria ────────────────────── */

    RG.legalDoc = function (o) {
        var dlg = o.dialog;
        var abrir = o.opener;
        var aceptar = o.accept;
        var timer = o.timer;
        var check = o.checkbox;
        var consent = o.consent;
        var estado = consent ? $('.rg-consent__status', consent) : null;
        var segundos = typeof o.seconds === 'number' ? o.seconds : 5;
        var aceptado = false;
        var cuenta = null;

        function soporteNativo() { return typeof dlg.showModal === 'function'; }

        function abrirDlg() {
            if (soporteNativo()) dlg.showModal();
            else { dlg.setAttribute('open', ''); dlg.style.display = 'block'; }

            if (aceptado) {
                if (timer) { timer.innerHTML = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i> ' + o.acceptedText; timer.classList.add('is-ready'); }
                aceptar.disabled = false;
                return;
            }
            var restante = segundos;
            aceptar.disabled = true;
            pintarTimer(restante);
            clearInterval(cuenta);
            cuenta = setInterval(function () {
                restante--;
                if (restante <= 0) {
                    clearInterval(cuenta);
                    aceptar.disabled = false;
                    if (timer) {
                        timer.innerHTML = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i> Ya puedes aceptar el documento.';
                        timer.classList.add('is-ready');
                    }
                } else {
                    pintarTimer(restante);
                }
            }, 1000);
        }

        function pintarTimer(n) {
            if (!timer) return;
            timer.classList.remove('is-ready');
            timer.innerHTML = '<i class="fa-solid fa-clock" aria-hidden="true"></i> Tómate un momento para leerlo · '
                + n + ' s';
        }

        function cerrarDlg() {
            clearInterval(cuenta);
            if (soporteNativo()) dlg.close();
            else { dlg.removeAttribute('open'); dlg.style.display = 'none'; }
        }

        if (abrir) abrir.addEventListener('click', function (e) { e.preventDefault(); abrirDlg(); });
        $$('[data-close-dialog]', dlg).forEach(function (b) {
            b.addEventListener('click', function (e) { e.preventDefault(); cerrarDlg(); });
        });
        dlg.addEventListener('close', function () { clearInterval(cuenta); });

        aceptar.addEventListener('click', function () {
            aceptado = true;
            check.checked = true;
            if (consent) {
                consent.classList.add('is-accepted');
                consent.classList.remove('is-invalid');
            }
            if (estado) {
                estado.innerHTML = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i> ' + o.acceptedText;
            }
            check.dispatchEvent(new Event('change', { bubbles: true }));
            cerrarDlg();
            if (abrir) abrir.focus();
        });

        return {
            get accepted() { return aceptado; },
            open: abrirDlg,
            markMissing: function () {
                if (consent) {
                    consent.classList.add('is-invalid');
                    consent.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        };
    };

    /* ── Resumen de revisión ─────────────────────────────────────────────── */

    /** Texto legible del valor de un control, para el resumen. */
    function valorLegible(el) {
        if (el.tagName === 'SELECT') {
            var op = el.options[el.selectedIndex];
            return op && op.value ? op.textContent.trim() : '';
        }
        if (el.type === 'radio') {
            if (!el.checked) return null;
            var card = el.closest('.rg-choice');
            var t = card ? $('.rg-choice__title', card) : null;
            return t ? t.textContent.trim() : el.value;
        }
        if (el.type === 'checkbox') return el.checked ? 'Sí' : 'No';
        if (el.type === 'date') return formatDate(el.value);
        if (el.type === 'file') {
            var f = el.files && el.files[0];
            return f ? f.name : '';
        }
        return (el.value || '').trim();
    }

    /**
     * Construye el resumen leyendo los pasos anteriores al de revisión.
     * Cada bloque corresponde a un paso y trae un botón "Editar" que regresa
     * exactamente a él: el usuario nunca tiene que rehacer el camino completo.
     */
    RG.buildReview = function (o) {
        var destino = o.target;
        var pasos = o.steps;
        var hasta = typeof o.upTo === 'number' ? o.upTo : pasos.length;
        var frag = document.createDocumentFragment();

        for (var i = 0; i < hasta; i++) {
            var paso = pasos[i];
            var campos = $$('[data-review]', paso).filter(function (el) {
                // Un campo condicional oculto (p. ej. "especifica el parentesco"
                // cuando no aplica) no debe aparecer como "Sin capturar".
                if (el.disabled || el.closest('[hidden]')) return false;
                var v = valorLegible(el);
                return v !== null && v !== undefined;
            });
            if (!campos.length) continue;

            var items = campos.map(function (el) {
                var v = valorLegible(el);
                var etiqueta = el.dataset.label || el.name || '';
                return { label: etiqueta, value: v };
            }).filter(function (x) { return x.label; });

            if (!items.length) continue;

            var bloque = document.createElement('section');
            bloque.className = 'rg-review__block';

            var head = document.createElement('div');
            head.className = 'rg-review__head';
            var h3 = document.createElement('h3');
            h3.textContent = paso.dataset.title || ('Paso ' + (i + 1));
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rg-review__edit';
            btn.innerHTML = '<i class="fa-solid fa-pen" aria-hidden="true"></i> Editar';
            btn.setAttribute('aria-label', 'Editar ' + h3.textContent);
            (function (idx) {
                btn.addEventListener('click', function () { o.onEdit(idx); });
            })(i);
            head.appendChild(h3);
            head.appendChild(btn);

            var dl = document.createElement('dl');
            dl.className = 'rg-review__list';
            items.forEach(function (it) {
                var wrap = document.createElement('div');
                wrap.className = 'rg-review__item';
                var dt = document.createElement('dt');
                dt.textContent = it.label;
                var dd = document.createElement('dd');
                if (it.value) {
                    dd.textContent = it.value;
                } else {
                    dd.textContent = 'Sin capturar';
                    dd.className = 'is-empty';
                }
                wrap.appendChild(dt);
                wrap.appendChild(dd);
                dl.appendChild(wrap);
            });

            bloque.appendChild(head);
            bloque.appendChild(dl);
            frag.appendChild(bloque);
        }

        destino.innerHTML = '';
        destino.appendChild(frag);
    };

    /* ── Borrador en sessionStorage ──────────────────────────────────────── */

    /**
     * Guarda lo capturado mientras dura la pestaña. No persiste archivos ni
     * consentimientos: aceptar los documentos legales siempre debe ser un acto
     * explícito del usuario en la sesión actual.
     */
    RG.draft = function (o) {
        var clave = o.key;
        var form = o.form;
        var omitir = o.skip || [];
        var disponible = (function () {
            try { window.sessionStorage.setItem('__rg', '1'); window.sessionStorage.removeItem('__rg'); return true; }
            catch (e) { return false; }
        })();

        function guardar() {
            if (!disponible) return;
            var datos = {};
            $$('input, select, textarea', form).forEach(function (el) {
                if (!el.name || el.type === 'file' || el.type === 'checkbox') return;
                if (omitir.indexOf(el.name) !== -1) return;
                if (el.type === 'radio') { if (el.checked) datos[el.name] = el.value; return; }
                if ((el.value || '') !== '') datos[el.name] = el.value;
            });
            try { window.sessionStorage.setItem(clave, JSON.stringify(datos)); } catch (e) { }
        }

        return {
            restore: function () {
                if (!disponible) return null;
                var raw;
                try { raw = window.sessionStorage.getItem(clave); } catch (e) { return null; }
                if (!raw) return null;
                var datos;
                try { datos = JSON.parse(raw); } catch (e) { return null; }
                Object.keys(datos).forEach(function (name) {
                    var els = $$('[name="' + name.replace(/"/g, '\\"') + '"]', form);
                    els.forEach(function (el) {
                        if (el.type === 'radio') el.checked = (el.value === datos[name]);
                        else el.value = datos[name];
                    });
                });
                syncChoices(form);
                return datos;
            },
            watch: function () {
                if (!disponible) return;
                var g = debounce(guardar, 400);
                form.addEventListener('input', g);
                form.addEventListener('change', g);
            },
            clear: function () {
                if (!disponible) return;
                try { window.sessionStorage.removeItem(clave); } catch (e) { }
            }
        };
    };

    /* ── Estado de botón ─────────────────────────────────────────────────── */

    RG.button = function (btn, estado, texto) {
        if (!btn) return;
        if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
        if (estado === 'loading') {
            btn.classList.add('is-loading');
            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            btn.innerHTML = '<span class="rg-spin" aria-hidden="true"></span>' + (texto || 'Enviando…');
        } else if (estado === 'done') {
            btn.classList.remove('is-loading');
            btn.removeAttribute('aria-busy');
            btn.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>' + (texto || 'Listo');
        } else {
            btn.classList.remove('is-loading');
            btn.disabled = false;
            btn.removeAttribute('aria-busy');
            btn.innerHTML = btn.dataset.originalHtml;
        }
    };

    /* ── Alerta de nivel formulario ──────────────────────────────────────── */

    RG.alert = function (o) {
        var host = o.host;
        if (!host) return;
        if (!o.message) { host.innerHTML = ''; host.hidden = true; return; }
        var tipo = o.type || 'error';
        var icono = { error: 'fa-circle-exclamation', warn: 'fa-triangle-exclamation', ok: 'fa-circle-check', info: 'fa-circle-info' }[tipo] || 'fa-circle-info';
        host.hidden = false;
        host.innerHTML =
            '<div class="rg-note rg-note--' + tipo + '" role="alert">' +
            '<i class="fa-solid ' + icono + '" aria-hidden="true"></i>' +
            '<div>' + (o.title ? '<strong>' + o.title + '</strong><br>' : '') + o.message + '</div>' +
            '</div>';
        // scroll:false para refrescos en vivo — mover la página mientras el
        // usuario adjunta archivos sería más molesto que útil.
        if (o.scroll !== false) host.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    RG.syncChoices = syncChoices;

    window.RG = RG;

})(window, document);
