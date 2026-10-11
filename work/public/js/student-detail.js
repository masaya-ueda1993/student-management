// 生徒詳細画面の処理

// 編集画面に移動
const editStudent = (studentId) => {
    window.location.href = 'student-form.php?id=' + studentId;
};

// 削除確認の表示/非表示を切り替え
const toggleDeleteConfirm = (show) => {
    const deleteBtn = document.getElementById('delete-student-btn');
    const deleteConfirm = document.getElementById('delete-confirm');

    if(show) {
        deleteBtn.classList.add('hidden');
        deleteConfirm.classList.remove('hidden');
    } else {
        deleteConfirm.classList.add('hidden');
        deleteBtn.classList.remove('hidden');
    }
};

// 成績削除処理
const deleteScore = (testId) => {
    if (confirm('このテストの成績を削除しますか？')) {
        const form = document.getElementById('delete-form-' + testId);
        if (form) {
            form.submit();
        }
    }
};

// 合計・平均を計算して表示を更新
const updateTotalAndAverage = (testId) => { 
    //該当テストのフォームを取得
    const form = document.getElementById('score-form-' + testId);
    if (!form) {
        return;
    }

    //フォーム内のすべての点数入力フィールドを取得
    const scoreInputs = form.querySelectorAll('.score-input');

    //合計を計算
    let total = 0;
    let count = 0;
    scoreInputs.forEach((input) => {
        const value = parseInt(input.value, 10);
        if (!isNaN(value)) {
            total += value;
            count++;
        }
    });

    //平均を計算
    const average = count > 0 ? (total / count).toFixed(1) : '0.0';

    //表示を更新
    const totalElement = document.getElementById('total-' + testId);
    const averageElement = document.getElementById('average-' + testId);

    if (totalElement && averageElement) {
        totalElement.textContent = total;
        averageElement.textContent = average;
    }
};

// ページ読み込み時に全テストの合計・平均を計算する
const caluclateAllScores = () => {
    const scoreForms = document.querySelectorAll('form[id^="score-form-"]');
    scoreForms.forEach((form) => {
        const testId = form.id.replace('score-form-', '');
        updateTotalAndAverage(testId);
    });
};

// 生徒詳細画面のイベント設定
const setupStudentDetailEvents = () => {
    document.addEventListener('click', (e) => {

        // ページネーションのリンクは処理しない
        if (e.target.closest('.pagination')) {
            return;
        }
 
        // 編集ボタン
        if (e.target.id === 'edit-student-btn') {
            const studentId = e.target.dataset.studentId;
            editStudent(studentId);
        }

        // 成績削除ボタン
        if (e.target.classList.contains('delete-btn')) {
            const testId = e.target.dataset.testId;
            deleteScore(testId);
        }

        // 個人データ全削除ボタン
        if (e.target.id === 'delete-student-btn') {
            toggleDeleteConfirm(true);
        }
        
        // 個人データ全削除キャンセルボタン
        if (e.target.id === 'cancel-delete-btn') {
            toggleDeleteConfirm(false);
        }
    });

    // 点数入力フィールドの変更時の処理
    document.addEventListener('input', (e) => {
        if (e.target.classList.contains('score-input')) {
            // 該当するフォームを取得
            const form =e.target.closest('form');
            if (form && form.id) {
                const testId = form.id.replace('score-form-', '');
                updateTotalAndAverage(testId);
            }
        }
    });

    // フォーム送信時の確認
    document.addEventListener('submit', (e) => {
        // 成績削除フォーム
        if (e.target.classList.contains('delete-score-form')) {
            if (!confirm('このテストの成績を削除しますか？')) {
                e.preventDefault();
            }
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setupStudentDetailEvents();
    caluclateAllScores();
});
