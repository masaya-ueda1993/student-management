<?php

namespace MyApp;

class Database {

  private static $instance;

  public static function getInstance() {
    try {
      if (!isset(self::$instance)) {
        // インスタンスが設定されていない場合、新しく接続を作る
        self::$instance = new \PDO(
          DSN,
          DB_USER,
          DB_PASS,
          [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_EMULATE_PREPARES => false,
          ]
        );
      }

      // 作成した（または既に作られている）データベース接続を返す
      return  self::$instance;

    } catch (\PDOException $e) {
        echo $e->getMessage();
        exit;
    }
  }
}
