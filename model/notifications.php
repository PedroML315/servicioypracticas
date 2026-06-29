<?php

class Notifications
{
    /* ========= Helpers (privados) ========= */
    private static function db(): PDO
    {
        return Conexion::conectar();
    }

    /** Ejecuta y retorna PDOStatement (uso interno) */
    private static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** SELECT múltiples filas -> array asociativo + cierra cursor */
    private static function all(string $sql, array $params = []): array
    {
        $stmt = self::run($sql, $params);
        $res  = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); $stmt = null;
        return $res;
    }

    /** SELECT una fila -> array|false + cierra cursor */
    private static function one(string $sql, array $params = [])
    {
        $stmt = self::run($sql, $params);
        $res  = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); $stmt = null;
        return $res;
    }

    /** SELECT una columna -> mixed + cierra cursor */
    private static function col(string $sql, array $params = [])
    {
        $stmt = self::run($sql, $params);
        $res  = $stmt->fetchColumn();
        $stmt->closeCursor(); $stmt = null;
        return $res;
    }

    /** UPDATE/INSERT/DELETE -> filas afectadas (rowCount) + cierra cursor */
    private static function aff(string $sql, array $params = []): int
    {
        $stmt = self::run($sql, $params);
        $n    = $stmt->rowCount();
        $stmt->closeCursor(); $stmt = null;
        return $n;
    }

    /** Ejecuta y retorna el bool de execute() (para NO cambiar la semántica) */
    private static function execOK(string $sql, array $params = []): bool
    {
        $stmt = self::db()->prepare($sql);
        $ok   = $stmt->execute($params); // mismo comportamiento que tu código original
        $stmt->closeCursor(); $stmt = null;
        return $ok;
    }

    /* ========== API pública (igual que la tuya) ========== */

    public static function addNotification($recipient_id, $recipient_type, $message, $url = null, $data = null, $notification_type = null, $priority = 1)
    {
        $sql = "INSERT INTO notifications
            (recipient_id, recipient_type, message, url, data, notification_type, priority, is_read, created_at)
            VALUES
            (:recipient_id, :recipient_type, :message, :url, :data, :notification_type, :priority, 0, NOW())";

        // Devuelve bool como antes (execute())
        return self::execOK($sql, [
            ':recipient_id'     => $recipient_id,
            ':recipient_type'   => $recipient_type,
            ':message'          => $message,
            ':url'              => $url,
            ':data'             => $data,
            ':notification_type'=> $notification_type,
            ':priority'         => (int) $priority,
        ]);
    }

    public static function getNotifications($recipient_id, $recipient_type, $limit = 10)
    {
        $sql = "SELECT * FROM notifications
                WHERE recipient_id = :recipient_id AND recipient_type = :recipient_type
                ORDER BY created_at DESC
                LIMIT :limit";

        // Para LIMIT como entero estrictamente
        $stmt = self::db()->prepare($sql);
        $stmt->bindValue(':recipient_id', (int)$recipient_id, PDO::PARAM_INT);
        $stmt->bindValue(':recipient_type', $recipient_type, PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); $stmt = null;
        return $res;
    }

    public static function markAsRead($notification_id)
    {
        // Debe seguir devolviendo el bool de execute()
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = :id";
        return self::execOK($sql, [':id' => (int)$notification_id]);
    }

    public static function deleteNotification($notification_id)
    {
        // Debe seguir devolviendo el bool de execute()
        $sql = "DELETE FROM notifications WHERE id = :id";
        return self::execOK($sql, [':id' => (int)$notification_id]);
    }

    public static function getUnreadCount($recipient_id, $recipient_type)
    {
        $sql = "SELECT COUNT(*) FROM notifications
                WHERE recipient_id = :recipient_id AND recipient_type = :recipient_type AND is_read = 0";
        return (int) self::col($sql, [
            ':recipient_id'   => (int)$recipient_id,
            ':recipient_type' => $recipient_type,
        ]);
    }

    public static function getNotificationById($notification_id)
    {
        $sql = "SELECT * FROM notifications WHERE id = :id";
        return self::one($sql, [':id' => (int)$notification_id]);
    }

    public static function deleteAllNotifications($recipient_id, $recipient_type)
    {
        // Debe seguir devolviendo el bool de execute()
        $sql = "DELETE FROM notifications WHERE recipient_id = :recipient_id AND recipient_type = :recipient_type";
        return self::execOK($sql, [
            ':recipient_id'   => (int)$recipient_id,
            ':recipient_type' => $recipient_type,
        ]);
    }
}
