<?php
// get_responses.php - 既存の回答を取得するAPIエンドポイント
require_once 'functions.php';

header('Content-Type: application/json');

$event_id = $_GET['event_id'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($event_id) || empty($email)) {
    echo json_encode(['error' => 'パラメータが不足しています']);
    exit;
}

try {
    $pdo = connectDB();
    
    // 参加者の名前を取得
    $stmt = $pdo->prepare("
        SELECT DISTINCT participant_name 
        FROM responses 
        WHERE event_id = :event_id AND participant_email = :email
        LIMIT 1
    ");
    $stmt->bindParam(':event_id', $event_id);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 回答を取得
    $stmt = $pdo->prepare("
        SELECT date_option_id, availability
        FROM responses
        WHERE event_id = :event_id AND participant_email = :email
    ");
    $stmt->bindParam(':event_id', $event_id);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    $responses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $responses[$row['date_option_id']] = $row['availability'];
    }
    
    echo json_encode([
        'name' => $participant ? $participant['participant_name'] : '',
        'responses' => $responses
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
