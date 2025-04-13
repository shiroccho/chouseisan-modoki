<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventName = $_POST['event_name'];
    $candidateDatetimes = $_POST['candidate_datetimes']; // 例: "2025-04-15 10:00,2025-04-16 13:00"

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO events (name) VALUES (:name) RETURNING id");
        $stmt->bindParam(':name', $eventName);
        $stmt->execute();
        $eventId = $stmt->fetchColumn();

        $datetimes = explode(',', $candidateDatetimes);
        foreach ($datetimes as $datetime) {
            $datetime = trim($datetime);
            $stmt = $pdo->prepare("INSERT INTO candidates (event_id, datetime) VALUES (:event_id, :datetime)");
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':datetime', $datetime);
            $stmt->execute();
        }

        $pdo->commit();
        header("Location: view_event.php?id=" . $eventId); // 作成したイベントの詳細ページへリダイレクト
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("イベント作成エラー: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>イベント作成</title>
</head>
<body>
    <h1>イベント作成</h1>
    <form method="POST">
        <div>
            <label for="event_name">イベント名:</label>
            <input type="text" name="event_name" required>
        </div>
        <div>
            <label for="candidate_datetimes">候補日時 (カンマ区切り):</label>
            <input type="text" name="candidate_datetimes" placeholder="YYYY-MM-DD HH:MM,YYYY-MM-DD HH:MM" required>
        </div>
        <button type="submit">作成</button>
    </form>
</body>
</html>