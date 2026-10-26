<?php

namespace MyApp;

class User extends Utils {
  
  private $_pdo;

  // コンストラクタ＝クラスがインスタンス化されるときに自動手実行される関数
  public function __construct($pdo)
  {
    // データベース接続を保存
    $this->_pdo = $pdo;

    // CSRFトークンを生成
    Token::create();

    // エラー格納用オブジェクトを初期化
    $this->_errors = new \stdClass();

    // 入力値格納用オブジェクトを初期化
    $this->_values = new \stdClass();
  }

  // POSTリクエストの処理関数
  public function processPost() 
  {
    // POSTリクエストかチェック
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      
      // CSRFトークンをチェック
      Token::validate();

      // アクションを取得
      $action = filter_input(INPUT_GET, 'action');

      // アクションに応じて処理を分岐
      switch ($action) {

        case 'login':
          // バリデーション（値）チェック
          $this->_validate();
          
          // エラーがあれば中断
          if ($this->hasError()) {
            return;
          }
          
          // ログイン処理
          $this->_login();

          // ログイン失敗した場合は中断
          if ($this->hasError()) {
            return;
          }

          // ログイン成功したらリダイレクトし、生徒リストへ移動
          header('Location: ' . SITE_URL . '/student-list.php');
          exit;

          default:
          exit;
      }
    }
  }

  // ユーザーが入力した値のチェック関数
  private function _validate()
  {
    // FILTER_SANITIZE_EMAIL = メアドから不正な文字を取り除く
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL); 
    $password = filter_input(INPUT_POST, 'password');

    $this->setValues('email', $email);
    $this->setValues('password', $password);

    if (empty($email)) {
      $this->setErrors('email', 'メールアドレスは必須です。');
      
      // filter_var() = 値をフィルタリング・検証する関数
      // FILTER_VALIDATE_EMAIL = メールアドレスの形式チェック
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $this->setErrors('email', 'メールアドレスの形式が正しくありません。');
    }

    if (empty($password)) {
      $this->setErrors('password', 'パスワードは必須です。');
    }
  }

  // データベースでユーザーを確認して、ログイン処理を行う関数
  private function _login()
  {
    // 入力したメールアドレス（データ）がuserにあるかを確認
    $stmt = $this->_pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindValue(':email', $this->getValues()->email, \PDO::PARAM_STR);
    $stmt->execute();
    // setFetchMode() = データの取得形式を設定する
    // PDO::FETCH_CLASS = データをオブジェクトとして取得する
    $stmt->setFetchMode(\PDO::FETCH_CLASS, 'stdClass');
    $user = $stmt->fetch();

    // パスワードが同一かを確認
    if (!$user || !password_verify($this->getValues()->password, $user->password)) {
      $this->setErrors('login', 'メールアドレスまたはパスワードが正しくありません。');
      return false;
    }

    // 認証成功したら、セッション値を格納
    session_regenerate_id(true); // セッションIDを新しく生成し直す。true = 古いセッションを削除
    $_SESSION['me'] = $user;
  }

  // 現在ログインしているユーザー情報を取得する関数
  public function me()
  {
    return $_SESSION['me'] ?? null;
  }
}
