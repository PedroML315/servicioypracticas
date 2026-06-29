<?php 

class EmailsModel {
    public static function getMailTemplates(): array {
        $sql = "SELECT * FROM email_templates ORDER BY name ASC";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute();

        $response = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response;
    }

    public static function updateMailTemplate(int $id, string $subject, string $html, string $text_plain, int $idUser): bool {
        $sql = "UPDATE email_templates SET subject = :subject, html = :html, text_plain = :text_plain, updated_by = :id_user WHERE id = :id";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
        $stmt->bindParam(':html', $html, PDO::PARAM_STR);
        $stmt->bindParam(':text_plain', $text_plain, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':id_user', $idUser, PDO::PARAM_INT);
        $result = $stmt->execute();
        $stmt->closeCursor();
        $stmt = null;
        return $result;
    }

    static public function getMailTemplateByKey(string $key): ?array {
        $sql = "SELECT * FROM email_templates WHERE tkey = :key";
        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->bindParam(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $stmt = null;
        return $response ?: null;
    }
}