<?php
require_once 'config.php';

// データベース接続関数
function connectDB() {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("データベース接続エラー: " . $e->getMessage());
    }
}

// イベントを作成する関数
function createEvent($title, $description, $creator_name, $creator_email) {
    $pdo = connectDB();
    $event_id = bin2hex(random_bytes(16)); // ユニークなIDを生成
    
    try {
        $stmt = $pdo->prepare("INSERT INTO events (event_id, title, description, creator_name, creator_email) 
                              VALUES (:event_id, :title, :description, :creator_name, :creator_email)");
        
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':creator_name', $creator_name);
        $stmt->bindParam(':creator_email', $creator_email);
        
        $stmt->execute();
        return $event_id;
    } catch (PDOException $e) {
        error_log("データベースエラー（createEvent）: " . $e->getMessage());
        return false;
    }
}

// 日程候補を追加する関数
function addDateOption($event_id, $date, $start_time, $end_time) {
    $pdo = connectDB();
    
    try {
        $stmt = $pdo->prepare("INSERT INTO date_options (event_id, date, start_time, end_time) 
                              VALUES (:event_id, :date, :start_time, :end_time)");
        
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':start_time', $start_time);
        $stmt->bindParam(':end_time', $end_time);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("データベースエラー（addDateOption）: " . $e->getMessage());
        return false;
    }
}

// 参加者の回答を登録する関数
function addResponse($event_id, $date_option_id, $participant_name, $participant_email, $availability, $comment = '') {
    $pdo = connectDB();
    
    try {
        // トランザクション開始
        $pdo->beginTransaction();
        
        // 既存の回答を削除（上書き更新）
        $stmt = $pdo->prepare("DELETE FROM responses WHERE event_id = :event_id AND 
                              date_option_id = :date_option_id AND participant_email = :email");
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':date_option_id', $date_option_id);
        $stmt->bindParam(':email', $participant_email);
        $stmt->execute();
        
        // 新しい回答を登録
        $stmt = $pdo->prepare("INSERT INTO responses (event_id, date_option_id, participant_name, participant_email, availability, comment) 
                              VALUES (:event_id, :date_option_id, :participant_name, :participant_email, :availability, :comment)");
        
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':date_option_id', $date_option_id);
        $stmt->bindParam(':participant_name', $participant_name);
        $stmt->bindParam(':participant_email', $participant_email);
        $stmt->bindParam(':availability', $availability);
        $stmt->bindParam(':comment', $comment);
        
        $result = $stmt->execute();
        
        // トランザクションをコミット
        $pdo->commit();
        
        return $result;
    } catch (PDOException $e) {
        // トランザクションをロールバック
        $pdo->rollBack();
        error_log("データベースエラー（addResponse）: " . $e->getMessage());
        return false;
    }
}

// イベント情報を取得する関数
function getEvent($event_id) {
    $pdo = connectDB();
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = :event_id");
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("データベースエラー（getEvent）: " . $e->getMessage());
        return false;
    }
}

// イベントの日程候補を取得する関数
function getDateOptions($event_id) {
    $pdo = connectDB();
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM date_options WHERE event_id = :event_id ORDER BY date, start_time");
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("データベースエラー（getDateOptions）: " . $e->getMessage());
        return [];
    }
}

// イベントの回答を集計する関数
function getSummary($event_id) {
    $pdo = connectDB();
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                d.id AS date_option_id,
                d.date,
                d.start_time,
                d.end_time,
                COUNT(CASE WHEN r.availability = 'available' THEN 1 END) AS available_count,
                COUNT(CASE WHEN r.availability = 'maybe' THEN 1 END) AS maybe_count,
                COUNT(CASE WHEN r.availability = 'unavailable' THEN 1 END) AS unavailable_count,
                COUNT(DISTINCT r.participant_name) AS total_responses
            FROM 
                date_options d
            LEFT JOIN 
                responses r ON d.id = r.date_option_id
            WHERE 
                d.event_id = :event_id
            GROUP BY 
                d.id, d.date, d.start_time, d.end_time
            ORDER BY 
                available_count DESC, maybe_count DESC, d.date, d.start_time
        ");
        
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("データベースエラー（getSummary）: " . $e->getMessage());
        return [];
    }
}

// 参加者リストを取得する関数
function getParticipants($event_id) {
    $pdo = connectDB();
    
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                participant_name, 
                participant_email
            FROM 
                responses
            WHERE 
                event_id = :event_id
            ORDER BY 
                participant_name
        ");
        
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("データベースエラー（getParticipants）: " . $e->getMessage());
        return [];
    }
}

// 参加者ごとの回答を取得する関数
function getParticipantResponses($event_id, $participant_email) {
    $pdo = connectDB();
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                r.date_option_id,
                r.availability,
                r.comment
            FROM 
                responses r
            WHERE 
                r.event_id = :event_id
                AND r.participant_email = :participant_email
        ");
        
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':participant_email', $participant_email);
        $stmt->execute();
        
        $responses = [];
        while ($row = $stmt->fetch()) {
            $responses[$row['date_option_id']] = [
                'availability' => $row['availability'],
                'comment' => $row['comment']
            ];
        }
        
        return $responses;
    } catch (PDOException $e) {
        error_log("データベースエラー（getParticipantResponses）: " . $e->getMessage());
        return [];
    }
}

// 日付のフォーマット関数
function formatDate($date) {
    $timestamp = strtotime($date);
    return date('Y年n月j日(', $timestamp) . get_day_of_week_jp(date('w', $timestamp)) . ')';
}

// 時間のフォーマット関数
function formatTime($time) {
    if (!$time) return '';
    return date('H:i', strtotime($time));
}

// 曜日の日本語表記を取得
function get_day_of_week_jp($w) {
    $week = ['日', '月', '火', '水', '木', '金', '土'];
    return $week[$w];
}

// 可用性の日本語表記を取得
function getAvailabilityText($availability) {
    switch ($availability) {
        case 'available':
            return '○';
        case 'maybe':
            return '△';
        case 'unavailable':
            return '×';
        default:
            return '-';
    }
}

// 可用性の色を取得
function getAvailabilityColor($availability) {
    switch ($availability) {
        case 'available':
            return 'success';
        case 'maybe':
            return 'warning';
        case 'unavailable':
            return 'danger';
        default:
            return 'secondary';
    }
}
