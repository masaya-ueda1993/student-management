<?php

// 設定ファイルを読み込む
require_once(__DIR__ . '/../app/Config.php');

if (!isset($_SESSION['me'])) {
    header('Location: ' . SITE_URL . '/login.php' );
    exit;
}

use MyApp\Database;
use MyApp\Student;
use MyApp\Token;
use MyApp\Utils;

// データベース接続インスタンスを取得
$pdo = Database::getInstance();

// 生徒データの取得・登録・更新・削除を行うインスタンスを作成
$student = new Student($pdo);

// 一括削除のPOST処理
$student->processPost();

// CSRFトークンを生成
Token::create();

/* 
1ページあたりの表示する生徒の件数を設定（20件）
Config/phpで定義しているdefine('STUDENTS_PER_PAGE', 20);
*/
$limit = STUDENTS_PER_PAGE;

// URLのGETパラメータからclass,nameを取得
$class = filter_input(INPUT_GET, 'class');
$name = filter_input(INPUT_GET, 'name');

/*
URLのGETパラメータからページ番号を整数として取得
なぜ・・・現在のページ番号の管理及びユーザーがページ番号をクリックしたときの値を取得し、
整数として検証することで、不正な値を防ぐ
*/
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
// 不正な値を安全な値（１）に修正する
$page = ($page !== false && $page > 0) ? $page : 1;

/* 
ページ取得の際に、指定されたページのデータだけ取得
【基本文法の説明】
`$offset` = スキップする件数を格納する変数
`($page - 1)` = 現在のページ番号から1を引く
【計算の仕組み】
オフセット = (ページ番号 - 1) × 1ページの件数
$limit = 20; // 1ページあたり20件

例：
1ページ目
$page = 1;
$offset = (1 - 1) * 20 = 0 * 20 = 0;
0件スキップ → 1～20件目を表示

2ページ目
$page = 2;
$offset = (2 - 1) * 20 = 1 * 20 = 20;
20件スキップ → 21～40件目を表示
*/
$offset = ($page - 1) * $limit;

/*
・検索条件に一致する生徒の総数をデータベースから取得
・「全○○件中○○～○○件を表示」という表示に使
・$student->countAll() = Student クラスの countAll メソッドを呼び出す
*/
$totalStudents = $student->countAll($class, $name);

/* 
・総ページ数を計算して格納
・ページング番号（１，２，３・・）を表示するために使用
・「次へ」ボタンを表示するかどうかを判定に使用
・ceil() = 小数点以下を切り上げる関数
・$totalStudents / $limit = 総件数 ÷ 1ページあたりの件数
*/
$totalPages = ceil($totalStudents / $limit);

/*
データベースから検索条件とページング条件に一致する生徒データを取得して格納する
・現在のページに表示する生徒データを取得
・検索条件に一致する生徒データを取得
・limit,offsetで指定された件数だけを取得
*/
$students = $student->getAllPaginated($limit, $offset, $class, $name);

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
    <link rel="stylesheet" href="/css/student-list.css">
