<?php
// 定数や設定をまとめて管理

// セッション開始
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// session_status() = 現在のセッションの状態を取得する関数
// PHP_SESSION_NONE = セッション機能は有効だが、まだ開始していない


// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');


// エラー表示
// APP_ENV = 動作環境（未設定なら development として扱う）
$appEnv = getenv('APP_ENV') ?: 'development';
if ($appEnv === 'production') {
  // 本番：エラーは画面に出さず、ログにだけ記録する
  ini_set('display_errors', 0);
  ini_set('log_errors', 1);
} else {
  ini_set('display_errors', 1);
}
// ini_set('設定名', '値')
// display_errors = エラーをブラウザに表示するかどうか
// log_errors = エラーをログに記録するかどうか


// データベース接続
define('DSN', 'mysql:host=db;dbname=scoremanager;charset=utf8mb4');
define('DB_USER', 'smuser');
define('DB_PASS', 'smpass');

define('SITE_URL', 'http://' .$_SERVER['HTTP_HOST']); // サイトURLの取得
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // ファイルサイズ 1MB
define('UPLOAD_DIR', '/work/public/uploads/'); // ファイルパス
define('STUDENTS_PER_PAGE', 20); // 生徒一覧：1ページ20人まで表示
define('TESTS_PER_PAGE', 5); // 生徒詳細：テスト5件まで表示
define('GENDER_MALE', 1);
define('GENDER_FEMALE', 2);

// クラスの自動読み込み
spl_autoload_register(function($class) {
  // 名前空間を除去
    $class = str_replace('MyApp\\', '', $class);

    // クラスファイルのパス
    $file = __DIR__ . '/' . $class . '.php';

    // ファイルが存在すれば読み込む
    if (file_exists($file)) {
      require_once $file;
    }
});

// str_replace('検索文字列', 置換文字列, 対象文字列)
// 例：$class = 'MyApp\Student';
// 　　$class = str_replace('MyApp\\', '', $class);
// 　　'Student'
