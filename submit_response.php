<?php
require_once 'functions.php';

// POSTリクエストの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// フォームデータの取得
$event_id = $_POST['event_id'] ?? '';
$participant_name = $_POST['participant_name'] ?? '';
$participant_email = $_POST['participant_email'] ?? '';
$date_option_ids = $_POST['date_option_ids'] ?? [];
$availability = $_POST['availability'] ?? [];
$comment = $_POST['comment'] ?? [];

// 基本的なバリデーション
$errors = [];

if (empty($event_id)) {
    $errors[] = 'イベントIDが指定されていません';
}

if (empty($participant_name)) {
    $errors[] = 'お名前は必須です';
}

if (empty($participant_email) || !filter_var($participant_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '有効なメールアドレスを入力してください';
}

if (empty($date_option_ids)) {
    $errors[] = '日程候補がありません';
}

// イベント情報の取得（存在確認）
if (!empty($event_id)) {
    $event = getEvent($event_id);
    if (!$event) {
        $errors[] = '指定されたイベントは存在しません';
    }
}

// エラーがある場合
if (!empty($errors)) {
    require_once 'layout.php';
    renderHeader('エラー - ' . SITE_NAME);
    
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">';
    echo '<h4>エラーが発生しました</h4>';
    echo '<ul>';
    foreach ($errors as $error) {
        echo '<li>' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
    echo '</div>';
    echo '<a href="javascript:history.back();" class="btn btn-primary">戻る</a>';
    echo '</div>';
    
    renderFooter();
    exit;
}

try {
    // データベース接続
    $pdo = connectDB();
    
    // トランザクション開始
    $pdo->beginTransaction();
    
    // 各日程候補に対する回答を登録
    $responses_added = 0;
    
    foreach ($date_option_ids as $date_option_id) {
        $avail = $availability[$date_option_id] ?? '';
        
        // 未回答の場合はスキップ
        if (empty($avail)) continue;
        
        $comm = $comment[$date_option_id] ?? '';
        
        if (addResponse($event_id, $date_option_id, $participant_name, $participant_email, $avail, $comm)) {
            $responses_added++;
        }
    }
    
    // トランザクションをコミット
    $pdo->commit();
    
    // 成功メッセージをセット
    $_SESSION['success_message'] = '回答を送信しました。';
    
    // イベントページにリダイレクト
    header("Location: event.php?id=" . $event_id);
    exit;
    
} catch (Exception $e) {
    // トランザクションをロールバック
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    require_once 'layout.php';
    renderHeader('エラー - ' . SITE_NAME);
    
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">';
    echo '<h4>エラーが発生しました</h4>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
    echo '<a href="javascript:history.back();" class="btn btn-primary">戻る</a>';
    echo '</div>';
    
    renderFooter();
    exit;
}
