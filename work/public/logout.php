<?php 

require_once(__DIR__ . '/../app/Config.php');

use MyApp\Token;

// POSTリクエストのチェック
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // CSRF検証（バリデーション）
  Token::validate();

  // セッションに保存されているデータを空の配列で上書き削除
  $_SESSION = [];

  // クッキーの削除
  if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
  }

  // サーバー側に保存されているセッションファイルを破棄
  session_destroy();
}

header('Location: ' . SITE_URL . '/login.php');

?>
