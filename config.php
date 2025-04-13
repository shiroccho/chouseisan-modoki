<?php
// データベース接続情報
define('DB_HOST', '192.168.0.132');
define('DB_NAME', 'schedule_app');
define('DB_USER', 'postgres');
define('DB_PASSWORD', 'postgres'); // 実際のパスワードに変更してください

// 設定項目
define('SITE_NAME', '調整さんアプリ');
define('BASE_URL', 'http://192.168.0.132/chouseisan-modoki/'); // 実際のURLに変更してください

// タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// エラー表示設定（本番環境ではfalseにすることを推奨）
ini_set('display_errors', true);
error_reporting(E_ALL);

// セッション開始
session_start();
