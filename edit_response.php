<?php
require_once 'functions.php';
require_once 'layout.php';

// イベントとメールアドレスの取得
$event_id = $_GET['id'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($event_id) || empty($email)) {
    renderHeader('エラー - ' . SITE_NAME);
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">イベントIDとメールアドレスが必要です。</div>';
    echo '<a href="index.php" class="btn btn-primary">ホームに戻る</a>';
    echo '</div>';
    renderFooter();
    exit;
}

// 編集モードで回答ページにリダイレクト
header("Location: respond.php?id=" . $event_id . "&email=" . urlencode($email));
exit;
