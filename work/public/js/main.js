// main.js - 共通処理とページ遷移

// ログアウト処理
const logout = () => {
    const form = document.getElementById('logout-form');
    if (form) {
        form.submit();
    }
};

// 共通イベントの設定
const setupCommonEvents = () => {
    // ログアウトボタン（全画面共通）
    document.addEventListener('click', (e) => {
        if (e.target.id === 'logout-btn' || 
            e.target.id === 'logout-btn2' || 
            e.target.id === 'logout-btn3') {
            logout();
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setupCommonEvents();
});
