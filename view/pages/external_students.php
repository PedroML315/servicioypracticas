<?php
// Esta página es un alias de students.php mostrando el tab externo directamente
$_role        = $_SESSION['user']['role'] ?? '';
$_teacherName = htmlspecialchars($_SESSION['user']['nombre_completo'] ?? '', ENT_QUOTES, 'UTF-8');
$_initials    = strtoupper(implode('', array_map(fn($w) => substr($w, 0, 1), array_slice(explode(' ', $_teacherName), 0, 2))));
?>
<script>
// Redirigir a la página de students con el tab externo activo
window.location.href = 'students#tab=externo';
</script>
<!-- Fallback: si JS está deshabilitado, mostramos un enlace -->
<div style="padding:2rem; text-align:center;">
  <p>Redirigiendo a <a href="students#tab=externo">Estudiantes Externos</a>…</p>
</div>
