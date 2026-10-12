<?php

#autoload composer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Environment.php';

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

    Environment::assertDatabaseAllowed($db);

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
    // En pruebas apunta a SIL_TEST_DB_HOST o se niega a conectar (ver Environment::silServer)
    $sil = Environment::silServer();

    try {
      $conn = new PDO("sqlsrv:Server={$sil['host']};Database={$sil['database']}{$sil['dsn_options']}", $sil['username'], $sil['password']);
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
    // En pruebas apunta a SIL_TEST_DB_HOST o se niega a conectar (ver Environment::silServer)
    $sil = Environment::silServer();

    try {
      $conn = new PDO("sqlsrv:Server={$sil['host']};Database={$sil['database_ges']}{$sil['dsn_options']}", $sil['username'], $sil['password']);
      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $conn;
    } catch (PDOException $e) {
      echo "Connection failed: " . $e->getMessage();
    }
  }

}