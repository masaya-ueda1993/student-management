<?php
// 生徒データの取得・検索

namespace MyApp;

class Student extends Utils
{
  private $_pdo;

  public function __construct($pdo)
  {
    $this->_pdo = $pdo;
    Token::create();
    $this->_errors = new \stdClass();
    $this->_values = new \stdClass();
  }

  // ＝＝＝＝＝　データ取得メソッド　＝＝＝＝＝

  // ページング + 検索処理関数
  public function getAllPaginated($limit, $offset, $class = '', $name = '')
  {
    // SQLの基本形を作成
    $sql = "SELECT * FROM students WHERE 1=1"; // 全ての生徒を取得（まだ絞り込みなし）// WHERE 1=1 常に真（ANDで条件を追加できる）
    $params = []; // プレースホルダーに値を保持する配列

    // 条件を追加
    if (!empty($class)) {
      $sql .= " AND class = :class"; // .=の意味：既存の文字列に追加（$sql = $sql . " AND class = :class"; と同じ）
      $params[':class'] = $class; // プレースホルダーに入れる値を保存
    }

    if (!empty($name)) {
      $sql .= " AND (last_name LIKE :name1 OR first_name LIKE :name2 OR last_name_kana LIKE :name3 OR first_name_kana LIKE :name4)";
      $params[':name1'] = '%' . $name . '%';
      $params[':name2'] = '%' . $name . '%';
      $params[':name3'] = '%' . $name . '%';
      $params[':name4'] = '%' . $name . '%';
    }

    $sql .= " ORDER BY class, class_no LIMIT :limit OFFSET :offset";
    /* ORDER BY class, class_no:
     ・クラス順、クラス番号順に並べる 
     ・A組1番、A組2番、...、B組1番、B組2番、...
     LIMIT :limit:
     ・取得する件数（例: 20件）
     OFFSET :offset:
     ・スキップする件数（例: 0件、20件、40件）
    */

    // SQLを実行
    $stmt = $this->_pdo->prepare($sql);

    // 検索条件を結合
    foreach ($params as $key => $value) {
      $stmt->bindValue($key, $value);
    }

    // ページング情報を結合
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

    // 実行後、結果をすべて配列で取得
    $stmt->execute();
    return $stmt->fetchAll();
  }

