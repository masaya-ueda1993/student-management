<?php

namespace MyApp;

class Utils {
  protected $_errors; // エラー値格納用
  protected $_values; // 入力値格納用

  // HTMLエスケープ処理
  public static function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
  }

  // エラーをセット
  protected function setErrors($key, $error) {
    $this->_errors->$key = $error;
  }
  
  // エラーを取得
  public function getErrors($key) {
    return isset($this->_errors->$key) ? $this->_errors->$key : '';
  }

  // ユーザーが入力した値をセット
  protected function setValues($key, $value) {
    $this->_values->$key = $value;
  }

  // ユーザーの入力した値を取得
  public function getValues() {
    return $this->_values;
  }

  // エラーが存在するかチェック
  protected function hasError() {
    return !empty(get_object_vars($this->_errors));
  }

  // ログインしているかチェック
  public function isLoggedIn() {
    return isset($_SESSION['me']) && !empty($_SESSION['me']);
  }
}
