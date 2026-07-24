<?php

/**
 * OBSOLETO desde la FASE 6.
 *
 * La carta de presentación ya NO tiene fecha de vigencia, por lo que este proceso
 * de expiración quedó sin efecto. Se conserva como no-op para no romper tareas
 * programadas existentes que aún lo invoquen. Puede retirarse del cron.
 *
 * Reemplazo: controller/cron/cron_retroalimentacion_entrevista.php
 */

echo "cron_expirar_cartas.php está OBSOLETO desde la Fase 6 (la carta ya no tiene vigencia). "
   . "No se realizó ninguna acción.\n";
exit;
