// student-form.js - 生徒登録・編集画面の処理

// 写真が選択されているかどうかのフラグ
let hasPhoto = false;
let selectedPhotoURL = null;

// ステップ切り替え処理
const switchToStep = (stepNumber) => {
    // すべてのステップ関連要素のIDを配列で管理
    const allStepIds = ['step-1', 'step-2', 'form-step-1', 'form-step-2'];
    
    // すべての要素からactiveクラスを削除
    for (let i = 0; i < allStepIds.length; i++) {
        const element = document.getElementById(allStepIds[i]);
        if (element) {
            element.classList.remove('active');
        }
    }
    
    // アクティブにする要素のIDを設定
    let activeStepIds = [];
    if (stepNumber === 1) {
        activeStepIds = ['step-1', 'form-step-1'];
    } else if (stepNumber === 2) {
        activeStepIds = ['step-2', 'form-step-2'];
    }
    
    // 指定された要素にactiveクラスを追加
    for (let i = 0; i < activeStepIds.length; i++) {
        const element = document.getElementById(activeStepIds[i]);
        if (element) {
            element.classList.add('active');
        }
    }
};

// 写真ファイル選択時の処理
const handlePhotoSelect = () => {
    const photoInput = document.getElementById('photo-input');
    const photoUpload = document.querySelector('.photo-upload');
    
    // ファイルが選択されたかチェック
    if (photoInput.files.length === 1) {
        hasPhoto = true;
        const file = photoInput.files[0];
        
        // プレビュー表示
        const reader = new FileReader();
        reader.onload = (e) => {
            selectedPhotoURL = e.target.result;
            
            // 既存のプレビューがあれば削除
            const existingPreview = photoUpload.querySelector('.photo-preview');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            // 新しいプレビューを作成
            const previewDiv = document.createElement('div');
            previewDiv.className = 'photo-preview';
            previewDiv.innerHTML = '<img src="' + selectedPhotoURL + '" alt="選択された写真" style="max-width: 200px; max-height: 200px; margin-top: 10px;">';
            photoUpload.appendChild(previewDiv);
        };
        reader.readAsDataURL(file);
    } else {
        hasPhoto = false;
        selectedPhotoURL = null;
    }
};

// 確認画面にデータを表示
const showConfirmData = (classValue, classNo, lastName, firstName, lastNameKana, firstNameKana, sex, birthDate) => {
    document.getElementById('confirm-class').textContent = classValue;
    document.getElementById('confirm-class-no').textContent = classNo;
    document.getElementById('confirm-name').textContent = lastName + ' ' + firstName;
    document.getElementById('confirm-name-kana').textContent = lastNameKana + ' ' + firstNameKana;
    let sexText = '';
    if (sex === '1' || sex === 1) {
        sexText = '男性';
    } else if (sex === '2' || sex === 2) {
        sexText = '女性';
    } else {
        sexText = sex;
    }
    document.getElementById('confirm-sex').textContent = sexText;
    document.getElementById('confirm-birth-date').textContent = birthDate;

    // 写真の表示
    const confirmPhoto = document.getElementById('confirm-photo');
    if (hasPhoto && selectedPhotoURL) {
        // 新しい写真が選択されている場合
        confirmPhoto.innerHTML = '<img src="' + selectedPhotoURL + '" alt="選択された写真" class="confirm-photo-image">';
    } else {
        // 新しい写真が選択されていない場合
        const currentPhotoPreview = document.getElementById('current-photo-preview');
        if (currentPhotoPreview) {
            // 編集モードで既存の写真がある場合
            const currentPhotoImg = currentPhotoPreview.querySelector('img');
            if (currentPhotoImg) {
                const currentPhotoSrc = currentPhotoImg.getAttribute('src');
                confirmPhoto.innerHTML = '<img src="' + currentPhotoSrc + '" alt="現在の写真" class="confirm-photo-image">';
            } else {
                confirmPhoto.textContent = '写真がありません';
            }
        } else {
            // 新規登録モードで写真が選択されていない場合
            confirmPhoto.textContent = '写真がありません';
        }
    }
};

// エラーメッセージを表示する
const showError = (inputId, message) => {
    const input = document.getElementById(inputId);
    if (!inputId) {
        return;
    }

     // 入力フィールドに赤枠を付ける
    input.classList.add('error');

    // エラーメッセージ要素を作成
    const errorElement = document.createElement('p');
    errorElement.className = 'error-message js-error';
    errorElement.textContent = message;
    input.parentNode.insertBefore(errorElement, input.nextSibling);
};

// すべてのJavaScriptエラー表示を削除する
const clearErrors = () => {
    // JavaScriptが追加したエラーメッセージをすべて削除
    const allErrors = document.querySelectorAll('.error-message');
    allErrors.forEach((error) => {
        error.remove();
    });

    // 入力フィールドの赤枠をすべて削除
    const errorInputs = document.querySelectorAll('.form-group input.error, .form-group select.error');
    errorInputs.forEach((input) => {
        input.classList.remove('error');
    });
};