  // 検索条件に一致する生徒の総数を取得する関数
  public function countAll($class = '', $name = '')
  {
    $sql = "SELECT COUNT(*) FROM students WHERE 1=1";
    $params = [];

    if (!empty($class)) {
      $sql .= " AND class = :class";
      $params[':class'] = $class;
    }

    if (!empty($name)) {
      $sql .= " AND (last_name LIKE :name1 OR first_name LIKE :name2 OR last_name_kana LIKE :name3 OR first_name_kana LIKE :name4)";
      $params[':name1'] = '%' . $name . '%';
      $params[':name2'] = '%' . $name . '%';
      $params[':name3'] = '%' . $name . '%';
      $params[':name4'] = '%' . $name . '%';
    }

    $stmt = $this->_pdo->prepare($sql);

    foreach ($params as $key => $value) {
      $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    return $stmt->fetchColumn(); // fetchColumn()は1つだけの値を取得（例.150）/fetchはオブジェクトで取得するため、配列から取り出す必要がある（例.｛COUNT(*):150｝）
  }

  // 指定したIDの生徒1人だけ取得関数
  public function getById($id)
  {
    $stmt = $this->_pdo->prepare("SELECT * FROM students WHERE id = :id");
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch();
  }

  // ＝＝＝＝＝　POST処理メソッド　＝＝＝＝＝

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
        case 'add':
          // バリデーション
          $this->_validate();

          // エラーチェック
          if ($this->hasError()) {
            return;
          }

          // 生徒登録
          $studentId = $this->_add();

          // 成績データを自動作成（全テスト×全科目を0点で登録）
          $this->_createInitialScores($studentId);

          // 一覧画面にリダイレクト
          header('Location: ' . SITE_URL . '/student-list.php');
          exit;
       
        case 'update':
          // バリデーション
          $this->_validate();

          // エラーチェック
          if ($this->hasError()) {
            return;
          }

          // 生徒更新
          $this->_update();

          // 詳細画面にリダイレクト
          $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
          header('Location: ' . SITE_URL . '/student-detail.php?id=' . $id);
          exit;
        
        case 'delete':

          // 生徒削除
          $this->_delete();

          // 一覧画面の検索条件を取得（$_POSTから直接取得）
          $listClass = $_POST['list_class'] ?? '';
          $listName = $_POST['list_name'] ?? '';
          $listPage = $_POST['list_page'] ?? '';
          
          // ページ番号のバリデーション（空文字やnullの場合は1にする）
          if (is_numeric($listPage) &&  $listPage > 0) {
              $listPage = (int)$listPage;
          } else {
              $listPage = 1;
          }

          // 一覧画面にリダイレクト(検索条件を保持)
          $redirectUrl = SITE_URL . '/student-list.php?class=' . urlencode($listClass) . '&name=' . urlencode($listName) . '&page=' . $listPage;  
          header('Location: ' . $redirectUrl);
          exit;

        case 'delete_multiple':

          // 一括削除
          $this->_deleteMultiple();

          // 一覧画面にリダイレクト
          header('Location: ' . SITE_URL . '/student-list.php');
          exit;

        default:
          exit;
      }
    }
  }

  // ＝＝＝＝＝　バリデーションメソッド　＝＝＝＝＝

  // フォームから送信されたデータのバリデーションチェック関数
  private function _validate()
  {
    // ①値を取得
    $class = filter_input(INPUT_POST, 'class');
    $classNo = filter_input(INPUT_POST, 'class_no', FILTER_VALIDATE_INT);
    $lastName = filter_input(INPUT_POST, 'last_name');
    $firstName = filter_input(INPUT_POST, 'first_name');
    $lastNameKana = filter_input(INPUT_POST, 'last_name_kana');
    $firstNameKana = filter_input(INPUT_POST, 'first_name_kana');
    $gender = filter_input(INPUT_POST, 'gender', FILTER_VALIDATE_INT);
    $birthDate = filter_input(INPUT_POST, 'birth_date');

    // ②値を保持（エラー時に再表示するため）
    $this->setValues('class', $class);
    $this->setValues('class_no', $classNo);
    $this->setValues('last_name', $lastName);
    $this->setValues('first_name', $firstName);
    $this->setValues('last_name_kana', $lastNameKana);
    $this->setValues('first_name_kana', $firstNameKana);
    $this->setValues('gender', $gender);
    $this->setValues('birth_date', $birthDate);

    // ③値をチェック
    // クラス：必須
    if (empty($class)) {
      $this->setErrors('class', 'クラスを選択してください。');
    }

    // クラス番号：必須、1以上、クラスの重複チェック
    if (empty($classNo)) {
      $this->setErrors('class_no', 'クラス番号を入力してください。');
    } elseif ($classNo < 1 || $classNo > 50) {
      $this->setErrors('class_no', 'クラス番号は1～50の範囲で入力してください');
    } else {
      $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
      if ($this->_isClassNumberUsed($class, $classNo, $id)) {
        $this->setErrors('class_no', 'このクラス番号は既に使用されています。');
      }
    }

    // 姓：必須
    if (empty($lastName)) {
      $this->setErrors('last_name', '氏名（姓）を入力してください。');
    } elseif (mb_strlen($lastName) > 25) {
      $this->setErrors('last_name', '25文字以内で入力してください。');
    } elseif (!preg_match('/^[一-龥ぁ-んァ-ヶー]+$/u', $lastName)) {
      $this->setErrors('last_name', '漢字・ひらがな・カタカナで入力してください。');
    }

    // 名:必須
    if (empty($firstName)) {
      $this->setErrors('first_name', '氏名（名）を入力してください。');
    } elseif (mb_strlen($firstName) > 25) {
      $this->setErrors('first_name', '25文字以内で入力してください。');
    } elseif (!preg_match('/^[一-龥ぁ-んァ-ヶー]+$/u', $firstName)) {
      $this->setErrors('first_name', '漢字・ひらがな・カタカナで入力してください。');
    }

    // ふりがな（姓）：必須
    if (empty($lastNameKana)) {
      $this->setErrors('last_name_kana', 'ふりがな（姓）を入力してください。');
    } elseif (mb_strlen($lastNameKana) > 25) {
      $this->setErrors('last_name_kana', '25文字以内で入力してください。');
    } elseif (!preg_match('/^[ぁ-ん]+$/u', $lastNameKana)) {
      $this->setErrors('last_name_kana', 'ひらがなで入力してください。');
    }

    // ふりがな（名）：必須
    if (empty($firstNameKana)) {
      $this->setErrors('first_name_kana', 'ふりがな（名）を入力してください。');
    } elseif (mb_strlen($firstNameKana) > 25) {
      $this->setErrors('first_name_kana', '25文字以内で入力してください。');
    } elseif (!preg_match('/^[ぁ-ん]+$/u', $firstNameKana)) {
      $this->setErrors('first_name_kana', 'ひらがなで入力してください。');
    }

    // 性別:必須
    if (empty($gender)) {
      $this->setErrors('gender', '性別を選択してください。');
    } elseif ($gender !== GENDER_MALE && $gender !== GENDER_FEMALE) {
      $this->setErrors('gender', '性別の値が不正です。');
    }

    // 生年月日:必須
    if (empty($birthDate)) {
      $this->setErrors('birth_date', '生年月日を入力してください。。');
    } else {
      $dateParts = explode('-', $birthDate);
      if (count($dateParts) !== 3) {
        $this->setErrors('birth_date', '生年月日の形式が不正です。');
      } else {
          $year = (int)$dateParts[0];
          $month = (int)$dateParts[1];
          $day = (int)$dateParts[2];
          if (!checkdate($month, $day, $year)) {
            $this->setErrors('birth_date', '生年月日の形式が不正です。');
          }
        }
      }
  }

  // クラス番号が使用済みかチェック
  private function _isClassNumberUsed($class, $classNo, $excludeId = null)
  {
    // 値を整数に変換
    $classNo = (int)$classNo;

    if ($excludeId !== null && $excludeId !== false) {
      // 編集時：自分以外で同じクラス・番号があるかチェック
      $sql = "SELECT COUNT(*) FROM students WHERE class = :class AND class_no = :class_no AND id != :exclude_id";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':class', $class, \PDO::PARAM_STR);
      $stmt->bindValue(':class_no', $classNo, \PDO::PARAM_INT);
      $stmt->bindValue(':exclude_id', $excludeId, \PDO::PARAM_INT);
    } else {
      // 新規登録時：同じクラス・番号があるかチェック
      $sql = "SELECT COUNT(*) FROM students WHERE class = :class AND class_no = :class_no";
      $stmt = $this->_pdo->prepare($sql);
      $stmt->bindValue(':class', $class, \PDO::PARAM_STR);
      $stmt->bindValue(':class_no', $classNo, \PDO::PARAM_INT);
    }

    // 件数取得後、1件以上あれば使用済み
    $stmt->execute();
    $count = (int)$stmt->fetchColumn();
    return $count > 0;
  }

  // ＝＝＝＝＝　データ操作メソッド　＝＝＝＝＝

  // 共通のbindValueメソッド
  private function _bindStudentValue($stmt)
  {
    $stmt->bindValue(':class', $this->getValues()->class, \PDO::PARAM_STR);
    $stmt->bindValue(':class_no', $this->getValues()->class_no, \PDO::PARAM_INT);
    $stmt->bindValue(':last_name', $this->getValues()->last_name, \PDO::PARAM_STR);
    $stmt->bindValue(':first_name', $this->getValues()->first_name, \PDO::PARAM_STR);
    $stmt->bindValue(':last_name_kana', $this->getValues()->last_name_kana, \PDO::PARAM_STR);
    $stmt->bindValue(':first_name_kana', $this->getValues()->first_name_kana, \PDO::PARAM_STR);
    $stmt->bindValue(':gender', $this->getValues()->gender, \PDO::PARAM_INT);
    $stmt->bindValue(':birth_date', $this->getValues()->birth_date, \PDO::PARAM_STR);
  }

  // 写真のアップロード処理とbindValue（登録と更新で共通）
  private function _bindPhotoValue($stmt, $existingPhotoUrl = null)
  {
    // 画像アップロード処理（ファイル存在チェックはImageUploader内で行う）
    $uploader = new ImageUploader();
    $result = $uploader->upload();

    if ($result === null) {
      // ファイル未選択：既存の画像を保持（新規登録時はnull）
      if ($existingPhotoUrl !== null) {
        $stmt->bindValue(':photo_url', $existingPhotoUrl, \PDO::PARAM_STR);
      } else {
        $stmt->bindValue(':photo_url', null, \PDO::PARAM_NULL);
      }
    } elseif ($result === false) {
      // エラー：エラーメッセージを設定し、既存の画像を保持
      if ($existingPhotoUrl !== null) {
        $stmt->bindValue(':photo_url', $existingPhotoUrl, \PDO::PARAM_STR);
      } else {
        $stmt->bindValue(':photo_url', null, \PDO::PARAM_NULL);
      }
      $errors = $uploader->getErrors();
      foreach ($errors as $error) {
        $this->setErrors('photo', $error);
      }
    } else {
      // 成功：新しいファイル名を設定
      $stmt->bindValue(':photo_url', $result, \PDO::PARAM_STR);
    }
  }

  // 生徒登録
  private function _add()
  {
    $stmt = $this->_pdo->prepare(
      "INSERT INTO students (class, class_no, last_name, first_name, last_name_kana, first_name_kana, gender, birth_date, photo_url)
      VALUES (:class, :class_no, :last_name, :first_name, :last_name_kana, :first_name_kana, :gender, :birth_date, :photo_url)
      ");

    // 共通のbindValue処理
    $this->_bindStudentValue($stmt);

    // 写真のアップロード処理
    $this->_bindPhotoValue($stmt);

    // 実行とエラーハンドリング
    try {
      $stmt->execute();
      return $this->_pdo->lastInsertId();
    } catch (\PDOException $e) {
      echo $e->getMessage();
      exit;
    }
  }

  // 新規登録時に全テスト・科目の成績データ作成
  private function _createInitialScores($studentId)
  {
    $stmt = $this->_pdo->prepare(
      "INSERT INTO scores (student_id, test_id, subject_id)
       SELECT :student_id, t.id, sub.id
       FROM tests t
       CROSS JOIN subjects sub"
    );
    $stmt->bindValue(':student_id', $studentId, \PDO::PARAM_INT);
    $stmt->execute();
  }

  // 生徒更新
  private function _update()
  {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    $stmt = $this->_pdo->prepare(
      "UPDATE students SET
      class = :class,
      class_no = :class_no,
      last_name = :last_name,
      first_name = :first_name,
      last_name_kana = :last_name_kana,
      first_name_kana = :first_name_kana,
      gender = :gender,
      birth_date = :birth_date,
      photo_url = :photo_url
      WHERE id = :id
      ");
      
    // 更新対象の生徒をbind
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);

    // 共通のbindValue処理
    $this->_bindStudentValue($stmt);

    // 既存の写真URLを取得して、写真をアップロード処理とbindValue
    $studentData = $this->getById($id);
    $existingPhotoUrl = $studentData ? $studentData->photo_url : null;
    $this->_bindPhotoValue($stmt, $existingPhotoUrl);

    try {
      $stmt->execute();
    } catch (\PDOException $e) {
      echo $e->getMessage();
      exit;
    }
  }

  // 生徒削除（単体）
  private function _delete()
  {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    // idが存在するかチェック
    if (empty($id)) {
      return;
    }

    // 生徒データ削除（ON DELETE CASCADEにより成績データも自動削除）
    $stmt = $this->_pdo->prepare("DELETE FROM students WHERE id = :id");
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->execute();
  }

  // 生徒削除（一括）
  private function _deleteMultiple()
  {
    $ids = filter_input(INPUT_POST, 'id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

    if (empty($ids)) {
      return;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // 生徒データ削除（ON DELETE CASCADEにより成績データも自動削除）
    $stmt = $this->_pdo->prepare("DELETE FROM students WHERE id IN ($placeholders)");
    foreach ($ids as $index => $id) {
      $stmt->bindValue($index + 1, $id, \PDO::PARAM_INT);
    }
    $stmt->execute();
  }
}


