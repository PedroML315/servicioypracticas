<?php

#autoload composer
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class Conexion
{

  /**
   * Conecta a la base de datos usando MySQL.
   *
   * @return PDO
   */
  static public function conectar()
  {
    $host = $_ENV['DB_HOST'];
    $db = $_ENV['DB_DATABASE'];
    $user = $_ENV['DB_USERNAME'];
    $pass = $_ENV['DB_PASSWORD'];

    $link = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $link->exec("set names utf8");

    return $link;
  }

  /**
   * Conecta a la base de datos de SIL usando SQL Server.
   *
   * @return PDO|null
   */
  static public function conectarSIL()
  {
    $serverName = $_ENV['SIL_DB_HOST'];
    $database = $_ENV['SIL_DB_DATABASE'];
    $username = $_ENV['SIL_DB_USERNAME'];
    $password = trim($_ENV['SIL_DB_PASSWORD'], '"'); // Quita comillas si existen

    try {
      $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $conn;
    } catch (PDOException $e) {
      echo "Connection failed: " . $e->getMessage();
    }
  }

  /**
   * Conecta a la base de datos de SIL usando SQL Server.
   *
   * @return PDO|null
   */
  static public function conectarGES()
  {
    $serverName = $_ENV['SIL_DB_HOST'];
    $database = $_ENV['SIL_DB_DATABASE_GES'];
    $username = $_ENV['SIL_DB_USERNAME'];
    $password = trim($_ENV['SIL_DB_PASSWORD'], '"'); // Quita comillas si existen

    try {
      $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $conn;
    } catch (PDOException $e) {
      echo "Connection failed: " . $e->getMessage();
    }
  }

}