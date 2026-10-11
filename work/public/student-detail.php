<?php

require_once(__DIR__ . '/../app/Config.php');

if (!isset($_SESSION['me'])) {
    header('Location: ' . SITE_URL . '/login.php' );
    exit;
}

use MyApp\Database;
use MyApp\Student;
use MyApp\Score;
use MyApp\Token;
use MyApp\Utils;

// データベース接続インスタンスを取得
$pdo = Database::getInstance();

// 生徒データの取得・登録・更新・削除を行うインスタンスを作成
$student = new Student($pdo);

// 成績データの取得・更新・削除を行うインスタンスを作成
$scoreObj = new Score($pdo);

// URLのGETパラメーターからIDを取得
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// IDが存在しない場合は一覧画面にリダイレクト
if (!$id) {
    header('Location: ' . SITE_URL . '/student-list.php');
    exit;
}

// 指定されたIDの生徒データを取得
$studentData = $student->getById($id);

// 生徒データが存在しない場合は一覧画面にリダイレクト
if (!$studentData) {
    header('Location: ' . SITE_URL . '/student-list.php');
    exit;
}

// POST処理をクラスに委託
$scoreObj->processPost($id);
$student->processPost();

// CSRFトークンを生成
Token::create();

// 一覧画面へ戻るリンク用
$listClass = filter_input(INPUT_GET, 'list_class');
$listName = filter_input(INPUT_GET, 'list_name');
$listPage = filter_input(INPUT_GET, 'list_page', FILTER_VALIDATE_INT);
$listPage = ($listPage !== false && $listPage > 0) ? $listPage : 1;

// 一覧画面へ戻るURLを作成
$backTolistUrl = 'student-list.php?class=' . urlencode($listClass ?? '') . '&name=' . urlencode($listName ?? '') . '&page=' . $listPage;

// ページング設定(1ページあたりの表示件数)
$limit = TESTS_PER_PAGE;

// URLのGETパラメーターからページ番号を整数として取得
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

// 不正な値を安全な値に修正する
$page = ($page !== false && $page > 0) ? $page : 1; // データベースクエリのエラー防止、画面表示のエラー防止のため

// スキップする件数を計算
$offset = ($page - 1) * $limit;

// 指定した生徒の成績データの総数を取得
$totalTests = $scoreObj->countByStudentId($id);

// 総ページ数を計算
$totalPages = ceil($totalTests / $limit); // ceilは小数点切り上げ

