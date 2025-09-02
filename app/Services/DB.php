<?php
namespace App\Services;
use PDO;

class DB {
  private static ?PDO $pdo = null;
  public static function conn(): PDO {
    if (!self::$pdo) {
      // XAMPP par défaut
      $host='127.0.0.1'; $name='biome_univers'; $user='root'; $pass='';
      $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
      self::$pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      ]);
    }
    return self::$pdo;
  }
}
