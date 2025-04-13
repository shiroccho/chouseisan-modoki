<?php
// エラー表示を有効化（デバッグ用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// functions.php の読み込み
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

// デバッグ情報（必要に応じてコメントアウト）
// echo "<pre>POST データ: " . print_r($_POST, true) . "</pre>";

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
    echo '<div style="color: red; margin: 20px 0;">';
    foreach ($errors as $error) {
        echo $error . '<br>';
    }
    echo '</div>';
    echo '<a href="javascript:history.back();">戻る</a>';
    exit;
}

try {
    // データベース接続
    $pdo = connectDB();
    
    // トランザクション開始
    $pdo->beginTransaction();
    
    // イベントIDの生成
    $event_id = bin2hex(random_bytes(16));
    
    // イベントの作成
    $stmt = $pdo->prepare("INSERT INTO events (event_id, title, description, creator_name, creator_email, created_at) 
                          VALUES (:event_id, :title, :description, :creator_name, :creator_email, NOW())");
    
    $stmt->bindParam(':event_id', $event_id);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':creator_name', $creator_name);
    $stmt->bindParam(':creator_email', $creator_email);
    
    $stmt->execute();
    
    // 日程候補を追加
    foreach ($dates as $index => $date) {
        if (empty($date)) continue;
        
        $start_time = !empty($start_times[$index]) ? $start_times[$index] : null;
        $end_time = !empty($end_times[$index]) ? $end_times[$index] : null;
        
        $stmt = $pdo->prepare("INSERT INTO date_options (event_id, date, start_time, end_time) 
                              VALUES (:event_id, :date, :start_time, :end_time)");
        
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        
        $stmt->execute();
    }
    
    // トランザクションをコミット
    $pdo->commit();
    
    // 成功ページにリダイレクト
    header("Location: event.php?id=" . $event_id);
    exit;
    
} catch (PDOException $e) {
    // トランザクションをロールバック
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    echo '<div style="color: red; margin: 20px 0;">';
    echo 'データベースエラー: ' . $e->getMessage();
    echo '</div>';
    echo '<a href="javascript:history.back();">戻る</a>';
    exit;
} catch (Exception $e) {
    echo '<div style="color: red; margin: 20px 0;">';
    echo 'エラー: ' . $e->getMessage();
    echo '</div>';
    echo '<a href="javascript:history.back();">戻る</a>';
    exit;
}

