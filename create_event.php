<?php
require_once 'functions.php';

// POSTリクエストの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create.php");
    exit;
}

// フォームデータの取得
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$creator_name = $_POST['creator_name'] ?? '';
$creator_email = $_POST['creator_email'] ?? '';
$dates = $_POST['dates'] ?? [];
$start_times = $_POST['start_times'] ?? [];
$end_times = $_POST['end_times'] ?? [];

// 基本的なバリデーション
$errors = [];

if (empty($title)) {
    $errors[] = 'イベント名は必須です';
}

if (empty($creator_name)) {
    $errors[] = '主催者名は必須です';
}

if (empty($creator_email) || !filter_var($creator_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '有効なメールアドレスを入力してください';
}

if (empty($dates)) {
    $errors[] = '少なくとも1つの日程候補が必要です';
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
    
    // イベントの作成
    $event_id = createEvent($title, $description, $creator_name, $creator_email);
    
    if (!$event_id) {
        throw new Exception('イベントの作成に失敗しました');
    }
    
    // 日程候補を追加
    $date_options_added = 0;
    
    foreach ($dates as $index => $date) {
        if (empty($date)) continue;
        
        $start_time = !empty($start_times[$index]) ? $start_times[$index] : null;
        $end_time = !empty($end_times[$index]) ? $end_times[$index] : null;
        
        if (addDateOption($event_id, $date, $start_time, $end_time)) {
            $date_options_added++;
        }
    }
    
    if ($date_options_added === 0) {
        throw new Exception('日程候補の追加に失敗しました');
    }
    
    // トランザクションをコミット
    $pdo->commit();
    
    // 成功ページにリダイレクト
    $_SESSION['success_message'] = 'イベントを作成しました。下記のURLを参加者に共有してください。';
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