</head>
<body>
  <!-- 生徒一覧画面 -->
    <main>
        <header>
            <h1>Score Manager</h1>
            <!-- ログアウトフォーム -->
            <form method="post" action="logout.php" id="logout-form" style="display: none;">
                <input type="hidden" name="token" value="<?= Utils::h($_SESSION['token']); ?>">
            </form>
            
            <button class="btn btn-logout" id="logout-btn">ログアウト</button>
        </header>

        <div class="container">
            <section class="search-section">
                <!-- 検索フォームをGETメソッドで送信 -->
                <form method="get" action="">
                    <div class="search-area">
                        <div class="search-inputs">
                            <div class="form-group">
                                <label>氏名 / 氏名かな</label>
                                <input type="text"  name="name" value="<?= Utils::h($name ?? ''); ?>" placeholder="氏名で検索">
                            </div>
                            <div class="form-group">
                                <label>クラス名</label>
                                <select name="class">
                                    <!-- 検索後も選択したクラスが選ばれる状態にする　三項演算子 = (条件) ? 真の場合 : 偽の場合; -->
                                    <option value=''>すべてのクラス</option>
                                    <option value="A" <?= ($class === 'A') ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?= ($class === 'B') ? 'selected' : ''; ?>>B</option>
                                    <option value="C" <?= ($class === 'C') ? 'selected' : ''; ?>>C</option>
                                    <option value="D" <?= ($class === 'D') ? 'selected' : ''; ?>>D</option>
                                    <option value="E" <?= ($class === 'E') ? 'selected' : ''; ?>>E</option>
                                </select>
                            </div>
                            <button type="submit"  class="btn btn-search">検索</button>
                        </div>
                        <div class="action-area">
                            <button type="button" class="btn btn-primary" onclick="location.href='student-form.php'">新規登録</button>
                            <button type="submit" form="delete-form" class="btn btn-danger">削除</button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- 一括削除用のフォーム POSTメソッドでaction=delete_multipleに送信-->
            <form method="post" action="?action=delete_multiple" id="delete-form">
                <!-- CSRFトークン -->
                <input type="hidden" name="token" value="<?= Utils::h($_SESSION['token']); ?>">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>クラス</th>
                            <th>クラス番号</th>
                            <th>氏名</th>
                            <th>氏名かな</th>
                            <th>性別</th>
                            <th>生年月日</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody id="student-list-body">
                        <?php if(count($students) > 0): ?>
                            <?php foreach($students as $s): ?>
                                <tr>
                                    <td><input type="checkbox" name="id[]" value="<?= $s->id; ?>" class="student-checkbox"></td>
                                    <td><?= Utils::h($s->class); ?></td>
                                    <td><?= str_pad($s->class_no, 2, '0', STR_PAD_LEFT); ?></td> <!-- str_pad(文字列, 桁数, 埋める文字, 埋める方向) -->
                                    <td><?= Utils::h($s->last_name . ' ' . $s->first_name); ?></td>
                                    <td><?= Utils::h($s->last_name_kana . ' ' . $s->first_name_kana); ?></td>
                                    <td><?= $s->gender == GENDER_MALE ? '男' : '女'; ?></td>
                                    <td><?= Utils::h(date('Y年n月j日', strtotime($s->birth_date))); ?></td>
                                    <td><a href="student-detail.php?id=<?= $s->id; ?>&list_class=<?= urlencode($class ?? ''); ?>&list_name=<?= urlencode($name ?? ''); ?>&list_page=<?= $page; ?>" class="btn detail-btn">詳細</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-data-message">該当する生徒が見つかりませんでした</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
             </form>
            
            <!-- ページネーション -->
            <nav class="pagination">
                <?php
                // 開始位置と終了位置を計算
                if ($totalStudents > 0) {
                    // 表示しているページの最初の生徒番号を計算
                    $startFrom = $offset + 1;
                    // 表示しているページの最後の生徒番号を計算
                    $endAt = min($offset + $limit, $totalStudents);
                }
                ?>
               
                <!-- ページ番号リンク -->
                <div class="pagination-container">
                    <div class="pagination-links">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1; ?>&class=<?= urlencode($class ?? ''); ?>&name=<?= urlencode($name ?? ''); ?>" class="btn btn-secondary"><< 前へ</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <strong class="current-page"><?= $i; ?></strong>
                            <?php else: ?>
                                <a href="?page=<?= $i; ?>&class=<?= urlencode($class ?? ''); ?>&name=<?= urlencode($name ?? ''); ?>" class="page-link"><?= $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?> 

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1; ?>&class=<?= urlencode($class ?? ''); ?>&name=<?= urlencode($name ?? ''); ?>" class="btn btn-secondary"> 次へ >></a>
                        <?php endif; ?>
                    </div>

                    <?php if ($totalStudents > 0): ?>
                        <span id="page-info">全 <?= $totalPages; ?> ページ中 <?= $startFrom; ?>～<?= $endAt; ?> 件を表示</span>
                    <?php else: ?>
                        <span id="page-info">全 0 件</span>
                    <?php endif; ?>
                </div>
            </nav>
            
        </div>
    </main>
    
    <!-- JavaScript読み込み -->
    <script src="/js/main.js?v=20251122001"></script>
    <script src="/js/student-list.js?v=20251122001"></script>

</body>
</html>
