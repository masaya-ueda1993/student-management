<?php

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

// 生徒データの取得を行うインスタンスを作成
$student = new Student($pdo);

// フォーム送信処理（POSTリクエストの処理）
$student->processPost();

// 編集モードの判定フラグを初期化（デフォルトは新規登録）
$isEditMode = false;

// 既存の生徒データを格納する変数を初期化
$studentData = null;

// URLのGETパラメーターからIDを整数として取得
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    // GETにない場合はPOSTから取得
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

// IDが存在する場合は編集モード
if ($id) {
    // 編集モードフラグをtrueにする
    $isEditMode = true;
    // 指定されたIDの生徒データを取得
    $studentData = $student->getById($id);

    // データが存在しない場合
    if (!$studentData) {
        // 一覧画面にリダイレクト
        header('Location: ' . SITE_URL . '/student-list.php');
        exit;
    }
}

// CSRFトークンを生成
Token::create();

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
    <link rel="stylesheet" href="/css/student-form.css">
</head>
<body>
    <!-- 生徒登録・編集画面 -->
    <main>
        <header>
            <h1>Score Manager</h1>
            <!-- ログアウトフォーム -->
            <form method="post" action="logout.php" id="logout-form" style="display: none;">
                <input type="hidden" name="token" value="<?= Utils::h($_SESSION['token']); ?>">
            </form>
            
            <button class="btn btn-logout" id="logout-btn2">ログアウト</button>
        </header>

        <div class="container">
            <section class="form-container">
                <!-- ステップ表示 -->
                <nav class="steps">
                    <div class="step active" id="step-1">
                        <span class="step-number">1</span>
                        <span class="step-name">生徒情報入力</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" id="step-2">
                        <span class="step-number">2</span>
                        <span class="step-name">入力内容確認</span>
                    </div>
                </nav>

                <!-- 入力フォーム（ステップ1） -->
                <section id="form-step-1" class="form-step active">
                    <h2 id="form-title"><?= $isEditMode ? '生徒情報編集' : '生徒情報入力'; ?></h2>
                    <!-- actionを動的に設定：新規登録 or 編集 -->
                    <form id="student-form" method="post" action="?action=<?= $isEditMode ? 'update' : 'add'; ?>" enctype="multipart/form-data">
                        <!-- CSRFトークン -->
                        <input type="hidden" name="token" value="<?= Utils::h($_SESSION['token']); ?>">
                        <!-- 編集時はIDも送信 -->
                        <?php if ($isEditMode): ?>
                            <input type="hidden" name="id" value="<?= $studentData->id; ?>">
                        <?php endif; ?> 

                        <div class="form-row">
                            <div class="form-group">
                                <label>クラス</label>
                                <!-- クラスの値を取得 -->
                                <?php $currentClass = $student->getValues()->class ?? $studentData->class ?? ''; ?>
                                <select id="form-class" name="class" required>
                                    <option value="">クラスを選択してください</option>
                                    <option value="A" <?= ($currentClass === 'A') ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?= ($currentClass === 'B') ? 'selected' : ''; ?>>B</option>
                                    <option value="C" <?= ($currentClass === 'C') ? 'selected' : ''; ?>>C</option>
                                    <option value="D" <?= ($currentClass === 'D') ? 'selected' : ''; ?>>D</option>
                                    <option value="E" <?= ($currentClass === 'E') ? 'selected' : ''; ?>>E</option>
                                </select>
                                <!-- エラーメッセージ表示 -->
                                <?php if ($student->getErrors('class')): ?>
                                    <p class="error-message"><?= Utils::h($student->getErrors('class')); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>クラス番号</label>
                                <input type="number" id="form-class-no" name="class_no" min="1" max="50" value="<?= Utils::h($student->getValues()->class_no ?? $studentData->class_no ?? ''); ?>" required>
                                <!-- エラーメッセージ表示 -->
                                <?php if ($student->getErrors('class_no')): ?>
                                    <p class="error-message"><?= Utils::h($student->getErrors('class_no')); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>氏名（姓）</label>
                                <input type="text" id="form-last-name" name="last_name" value="<?= Utils::h($student->getValues()->last_name ?? $studentData->last_name ?? ''); ?>" required>
                                <!-- エラーメッセージ表示 -->
                                <?php if ($student->getErrors('last_name')): ?>
                                    <p class="error-message"><?= Utils::h($student->getErrors('last_name')); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>氏名（名）</label>
                                <input type="text" id="form-first-name" name="first_name" value="<?= Utils::h($student->getValues()->first_name ?? $studentData->first_name ?? ''); ?>" required>
                                <!-- エラーメッセージ表示 -->
                                <?php if ($student->getErrors('first_name')): ?>
                                    <p class="error-message"><?= Utils::h($student->getErrors('first_name')); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>氏名かな（姓）</label>
                                <input type="text" id="form-last-name-kana" name="last_name_kana" value="<?= Utils::h($student->getValues()->last_name_kana ?? $studentData->last_name_kana ?? ''); ?>" required>
                                <!-- エラーメッセージ表示 -->
                                <?php if ($student->getErrors('last_name_kana')): ?>
                                    <p class="error-message"><?= Utils::h($student->getErrors('last_name_kana')); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>氏名かな（名）</label>
                                <input type="text" id="form-first-name-kana" name="first_name_kana" value="<?= Utils::h($student->getValues()->first_name_kana ?? $studentData->first_name_kana ?? ''); ?>" required>
                                <!-- エラーメッセージ表示 -->
                                <?php if ($student->getErrors('first_name_kana')): ?>
                                    <p class="error-message"><?= Utils::h($student->getErrors('first_name_kana')); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>性別</label>
                                <!-- 性別の値を取得 -->
                                <?php $currentGender = $student->getValues()->gender ?? $studentData->gender ?? ''; ?>
                                <select id="form-sex" name="gender" required>
                                    <option value="">性別を選択してください</option>
                                    <option value="1" <?= ($currentGender == GENDER_MALE) ? 'selected' : ''; ?>>男性</option>
                                    <option value="2" <?= ($currentGender == GENDER_FEMALE) ? 'selected' : ''; ?>>女性</option>
                                </select>
                                <!-- エラーメッセージ表示 -->
                                <?php if ($student->getErrors('gender')): ?>
                                    <p class="error-message"><?= Utils::h($student->getErrors('gender')); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>生年月日</label>
                                <input type="date" id="form-birth-date" name="birth_date" value="<?= Utils::h($student->getValues()->birth_date ?? $studentData->birth_date ?? ''); ?>" required>
                                <!-- エラーメッセージ表示 -->
                                <?php if ($student->getErrors('birth_date')): ?>
                                    <p class="error-message"><?= Utils::h($student->getErrors('birth_date')); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>写真をアップロード</label>
                            <div class="photo-upload-container">
                                <div class="photo-upload">
                                    <input type="file" id="photo-input" name="photo" accept="image/*" style="display: none;">
                                    <p>ここに写真をドラッグ＆ドロップするか、クリックしてファイルを選択してください</p>
                                    <button type="button" class="btn btn-secondary" id="photo-select-btn">ファイルを選択</button>
                                </div>

                                <!-- 編集時：現在の写真を表示 -->
                                <?php if ($isEditMode && !empty($studentData->photo_url)): ?>
                                <aside id="current-photo-preview" class="current-photo hidden">
                                    <div class="photo-label">現在の写真</div>
                                    <img src="/uploads/<?= Utils::h($studentData->photo_url); ?>" alt="現在の写真" class="photo-placeholder-current">
                                </aside>
                                <?php endif; ?>
                            </div>
                            <!-- エラーメッセージを表示 -->
                             <?php if ($student->getErrors('photo')): ?>
                                <p class="error-message"><?= Utils::h($student->getErrors('photo')); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-buttons">
                            <button type="button" class="btn-cancel" id="cancel-btn">
                                × キャンセルして一覧に戻る
                            </button>
                            <button type="button" class="btn btn-primary" id="next-btn">
                                次へ →
                            </button>
                        </div>
                    </form>
                </section>

                <!-- 確認画面（ステップ2） -->
                <section id="form-step-2" class="form-step">
                    <h2>入力内容確認</h2>
                    <article id="confirm-content">
                        <div class="confirm-row">
                            <div class="confirm-item">
                                <div class="confirm-label">クラス</div>
                                <div class="confirm-value" id="confirm-class"></div>
                            </div>
                            <div class="confirm-item">
                                <div class="confirm-label">クラス番号</div>
                                <div class="confirm-value" id="confirm-class-no"></div>
                            </div>
                        </div>

                        <div class="confirm-row">
                            <div class="confirm-item">
                                <div class="confirm-label">氏名</div>
                                <div class="confirm-value" id="confirm-name"></div>
                            </div>
                            <div class="confirm-item">
                                <div class="confirm-label">氏名かな</div>
                                <div class="confirm-value" id="confirm-name-kana"></div>
                            </div>
                        </div>

                        <div class="confirm-row">
                            <div class="confirm-item">
                                <div class="confirm-label">性別</div>
                                <div class="confirm-value" id="confirm-sex"></div>
                            </div>
                            <div class="confirm-item">
                                <div class="confirm-label">生年月日</div>
                                <div class="confirm-value" id="confirm-birth-date"></div>
                            </div>
                        </div>

                        <aside class="confirm-photo-section">
                            <div class="confirm-photo-label">写真</div>
                            <div class="confirm-photo-preview">
                                <div class="confirm-photo-placeholder" id="confirm-photo">写真がありません</div>
                            </div>
                        </aside>
                    </article>
                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary" id="back-btn">修正</button>
                        <button type="submit" form="student-form" class="btn btn-primary" id="submit-btn"><?= $isEditMode ? '更新' : '登録' ; ?></button>
                    </div>
                </section>
            </section>
        </div>
    </main>

    <!-- JavaScript読み込み -->
    <script src="/js/main.js?v=20251122001"></script>
    <script src="/js/student-form.js?v=20251122001"></script>

</body>
</html>
