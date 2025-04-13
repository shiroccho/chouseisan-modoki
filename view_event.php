<?php
require 'config.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("イベントIDが無効です。");
}
$eventId = $_GET['id'];

// イベント情報の取得
$stmt = $pdo->prepare("SELECT name FROM events WHERE id = :id");
$stmt->bindParam(':id', $eventId);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("イベントが見つかりません。");
}

// 候補日時の取得
$stmt = $pdo->prepare("SELECT id, datetime FROM candidates WHERE event_id = :event_id ORDER BY datetime");
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 参加者の登録処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participant_name'])) {
    $participantName = $_POST['participant_name'];
    try {
        $stmt = $pdo->prepare("INSERT INTO participants (event_id, name) VALUES (:event_id, :name) RETURNING id");
        $stmt->bindParam(':event_id', $eventId);
        $stmt->bindParam(':name', $participantName);
        $stmt->execute();
        $participantId = $stmt->fetchColumn();
        // 参加者登録後に回答フォームを表示するために、参加者IDをセッションなどに保存するのも良いでしょう。
        $_SESSION['participant_id'] = $participantId;
    } catch (PDOException $e) {
        // 同一イベント内で同じ名前が登録された場合などのエラー処理
        $error = "参加者名の登録に失敗しました。";
    }
}

// 出欠の回答処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responses'])) {
    // 参加者IDの取得 (セッションから取得するなど)
    if (!isset($_SESSION['participant_id'])) {
        die("参加者IDが見つかりません。");
    }
    $participantId = $_SESSION['participant_id'];
    $responses = $_POST['responses']; // 例: ['1' => '〇', '2' => '×'] (candidate_id => status)

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO responses (participant_id, candidate_id, status) VALUES (:participant_id, :candidate_id, :status)
                               ON CONFLICT (participant_id, candidate_id) DO UPDATE SET status = :status");
        foreach ($responses as $candidateId => $status) {
            $stmt->bindParam(':participant_id', $participantId);
            $stmt->bindParam(':candidate_id', $candidateId);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
        }
        $pdo->commit();
        $message = "回答を送信しました。";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "回答の送信に失敗しました: " . $e->getMessage();
    }
}

// 結果の集計
$stmt = $pdo->prepare("
    SELECT
        c.datetime,
        SUM(CASE WHEN r.status = '〇' THEN 1 ELSE 0 END) AS yes,
        SUM(CASE WHEN r.status = '△' THEN 1 ELSE 0 END) AS maybe,
        SUM(CASE WHEN r.status = '×' THEN 1 ELSE 0 END) AS no
    FROM candidates c
    LEFT JOIN responses r ON c.id = r.candidate_id
    WHERE c.event_id = :event_id
    GROUP BY c.datetime
    ORDER BY c.datetime
");
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 参加者一覧の取得
$stmt = $pdo->prepare("SELECT name FROM participants WHERE event_id = :event_id ORDER BY name");
$stmt->bindParam(':event_id', $eventId);
$stmt->execute();
$participants = $stmt->fetchAll(PDO::FETCH_COLUMN);

session_start(); // セッションを開始
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($event['name']); ?></title>
    <style>
        table { border-collapse: collapse; width: 80%; }
        th, td { border: 1px solid black; padding: 8px; text-align: center; }
    </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($event['name']); ?></h1>

    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (isset($message)): ?>
        <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <h2>参加者登録</h2>
    <form method="POST">
        <div>
            <label for="participant_name">名前:</label>
            <input type="text" name="participant_name" required>
        </div>
        <button type="submit">登録</button>
    </form>

    <?php if (isset($_SESSION['participant_id']) && !empty($candidates)): ?>
        <h2>出欠回答</h2>
        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th>候補日時</th>
                        <th>〇</th>
                        <th>△</th>
                        <th>×</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidates as $candidate): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($candidate['datetime']); ?></td>
                            <td><input type="radio" name="responses[<?php echo $candidate['id']; ?>]" value="〇"></td>
                            <td><input type="radio" name="responses[<?php echo $candidate['id']; ?>]" value="△"></td>
                            <td><input type="radio" name="responses[<?php echo $candidate['id']; ?>]" value="×"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit">回答を送信</button>
        </form>
    <?php elseif (empty($candidates)): ?>
        <p>候補日時が登録されていません。</p>
    <?php elseif (!isset($_SESSION['participant_id'])): ?>
        <p>先に名前を登録してください。</p>
    <?php endif; ?>

    <h2>出欠状況</h2>
    <?php if (!empty($results)): ?>
        <table>
            <thead>
                <tr>
                    <th>候補日時</th>
                    <th>〇</th>
                    <th>△</th>
                    <th>×</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $result): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($result['datetime']); ?></td>
                        <td><?php echo $result['yes']; ?></td>
                        <td><?php echo $result['maybe']; ?></td>
                        <td><?php echo $result['no']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>まだ回答がありません。</p>
    <?php endif; ?>

    <h2>参加者一覧</h2>
    <?php if (!empty($participants)): ?>
        <ul>
            <?php foreach ($participants as $participant): ?>
                <li><?php echo htmlspecialchars($participant); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>まだ参加者はいません。</p>
    <?php endif; ?>
</body>
</html>