<?php
// Manuales de la plataforma para la Coordinación (rol admin): abre cualquiera de los PDF de
// config/manuales.php. Los demás perfiles abren el suyo con el botón "Manual" de su barra superior.
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ./");
    exit;
}
$manuales = require __DIR__ . '/../../config/manuales.php';
$iconos = [
    'empresa'      => 'fa-building',
    'alumno'       => 'fa-user-graduate',
    'encargado'    => 'fa-chalkboard-teacher',
    'coordinacion' => 'fa-shield-alt',
    'completo'     => 'fa-book',
];
?>
<style>
.mn-hero {
  background: linear-gradient(135deg, #c6db53 0%, #01643D 100%);
  border-radius: 1rem;
  color: #fff;
  padding: 1.75rem 2rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: 0 4px 18px rgba(0,32,74,.18);
}
.mn-hero-icon {
  width: 64px; height: 64px; border-radius: 50%;
  background: rgba(255,255,255,.15);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.8rem; flex-shrink: 0;
}
.mn-hero h2 { font-size: 1.4rem; font-weight: 700; margin: 0 0 .2rem; }
.mn-hero p  { margin: 0; opacity: .88; font-size: .92rem; }
.mn-card { border: 0; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,.07); transition: transform .2s, box-shadow .2s; }
.mn-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(1,100,61,.15); }
.mn-card .icon-box { width: 52px; height: 52px; border-radius: .75rem; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: rgba(1,100,61,.1); color: #01643D; flex-shrink: 0; }
.mn-card h5 { font-size: 1rem; font-weight: 700; color: #00204a; margin: 0 0 .35rem; }
.mn-card p  { font-size: .85rem; color: #6c757d; margin: 0; }
.btn-unimo { background: #01643D; border-color: #01643D; color: #fff; }
.btn-unimo:hover { background: #2A7E5D; border-color: #2A7E5D; color: #fff; }
</style>

<div class="container-fluid px-4 pt-3 pb-5">
    <div class="mn-hero">
        <div class="mn-hero-icon"><i class="fas fa-book-open"></i></div>
        <div>
            <h2>Manuales de Prácticas Profesionales</h2>
            <p>Cada perfil abre su manual desde el botón «Manual» de su panel. Aquí la Coordinación puede consultarlos todos.</p>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($manuales as $clave => $m): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card mn-card h-100 p-3">
                <div class="d-flex gap-3 h-100">
                    <div class="icon-box"><i class="fas <?= htmlspecialchars($iconos[$clave] ?? 'fa-file-pdf', ENT_QUOTES, 'UTF-8') ?>"></i></div>
                    <div class="d-flex flex-column flex-grow-1">
                        <h5><?= htmlspecialchars($m['titulo'], ENT_QUOTES, 'UTF-8') ?></h5>
                        <p class="flex-grow-1"><?= htmlspecialchars($m['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="mt-3">
                            <a class="btn btn-sm btn-unimo rounded-pill px-3"
                               href="controller/manuales.php?m=<?= urlencode($clave) ?>" target="_blank" rel="noopener">
                                <i class="fas fa-file-pdf me-1"></i> Abrir
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