// グループ化済みの成績データを取得
$groupedScores = $scoreObj->getByStudentId($id, $limit, $offset);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Score Manager - 成績管理システム</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS読み込み -->
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/student-detail.css">
</head>
<body>
    <!-- 生徒詳細画面 -->
    <main>
        <header>
            <h1>Score Manager</h1>
            <!-- ログアウトフォーム -->
            <form method="post" action="logout.php" id="logout-form" style="display: none;">
                <input type="hidden" name="token" value="<?= Utils::h($_SESSION['token']); ?>">
            </form>
            
            <button class="btn btn-logout" id="logout-btn3">ログアウト</button>
        </header>

        <div class="container">
            <nav class="breadcrumb">
                <a href="<?= Utils::h($backTolistUrl); ?>">生徒一覧</a>
                <span> &gt; </span>
                <span><?= Utils::h($studentData->last_name . ' ' . $studentData->first_name); ?></span>
            </nav>

            <section class="detail-container">
                <h2>生徒詳細情報</h2>
                
                <article class="student-info-section">
                    <aside class="student-photo-area">
                        <?php if (!empty($studentData->photo_url)): ?>
                            <img src="/uploads/<?= Utils::h($studentData->photo_url); ?>" alt="生徒の写真">
                        <?php else: ?>
                            <div class="photo-placeholder">写真</div>
                        <?php endif; ?>
                    </aside>
                    
                    <div class="student-info-grid">
                        <div class="info-item">
                            <div class="info-label">クラス</div>
                            <div class="info-value"><?= Utils::h($studentData->class); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">クラス番号</div>
                            <div class="info-value"><?= str_pad($studentData->class_no, 2, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">氏名</div>
                            <div class="info-value"><?= Utils::h($studentData->last_name . ' ' . $studentData->first_name); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">氏名かな</div>
                            <div class="info-value"><?= Utils::h($studentData->last_name_kana . ' ' . $studentData->first_name_kana); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">性別</div>
                            <div class="info-value"><?= $studentData->gender == GENDER_MALE ? '男性' : '女性'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">生年月日</div>
                            <div class="info-value"><?= Utils::h(date('Y年n月j日', strtotime($studentData->birth_date))); ?></div>
                        </div>
                    </div>
                </article>

                <button class="btn btn-primary" id="edit-student-btn" data-student-id="<?= $studentData->id; ?>">個人情報を編集</button>

                <!-- 成績一覧 -->
                <section class="grades-section">
                    <h3>成績一覧表</h3>
                    
                    <div id="grades-list">
                        <?php if (count($groupedScores) > 0): ?>
                            <?php foreach ($groupedScores as $testId => $testData): ?>
                                <article class="grade-card">
                                    <header class="grade-header">
                                        <div class="grade-info">
                                            <div class="grade-info-item">
                                                <span class="grade-info-label">実施日</span>
                                                <span class="grade-info-value"><?= Utils::h($testData['test_date']); ?></span>
                                            </div>
                                            <div class="grade-info-item">
                                                <span class="grade-info-label">テスト名</span>
                                                <span class="grade-info-value"><?= Utils::h($testData['test_name']); ?></span>
                                            </div>
                                        </div>
                                    </header>
                                    
                                    <!-- 成績更新用のフォーム -->
                                    <form method="post" action="?id=<?= $id; ?>&action=update_score" id="score-form-<?= $testId; ?>">
                                        <input type="hidden" name="token" value="<?= Utils::h($_SESSION['token']); ?>">
                                        <input type="hidden" name="test_id" value="<?= $testId; ?>">
                                        <input type="hidden" name="page" value="<?= $page; ?>">
                                        
                                        <div class="grade-body">
                                            <div class="grade-scores">
                                                <?php foreach ($testData['subjects'] as $subject): ?>
                                                    <div class="score-item">
                                                        <div class="score-label"><?= Utils::h($subject->subject_name); ?></div>
                                                        <input type="number" class="score-input" name="scores[<?= $subject->id; ?>]" value="<?= Utils::h($subject->score); ?>" min="0" max="100">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <div class="grade-summary">
                                                <div class="summary-item">
                                                    <div class="score-label">合計</div>                                                  
                                                    <div class="summary-value" id="total-<?= $testId; ?>">0</div>
                                                </div>
                                                <div class="summary-item">
                                                    <div class="score-label">平均</div>
                                                    <div class="summary-value" id="average-<?= $testId; ?>">0</div>
                                                </div>
                                            </div>
                                            
                                            <div class="grade-action">
                                                <button type="submit" class="btn btn-primary grade-save-btn">保存</button>
                                                <button type="button" form="delete-form-<?= $testId; ?>" class="btn btn-danger delete-btn" data-test-id="<?= $testId; ?>">削除</button>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <!-- 成績削除用のフォーム（非表示） -->
                                    <form method="post" action="?id=<?= $id; ?>&action=delete_score" id="delete-form-<?= $testId; ?>" style="display: none;">
                                        <input type="hidden" name="token" value="<?= Utils::h($_SESSION['token']); ?>">
                                        <input type="hidden" name="test_id" value="<?= $testId; ?>">
                                        <input type="hidden" name="page" value="<?= $page; ?>">
                                    </form>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data-message">成績データがありません</p>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- ページネーション -->
                <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <div class="pagination-container">
                        <div class="pagination-links">
                            <?php if ($page > 1): ?>
                                <a href="?id=<?= $id; ?>&page=<?= $page - 1; ?>" class="btn btn-secondary"><< 前へ</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <strong class="current-page"><?= $i; ?></strong>
                                <?php else: ?>
                                    <a href="?id=<?= $id; ?>&page=<?= $i; ?>" class="page-link"><?= $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?id=<?= $id; ?>&page=<?= $page + 1; ?>" class="btn btn-secondary">次へ >></a>
                            <?php endif; ?>
                        </div>

                        <span id="page-info">全 <?= $totalPages; ?> ページ（<?= $totalTests; ?> テスト）</span>
                    </div>
                </nav>
                <?php endif; ?>

                <!-- 削除ボタン -->
                <section class="delete-section">
                    <button class="btn btn-danger" id="delete-student-btn">
                        この生徒を削除する
                    </button>
                    <div id="delete-confirm" class="delete-confirm hidden">
                        <p class="warning-text">
                            この操作は取り消すことができません。すべての成績データも削除されます。
                        </p>
                        <div class="button-area">
                        <form method="post" action="?id=<?= $id; ?>&action=delete">
                                <input type="hidden" name="token" value="<?= Utils::h($_SESSION['token']); ?>">
                                <input type="hidden" name="id" value="<?= $id; ?>">
                                <input type="hidden" name="list_class" value="<?= Utils::h($listClass ?? ''); ?>">
                                <input type="hidden" name="list_name" value="<?= Utils::h($listName ?? ''); ?>">
                                <input type="hidden" name="list_page" value="<?= $listPage; ?>">
                                <button type="submit" class="btn btn-danger">この生徒を削除する</button>
                            </form>
                            <button class="btn btn-secondary" id="cancel-delete-btn">キャンセル</button>
                        </div>
                    </div>
                </section>
            </section>
        </div>
    </main>

    <!-- JavaScript読み込み -->
    <script src="/js/main.js?v=20251122001"></script>
    <script src="/js/student-detail.js?v=20251122001"></script>

</body>
</html>
