<?php
// 成績データの取得・更新・削除・POST処理

namespace MyApp;

class Score 
{
  private $_pdo;

  // コンストラクタ
  public function __construct($pdo)
  {
    // データベース接続を保存
    $this->_pdo = $pdo;
  }

// ＝＝＝＝＝　データ取得メソッド　＝＝＝＝＝

 // 指定した生徒IDの成績データの総数（テスト数）を取得する関数
 public function countByStudentId($studentId)
 {
  $stmt = $this->_pdo->prepare(
    "SELECT COUNT(DISTINCT test_id) FROM  scores WHERE student_id = :student_id"
  );
  
  $stmt->bindValue(':student_id', $studentId, \PDO::PARAM_INT);
  $stmt->execute();
  return (int)$stmt->fetchColumn();
 }

  // 指定した生徒の成績データをテストごとにグループ化して取得する関数
  public function getByStudentId($studentId, $limit, $offset)
  {
    $stmt = $this->_pdo->prepare(
      "SELECT 
        s.id,
        s.test_id,
        s.subject_id,
        s.score,
        t.test_date,
        CASE t.test_cd
          WHEN 1 THEN '中間テスト'
          WHEN 2 THEN '期末テスト'
          WHEN 3 THEN '総合テスト'
        END AS test_name,
        sub.name AS subject_name,
        sub.sort AS subject_sort
      FROM scores s
      INNER JOIN tests t ON s.test_id = t.id
      INNER JOIN subjects sub ON s.subject_id = sub.id
      WHERE s.student_id = :student_id
      AND s.test_id IN (
        SELECT test_id FROM (
        SELECT DISTINCT test_id FROM scores
        WHERE student_id = :student_id2
        ORDER BY test_id DESC
        LIMIT :limit OFFSET :offset
        ) AS tmp
      ) 
      ORDER BY t.test_date DESC, sub.sort ASC"
    );
    $stmt->bindValue(':student_id', $studentId, \PDO::PARAM_INT);
    $stmt->bindValue(':student_id2', $studentId, \PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // 取得したデータをテストごとにグループ化する
    $grouped = [];
    foreach ($rows as $row) {
      $testId = $row->test_id;

      // このテストIDのグループがまだ存在しなければ新規作成
      if (!isset($grouped[$testId])) {
        $grouped[$testId] = [
          'test_date' => $row->test_date,
          'test_name' => $row->test_name,
          'subjects' => [],
        ];
      }

      // 科目データをグループに追加
      $grouped[$testId]['subjects'][] = $row;
    }

    return $grouped;
  }

  // ＝＝＝＝＝　POST処理メソッド　＝＝＝＝＝

  // POSTリクエストの処理
  public function processPost($studentId) 
  {
    // POSTリクエストかチェック
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      
      // CSRFトークンチェック
      Token::validate();

      // アクションを取得
      $action = filter_input(INPUT_GET, 'action');

      // アクションに応じて分岐
      switch ($action) {
        // 成績更新処理
        case 'update_score':
          $this->_updateScore($studentId);
          break;

        // 成績削除
        case 'delete_score':
          $this->_deleteScore($studentId);
          break;
        
        default:
          break;
      }
    }
  }

  // 成績更新処理
  private function _updateScore($studentId)
  {
    // 点数配列を取得
    $scores = isset($_POST['scores']) && is_array($_POST['scores']) ? $_POST['scores'] : [];

    // バリデーション
    if (empty($scores)) {
      return;
    }

    foreach ($scores as $scoreId => $scoreValue) {
      // 各変数をフィルタリング
      $scoreId = filter_var($scoreId, FILTER_VALIDATE_INT);
      $scoreValue = filter_var($scoreValue, FILTER_VALIDATE_INT);  

      // バリデーション：IDまたは値が不正な場合はスキップ
      if ($scoreId === false || $scoreValue === false) {
        continue;
      }

      // バリデーション：点数が0－100の範囲外の場合はスキップ
      if ($scoreValue < 0 || $scoreValue > 100) {
        continue;
      }

      // データベース更新
      $this->update($scoreId, $scoreValue, $studentId);
    }

    // ページ番号を取得
    $page = $_POST['page'] ?? 1;
    if (!is_numeric($page) || $page < 1) {
      $page = 1;
    }

   // 詳細画面にリダイレクト
   header('Location: ' . SITE_URL . '/student-detail.php?id=' . $studentId . '&page=' . $page);
   exit;
  }

  // 成績削除処理
private function _deleteScore($studentId)
{
    // テストIDを取得
    $testId = filter_input(INPUT_POST, 'test_id', FILTER_VALIDATE_INT);

    // バリデーション
    if (!$testId) {
        return;
    }

    // データベース削除
    $this->deleteByTestId($testId, $studentId);

    // ページ番号を取得
    $page = $_POST['page'] ?? 1;
    if (!is_numeric($page) || $page < 1) {
        $page = 1;
    }

    // 詳細画面にリダイレクト
    header('Location: ' . SITE_URL . '/student-detail.php?id=' . $studentId . '&page=' . $page);
    exit;
}

  // ===== データ操作メソッド =====

  // 指定した成績IDと生徒IDの成績データを1件更新する関数
  public function update($id, $score, $studentId)
  {
    $stmt = $this->_pdo->prepare(
      "UPDATE scores SET score = :score WHERE id = :id AND student_id = :student_id"
    );
    $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
    $stmt->bindValue(':score', $score, \PDO::PARAM_INT);
    $stmt->bindValue(':student_id', $studentId, \PDO::PARAM_INT);
    $stmt->execute();
  }

  // 指定したテストIDと生徒IDの成績データを削除する関数
  public function deleteByTestId($testId, $studentId)
  {
    $stmt = $this->_pdo->prepare(
      "DELETE FROM scores WHERE test_id = :test_id AND student_id = :student_id"
    );
    $stmt->bindValue(':test_id', $testId, \PDO::PARAM_INT);
    $stmt->bindValue(':student_id', $studentId, \PDO::PARAM_INT);
    $stmt->execute();
  }
}
