<?php 

namespace MyApp;

class ImageUploader 
{

  private $_errors = [];

  // インスタンス生成時にエラー配列を初期化
  // データベースを介さないため引数なし
  public function __construct()
  {
    $this->_errors = [];
  }

  // アップロード処理のメイン関数
  public function upload()
  {
    try {
      // 1. ファイルが送信されているかチェック
      if (!$this->_hasFile()) {
        return null;
      }

      // 2.アップロードエラーをチェック
      $this->_validateUpload();

      // 3.画像の形式チェックし、拡張子を取得
      $ext = $this->_validateImageType();

      // 4.ファイルを保存し、ファイル名を返す
      return $this->_save($ext);

    } catch (\Exception $e) {
      // エラーが発生した場合、エラーメッセージを保存
      $this->_errors[] = $e->getMessage();
      return false;
    }
  }

  // エラーメッセージを取得
  public function getErrors()
  {
    return $this->_errors;
  }

  // ファイルが送信されているかチェックする関数
  private function _hasFile()
  {
    // $_FILES['photo']が存在しない場合はファイル未送信
    if (!isset($_FILES['photo'])) {
      return false;
    }

    // エラーコードが設定されていない場合はファイル未送信
    if (!isset($_FILES['photo']['error'])) {
      return false;
    }

    // UPLOAD_ERR_NO_FILEの場合はファイル未選択（ユーザーが写真を選ばなかった）
    if ($_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
      return false;
    }

    // 上記以外はファイルが送信されている
    return true;
  }
  
  // アップロードエラーの確認
  private function _validateUpload()
  {
    // PHPのアップロードエラーコードをチェック
    switch ($_FILES['photo']['error']) {
      // エラーなし：正常にアップロードされた
      case UPLOAD_ERR_OK:
        // ファイルサイズが上限（MAX_FILE_SIZE）を超えていないかチェック
        if ($_FILES['photo']['size'] > MAX_FILE_SIZE) {
          throw new \Exception('ファイルが大きすぎます。5MB以下のファイルを選択してください。');
        }
        return true;
      
      // ファイルサイズが大きすぎる
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        throw new \Exception('ファイルが大きすぎます。5MB以下のファイルを選択してください。');
      
      // その他エラー
      default:
        throw new \Exception('ファイルのアップロードに失敗しました。エラーコード: ' . $_FILES['photo']['error']);
    }
  }

  // 画像形式をチェック
  private function _validateImageType()
  {
    // 画像の形式を取得
    $imagetype = \exif_imagetype($_FILES['photo']['tmp_name']);

    // 画像の形式に応じて拡張子を返す
    switch ($imagetype) {
      case IMAGETYPE_JPEG:
        return 'jpg';
      
      case IMAGETYPE_PNG:
        return 'png';
      
      case IMAGETYPE_GIF:
        return 'gif';
      
      // 対応していない形式の場合のエラー
      default:
        throw new \Exception('対応していない画像形式です。JPEG、PNG、GIFのいずれかを選択してください。');
    }
  }

  // ファイルの保存処理
  private function _save($ext)
  {
    // ファイル名を生成
    $fileName = $this->_generateFileName($ext);

    // 保存先のフルパスを作成
    $savePath = UPLOAD_DIR . $fileName;

    // アップロードされた一時ファイルを指定の場所に移動
    $result = move_uploaded_file($_FILES['photo']['tmp_name'], $savePath);

    // 移動に失敗した場合はエラー
    if ($result === false) {
      throw new \Exception('ファイルの保存に失敗しました。');
    }

    // 成功した場合はファイル名を返す
    return $fileName;
  }

  // ファイル名を生成
  private function _generateFileName($ext)
  {
    // 現在のUnixタイムスタンプ（秒単位）
    $timestamp = time();

    // ランダムの文字列を生成
    $randomString = bin2hex(random_bytes(16));

    // タイムスタンプ_ランダム文字列.拡張子 の形式で返す
    return sprintf('%s_%s.%s', $timestamp,$randomString,$ext);
  }
}
