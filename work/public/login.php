<?php 

require_once(__DIR__ . '/../app/Config.php');

use MyApp\Database;
use MyApp\User;
use MyApp\Utils;

$pdo = Database::getInstance();

$user = new User($pdo);
$user->processPost();

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
    <link rel="stylesheet" href="/css/login.css">
</head>
<body>
    <!-- ログイン画面 -->
    <main>
        <section class="login-container">
            <h1>Score Manager</h1>
            <form id="login-form" method="post" action="?action=login">
                <!-- CSRFトークン -->
                <input type="hidden" name="token" value="<?= Utils::h($_SESSION['token']); ?>">

                <div class="form-group">
                    <label>メールアドレス</label>
                    <input type="email" id="email" name="email" value="<?= Utils::h($user->getValues()->email ?? ''); ?>" placeholder="メールアドレスを入力してください" required>
                    <!-- エラーメッセージ表示 -->
                    <?php if ($user->getErrors('email')): ?>
                        <p class="error-message"><?= Utils::h($user->getErrors('email')); ?></p>
                    <?php  endif; ?>
                </div>

                <div class="form-group">
                    <label>パスワード</label>
                    <input type="password" id="password" name="password" placeholder="パスワードを入力してください" required>
                    <!-- エラーメッセージ表示 -->
                    <?php if ($user->getErrors('password')): ?>
                        <p class="error-message"><?= Utils::h($user->getErrors('password')); ?></p>
                    <?php  endif; ?>
                </div>

                <!-- ログイン全体のエラーメッセージ -->
                 <?php if ($user->getErrors('login')): ?>
                    <p class="error-message"><?= Utils::h($user->getErrors('login')); ?></p>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">ログイン</button>
            </form>
        </section>
    </main>

    <!-- JavaScript読み込み -->
    <script src="/js/main.js?v=20251122001"></script>
    <script src="/js/login.js?v=20251122001"></script>

</body>
</html>
