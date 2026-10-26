<?php

namespace MyApp;

class Token {

  // トークンを生成
  public static function create() {
    if (!isset($_SESSION['token'])) {
      $_SESSION['token'] = bin2hex(random_bytes(32));
    }
  }

  // トークンチェック（フォームとセッション）
  public static function validate() {
    // hash_equals() = 2つの文字列を、比較にかかる時間から中身を推測されない方法で比べる
    if (empty($_SESSION['token']) || !hash_equals($_SESSION['token'], (string)filter_input(INPUT_POST, 'token'))) { 
      exit('Invalid post request');
    }
  }
}