// フォームのバリデーション
const validateForm = (classValue, classNo, lastName, firstName, lastNameKana, firstNameKana, sex, birthDate) => {
    let hasError = false;

    if (classValue === '') {
        showError('form-class', 'クラスを選択してください。');
        hasError = true;
    }
    
    if (classNo === '') {
        showError('form-class-no', 'クラス番号を入力してください。');
        hasError = true;
    } else {
        const classNoNum = Number(classNo);
        if (classNoNum < 1 || classNoNum > 50) {
            showError('form-class-no', 'クラス番号は1～50の範囲で入力してください。');
            hasError = true;
        }
    }
    
    if (lastName === '') {
        showError('form-last-name', '氏名（姓）を入力してください。');
        hasError = true;
    } else if (lastName.length > 25) {
        showError('form-last-name', '25文字以内で入力してください。');
    } else if (!/^[一-龥ぁ-んァ-ヶー]+$/.test(lastName)) {
        showError('form-last-name', '漢字・ひらがな・カタカナで入力してください。');
    }
    
    if (firstName === '') {
        showError('form-first-name', '氏名（名）を入力してください。');
        hasError = true;
    } else if (firstName.length > 25) {
        showError('form-first-name', '25文字以内で入力してください。');
    } else if (!/^[一-龥ぁ-んァ-ヶー]+$/.test(firstName)) {
        showError('form-first-name', '漢字・ひらがな・カタカナで入力してください');
    }
    
    if (lastNameKana === '') {
        showError('form-last-name-kana', 'ふりがな（姓）を入力してください。');
        hasError = true;
    } else if (lastNameKana.length > 25) {
        showError('form-last-name-kana', '25文字以内で入力してください。');
    } else if (!/^[ぁ-ん]+$/.test(lastNameKana)) {
        showError('form-last-name-kana', 'ひらがなで入力してください。');
    }
    
    if (firstNameKana === '') {
        showError('form-first-name-kana', 'ふりがな（名）を入力してください。');
        hasError = true;
    } else if (firstNameKana.length > 25) {
        showError('form-first-name-kana', '25文字以内で入力してください。');
    } else if (!/^[ぁ-ん]+$/.test(firstNameKana)) {
        showError('form-first-name-kana', 'ひらがなで入力してください。');
    }
    
    if (sex === '') {
        showError('form-sex', '性別を選択してください。');
        hasError = true;
    }
    
    if (birthDate === '') {
        showError('form-birth-date', '生年月日を入力してください。');
        hasError = true;
    }

    return !hasError;
};

// 修正ボタンの処理（ステップ1に戻る）
const backToEdit = () => {
    switchToStep(1);
};

// 確認画面へ進む処理
const goToConfirm = () => {

    // 前回のエラー表示をクリア
    clearErrors();

    // 入力値を取得
    const classValue = document.getElementById('form-class').value;
    const classNo = document.getElementById('form-class-no').value;
    const lastName = document.getElementById('form-last-name').value;
    const firstName = document.getElementById('form-first-name').value;
    const lastNameKana = document.getElementById('form-last-name-kana').value;
    const firstNameKana = document.getElementById('form-first-name-kana').value;
    const sex = document.getElementById('form-sex').value;
    const birthDate = document.getElementById('form-birth-date').value;

    // バリデーション（エラーがあれば確認画面へ進まない）
    if (!validateForm(classValue, classNo, lastName, firstName, lastNameKana, firstNameKana, sex, birthDate)) {
        return;
    }
    
    // 確認画面の内容を設定
    showConfirmData(classValue, classNo, lastName, firstName, lastNameKana, firstNameKana, sex, birthDate);
    
    // ステップ切り替え
    switchToStep(2);
};

// 生徒フォーム画面のイベント設定
const setupStudentFormEvents = () => {
    document.addEventListener('click', (e) => {
        // 次へボタン
        if (e.target.id === 'next-btn') {
            goToConfirm();
        }
        
        // キャンセルボタン
        if (e.target.id === 'cancel-btn') {
            window.location.href = 'student-list.php';
        }
        
        // 修正ボタン
        if (e.target.id === 'back-btn') {
            backToEdit();
        }
        
        // 写真選択ボタン
        if (e.target.id === 'photo-select-btn') {
            const photoInput = document.getElementById('photo-input');
            if (photoInput) {
                photoInput.click();
            }
        }
    });
    
    // チェンジイベント
    document.addEventListener('change', (e) => {
        // 写真選択
        if (e.target.id === 'photo-input') {
            handlePhotoSelect();
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    setupStudentFormEvents();

    // 編集モード時：現在の写真を表示
    const currentPhotoPreview = document.getElementById('current-photo-preview');
    if (currentPhotoPreview) {
        currentPhotoPreview.classList.remove('hidden');
    }
});
