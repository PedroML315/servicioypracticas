<?php
require_once 'config/menu.php';

// --------------------------------------
// Helpers
// --------------------------------------
function esc(string $text): string
{
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function isActive(string $href, string $currentPage): string
{
  return $href === $currentPage ? 'active disabled' : '';
}

function renderMenuItem(array $item, string $currentPage): string
{
  if ($item['type'] === 'link') {
    return sprintf(
      '<a href="%1$s" class="menu-top py-2 %2$s">
                <div class="row">
                  <div class="col-2"><i class="%3$s"></i></div>
                  <div class="col-10">%4$s</div>
                </div>
             </a>',
      esc($item['href']),
      isActive($item['href'], $currentPage),
      esc($item['icon']),
      esc($item['text'])
    );
  }

  // Dropdown
  $toggle = sprintf(
    '<button class="btn menu-top dropdown-toggle w-100" id="%1$s" data-bs-toggle="dropdown" aria-expanded="false" style="align-items: center; display: flex;">
             <i class="%2$s me-2"></i>%3$s
         </button>',
    esc($item['id']),
    esc($item['icon']),
    esc($item['text'])
  );

  $options = '';
  foreach ($item['items'] as $sub) {
    $options .= sprintf(
      '<li><a class="dropdown-item %1$s" href="%2$s">
                <i class="%3$s me-2"></i>%4$s
             </a></li>',
      isActive($sub['href'], $_GET['pagina'] ?? 'inicio'),
      esc($sub['href']),
      esc($sub['icon']),
      esc($sub['text'])
    );
  }

  return sprintf(
    '<div class="dropdown mt-1">%1$s<ul class="dropdown-menu w-100" aria-labelledby="%2$s">%3$s</ul></div>',
    $toggle,
    esc($item['id']),
    $options
  );
}

// --------------------------------------
// Lógica
// --------------------------------------
$user = $_SESSION['user'] ?? [];
$currentPage = isset($_GET['pagina']) ? htmlspecialchars($_GET['pagina'], ENT_QUOTES, 'UTF-8') : 'inicio';

// Manejo seguro de tipo_servicio
$tipoServicio = $user['tipo_servicio'] ?? null;
$typeAdmin = is_array($tipoServicio) ? ($tipoServicio['type_admin'] ?? null) : null;

if (($user['role'] ?? null) === 'admin' && $typeAdmin === 1) {
  $role = 'admin_servicio';
} elseif (($user['role'] ?? null) === 'admin' && $typeAdmin === 2) {
  $role = 'admin_practicas';
} else {
  $role = $user['role'] ?? '';
  if (in_array($role, ['alumno_practicas', 'student'], true)) {
    $role = 'other';
  }
}

// Preserve the real role for layout decisions
$rawRole = $user['role'] ?? '';
$userType = $user['type'] ?? '';
$isStudentInterno  = ($rawRole === 'student' && $userType === 'universidad');
$isStudentExterno  = ($rawRole === 'student' && $userType !== 'universidad');

// Evita offsets/índices inexistentes si constantes no cubren el rol
$roleLabel  = ROLE_LABELS[$role] ?? (ROLE_LABELS['default'] ?? 'Usuario');
$menuItems  = MENU_CONFIG[$role] ?? MENU_CONFIG['default'] ?? [];
$hiddenInputs = [];

// Campos ocultos según rol
switch ($role) {
  case 'student':
    $hiddenInputs['idStudent'] = $user['idStudent'] ?? '';
    if (($user['type'] ?? '') === 'universidad') {
      $hiddenInputs['loginOn'] = $user['loginOn'] ?? '';
    }
    break;
  case 'alumno_practicas':
    $hiddenInputs['idStudent'] = $user['id'] ?? '';
    break;
  case 'organismo_externo':
    // no additional IDs
    break;
  default:
    $hiddenInputs['idUser'] = $user['id'] ?? '';
    $hiddenInputs['idArea'] = $user['idArea'] ?? '';
    break;
}

// Nombre a mostrar
if ($role === 'alumno_practicas') {
  $displayName = esc($user['nombre_completo'] ?? '');
} elseif ($role === 'organismo_externo') {
  $displayName = esc($user['empresa'] ?? '') . '<br>' . esc($user['nombre_contacto'] ?? '');
} else {
  $displayName = esc(trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')));
}
?>
<?php if ($role === 'organismo_externo' || $role === 'teacher' || $isStudentInterno || $isStudentExterno || $rawRole === 'alumno_practicas'): ?>
<!-- ─────────────────────────────────────────────────────
     Layout especial: sin sidebar (organismo_externo / teacher / student)
───────────────────────────────────────────────────── -->
<style>
  /* Ocultar header/navbar estándar para organismo_externo */
  body > header { display: none !important; }

  /* Top bar organismo */
  .org-topbar {
    position: sticky;
    top: 0;
    z-index: 1050;
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    padding: .65rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  .org-topbar .org-logo {
    height: 36px;
    object-fit: contain;
  }
  .org-topbar .org-divider {
    width: 1px;
    height: 28px;
    background: #dee2e6;
  }
  .org-topbar .org-name {
    font-size: .88rem;
    font-weight: 600;
    color: #212529;
    line-height: 1.2;
  }
  .org-topbar .org-role-badge {
    background: rgba(1,100,61,.1);
    color: #01643D;
    border-radius: 1rem;
    padding: .2rem .7rem;
    font-size: .74rem;
    font-weight: 600;
  }
  .org-topbar .ms-auto { margin-left: auto !important; }
  .org-topbar .btn-logout-top {
    background: transparent;
    border: 1.5px solid #dee2e6;
    border-radius: .5rem;
    padding: .35rem .85rem;
    font-size: .82rem;
    color: #6c757d;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: .4rem;
    transition: all .2s;
  }
  .org-topbar .btn-logout-top:hover {
    border-color: #dc3545;
    color: #dc3545;
    background: rgba(220,53,69,.05);
  }

  /* Full-width main */
  .org-main-content {
    min-height: calc(100vh - 58px);
    background: #f4f6f8;
    /* padding: 1.5rem; */
  }
</style>

<div class="org-topbar">
  <a href="./">
    <img src="view/assets/images/logo-color.png" alt="UNIMO" class="org-logo">
  </a>
  <div class="org-divider"></div>
  <div style="line-height:1.3;">
    <?php if ($role === 'organismo_externo'): ?>
      <?php
        $empresa  = esc($user['empresa'] ?? '');
        $contacto = esc($user['nombre_contacto'] ?? '');
      ?>
      <div class="org-name"><?= $empresa ?></div>
      <?php if ($contacto): ?>
        <div style="font-size:.76rem;color:#6c757d;"><?= $contacto ?></div>
      <?php endif ?>
    <?php elseif ($rawRole === 'alumno_practicas'): ?>
      <div class="org-name"><?= esc($user['nombre_completo'] ?? '') ?></div>
      <?php if (!empty($user['programa_academico'])): ?>
        <div style="font-size:.76rem;color:#6c757d;"><?= esc($user['programa_academico']) ?></div>
      <?php endif ?>
    <?php else: ?>
      <div class="org-name"><?= $displayName ?></div>
    <?php endif ?>
  </div>
  <?php if ($role === 'organismo_externo'): ?>
    <span class="org-role-badge"><i class="fas fa-building me-1"></i>Organismo Externo</span>
  <?php elseif ($isStudentInterno): ?>
    <span class="org-role-badge" style="background:rgba(141,185,74,.12);color:#01643D;"><i class="fas fa-user-graduate me-1"></i>Servicio Social Interno</span>
  <?php elseif ($isStudentExterno): ?>
    <span class="org-role-badge" style="background:rgba(1,100,61,.12);color:#01643D;"><i class="fas fa-university me-1"></i>Servicio Social Externo</span>
  <?php elseif ($rawRole === 'alumno_practicas'): ?>
    <span class="org-role-badge" style="background:rgba(124,58,237,.12);color:#6d28d9;"><i class="fas fa-briefcase me-1"></i>Prácticas Profesionales</span>
  <?php else: ?>
    <span class="org-role-badge"><i class="fas fa-chalkboard-teacher me-1"></i>Encargado</span>
  <?php endif ?>
  <?php if ($role === 'teacher'): ?>
  <nav class="d-flex gap-1 ms-3">
    <?php foreach ($menuItems as $item): ?>
      <?php if ($item['type'] === 'link'): ?>
        <a href="<?= esc($item['href']) ?>"
           class="btn btn-sm <?= ($currentPage === $item['href']) ? 'btn-primary' : 'btn-outline-secondary' ?>"
           style="font-size:.8rem;padding:.25rem .65rem;">
          <i class="<?= esc($item['icon']) ?> me-1"></i><?= esc($item['text']) ?>
        </a>
      <?php endif ?>
    <?php endforeach ?>
  </nav>
  <?php endif ?>
  <div class="ms-auto d-flex align-items-center gap-2">
    <input type="hidden" id="role" value="<?= esc($rawRole) ?>">
    <?php foreach ($hiddenInputs as $id => $val): ?>
      <input type="hidden" id="<?= esc($id) ?>" value="<?= esc((string) $val) ?>">
    <?php endforeach; ?>
    <button class="btn-logout-top" onclick="logout()">
      <i class="fas fa-sign-out-alt"></i> Cerrar sesión
    </button>
  </div>
</div>

<div class="org-main-content">
  <!-- CONTENIDO DE PÁGINA ORGANISMO -->

<?php else: ?>
<!-- --------------------------------------
     HTML: Sidebar estándar
-------------------------------------- -->
<div class="row">
  <aside class="col-2 px-0 sidebar sidebar-collapse pt-2 d-flex flex-column h-100">
    <div class="px-3">
      <a href="./" class="icon-header-logo">
        <img src="view/assets/images/logo-color.png" alt="Logo" class="logo">
      </a>

      <nav class="mt-4">
        <div class="mb-2"><?= esc($roleLabel) ?></div>
        <h6><?= $displayName ?></h6>

        <?php foreach ($hiddenInputs as $id => $val): ?>
          <input type="hidden" id="<?= esc($id) ?>" value="<?= esc((string) $val) ?>">
        <?php endforeach; ?>

        <div class="row schools">
          <?php foreach ($menuItems as $item): ?>
            <?= renderMenuItem($item, $currentPage) ?>
          <?php endforeach; ?>
        </div>
      </nav>
    </div>

    <footer class="px-3 mt-auto">
      <ul class="navbar-nav w-100">
        <li class="d-grid gap-2">
          <button class="btn-logout mt-3 py-2" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
          </button>
        </li>
      </ul>
    </footer>
  </aside>

  <div class="col-lg-3 col-xl-2"></div>
  <main class="col-12 col-lg-9 col-xl-10 p-4 row">
    <!-- CONTENIDO DE PÁGINA -->
<?php endif; ?>