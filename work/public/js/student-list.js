// student-list.js - 生徒一覧画面の処理
// 検索機能・削除機能はPHPにて実装

// 新規登録画面に移動
const showNewStudent = () => {
    window.location.href = 'student-form.php';
};

// 詳細画面に移動
const showStudentDetail = () => {
    window.location.href = 'student-detail.php';
};

// 全選択チェックボックスの処理
const handleSelectAll = () => {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.student-checkbox');

    checkboxes.forEach((checkbox) => {
        checkbox.checked = selectAll.checked;
    });
};

// 生徒一覧画面のイベント設定
const setupStudentListEvents = () => {
    document.addEventListener('click', (e) => {
        // 新規登録ボタン
        if (e.target.id === 'new-student-btn') {
            showNewStudent();
        }
        
        // 詳細ボタン
        if (e.target.classList.contains('detail-btn')) {
            showStudentDetail();
        }
    });
};

document.addEventListener('change', (e) => {
    if (e.target.id === 'select-all') {
        handleSelectAll();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    setupStudentListEvents();
});
