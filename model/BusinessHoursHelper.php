<?php

require_once __DIR__ . '/conection.php';

class BusinessHoursHelper
{
    /**
     * Calcula la fecha de vencimiento: 00:00:00 del tercer día hábil
     * dando al alumno el día hábil completo #2 para presentarse.
     * Omite sábados, domingos y días festivos de dias_festivos.
     */
    public static function calcularVencimientoCarta(DateTimeImmutable $fechaGeneracion): DateTimeImmutable
    {
        date_default_timezone_set('America/Mexico_City');

        // Cargar días festivos activos
        $festivosStr = self::getFestivos();

        // Partir desde el día de generación
        $currentDate = clone $fechaGeneracion;
        $diasHabilesEncontrados = 0;

        // Avanzar hasta encontrar 2 días hábiles (el día de hoy cuenta si es hábil)
        while ($diasHabilesEncontrados < 2) {
            if (self::esDiaHabil($currentDate, $festivosStr)) {
                $diasHabilesEncontrados++;
            }
            if ($diasHabilesEncontrados < 2) {
                $currentDate = $currentDate->modify('+1 day');
            }
        }

        // El vencimiento es el final (23:59:59) del 2do día hábil
        $vencimiento = $currentDate->setTime(23, 59, 59);

        return $vencimiento;
    }

    private static function getFestivos(): array
    {
        try {
            $pdo = Conexion::conectar();
            $stmt = $pdo->prepare("SELECT fecha FROM dias_festivos WHERE is_active = 1");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $result ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    private static function esDiaHabil(DateTimeImmutable $date, array $festivos): bool
    {
        // 1 (Monday) to 7 (Sunday)
        $dayOfWeek = (int) $date->format('N');
        if ($dayOfWeek === 6 || $dayOfWeek === 7) {
            return false; // Sábado o Domingo
        }

        $dateStr = $date->format('Y-m-d');
        if (in_array($dateStr, $festivos)) {
            return false; // Día festivo
        }

        return true;
    }
}
