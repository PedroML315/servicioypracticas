<?php
require_once __DIR__ . '/../model/conection.php';
$db = Conexion::conectar();
$stmt = $db->query("DESCRIBE email_templates");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
