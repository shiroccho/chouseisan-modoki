<?php
require_once 'functions.php';

$event_id = $_GET['id'] ?? '';
if (empty($event_id)) {
    die('イベントIDが指定されていません');
}

// イベント情報を取得
$event = getEvent($event_id);
if (!$event) {
    die('指定されたイベントは存在しません');
}

// 日程候補を取得
$dateOptions = getDateOptions($event_id);

// 既存の回答を取得
$responses = getResponses($event_id);

// 回答の集計結果を取得
$summary = getSummary($event_id);

// 回答登録処理
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participant_name = $_POST['participant_name'] ?? '';
    $participant_email = $_POST['participant_email'] ?? '';
    $availabilities = $_POST['availability'] ?? [];
    $comment = $_POST['comment'] ?? '';
    
    // バリデーション
    $errors = [];
    
    if (empty($participant_name)) {
        $errors[] = '名前は必須です';
    }
    
    if (empty($participant_email) || !filter_var($participant_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '有効なメールアドレスを入力してください';
    }
    
    if (empty($availabilities)) {
        $errors[] = '少なくとも1つの日程に回答してください';
    }
    
    if (empty($errors)) {
        // 既存の回答を削除（更新のため）
        $pdo = connectDB();
        $stmt = $pdo->prepare("DELETE FROM responses WHERE event_id = :event_id AND participant_email = :participant_email");
        $stmt->bindParam(':event_id', $event_id);
        $stmt->bindParam(':participant_email', $participant_email);
        $stmt->execute();
        
        // 新しい回答を登録
        foreach ($availabilities as $date_option_id => $availability) {
            addResponse($event_id, $date_option_id, $participant_name, $participant_email, $availability);
        }
        
        $message = '回答を登録しました！';
        
        // 最新のデータを取得するために再読み込み
        $responses = getResponses($event_id);
        $summary = getSummary($event_id);
    } else {
        $message = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - 調整さんアプリ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/css/bootstrap.min.css">
    <style>
        .availability-option {
            cursor: pointer;
        }
        .availability-option:hover {
            opacity: 0.8;
        }
        .availability-option.selected {
            border: 2px solid #0d6efd;
        }
        .response-table th, .response-table td {
            text-align: center;
            vertical-align: middle;
        }
        .availability-available {
            background-color: #d1e7dd;
        }
        .availability-maybe {
            background-color: #fff3cd;
        }
        .availability-unavailable {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <?php if (!empty($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <h1 class="mb-3"><?php echo htmlspecialchars($event['title']); ?></h1>
        
        <div class="card mb-4">
            <div class="card-header">
                イベント詳細
            </div>
            <div class="card-body">
                <p><strong>主催者:</strong> <?php echo htmlspecialchars($event['creator_name']); ?></p>
                <?php if (!empty($event['description'])): ?>
                    <p><strong>詳細:</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                <?php endif; ?>
                <p><strong>作成日時:</strong> <?php echo date('Y年m月d日 H:i', strtotime($event['created_at'])); ?></p>
            </div>
        </div>
        
        <!-- 集計結果表示 -->
        <div class="card mb-4">
            <div class="card-header">
                回答状況
            </div>
            <div class="card-body">
                <?php if (count($responses) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered response-table">
                            <thead>
                                <tr>
                                    <th>日程</th>
                                    <?php foreach ($responses as $participant): ?>
                                        <th><?php echo htmlspecialchars($participant['participant_name']); ?></th>
                                    <?php endforeach; ?>
                                    <th>○</th>
                                    <th>△</th>
                                    <th>×</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary as $item): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            echo date('Y/m/d', strtotime($item['date']));
                                            if (!empty($item['start_time'])) {
                                                echo ' ' . date('H:i', strtotime($item['start_time']));
                                                if (!empty($item['end_time'])) {
                                                    echo '-' . date('H:i', strtotime($item['end_time']));
                                                }
                                            }
                                            ?>
                                        </td>
                                        
                                        <?php foreach ($responses as $participant): 
                                            $participant_responses = getParticipantResponses($event_id, $participant['participant_email']);
                                            $response = $participant_responses[$item['date_option_id']] ?? 'no_response';
                                            $class = '';
                                            $text = '';
                                            
                                            switch ($response) {
                                                case 'available':
                                                    $class = 'availability-available';
                                                    $text = '○';
                                                    break;
                                                case 'maybe':
                                                    $class = 'availability-maybe';
                                                    $text = '△';
                                                    break;
                                                case 'unavailable':
                                                    $class = 'availability-unavailable';
                                                    $text = '×';
                                                    break;
                                                default:
                                                    $text = '-';
                                            }
                                        ?>
                                            <td class="<?php echo $class; ?>"><?php echo $text; ?></td>
                                        <?php endforeach; ?>
                                        
                                        <td><?php echo $item['available_count']; ?></td>
                                        <td><?php echo $item['maybe_count']; ?></td>
                                        <td><?php echo $item['unavailable_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>まだ回答がありません。</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 回答入力フォーム -->
        <div class="card">
            <div class="card-header">
                出欠を入力する
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="participant_name" class="form-label">名前 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="participant_name" name="participant_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="participant_email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="participant_email" name="participant_email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">出欠状況を選択 <span class="text-danger">*</span></label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 40%;">日程</th>
                                        <th style="width: 20%;">○ 参加可能</th>
                                        <th style="width: 20%;">△ 調整中</th>
                                        <th style="width: 20%;">× 参加不可</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dateOptions as $option): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                echo date('Y/m/d', strtotime($option['date']));
                                                if (!empty($option['start_time'])) {
                                                    echo ' ' . date('H:i', strtotime($option['start_time']));
                                                    if (!empty($option['end_time'])) {
                                                        echo '-' . date('H:i', strtotime($option['end_time']));
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="availability[<?php echo $option['id']; ?>]" id="available_<?php echo $option['id']; ?>" value="available" required>
                                                    <label class="form-check-label" for="available_<?php echo $option['id']; ?>">○</label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="availability[<?php echo $option['id']; ?>]" id="maybe_<?php echo $option['id']; ?>" value="maybe">
                                                    <label class="form-check-label" for="maybe_<?php echo $option['id']; ?>">△</label>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="availability[<?php echo $option['id']; ?>]" id="unavailable_<?php echo $option['id']; ?>" value="unavailable">
                                                    <label class="form-check-label" for="unavailable_<?php echo $option['id']; ?>">×</label>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">コメント</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">回答を送信</button>
                </form>
            </div>
        </div>
        
        <!-- イベントURL共有 -->
        <div class="mt-4">
            <div class="input-group">
                <input type="text" class="form-control" id="eventUrl" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="copyEventUrl()">URLをコピー</button>
            </div>
            <small class="text-muted">このURLを共有して、参加者に回答を依頼しましょう。</small>
        </div>
    </div>
    
    <script>
        function copyEventUrl() {
            const eventUrlInput = document.getElementById('eventUrl');
            eventUrlInput.select();
            document.execCommand('copy');
            alert('URLをクリップボードにコピーしました');
        }
    </script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 回答フォームで同じメールアドレスの既存回答を読み込む
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('participant_email');
            
            emailInput.addEventListener('blur', function() {
                const email = this.value.trim();
                if (email && validateEmail(email)) {
                    // Ajaxで既存の回答を取得
                    fetch(`get_responses.php?event_id=<?php echo $event_id; ?>&email=${encodeURIComponent(email)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.name) {
                                document.getElementById('participant_name').value = data.name;
                            }
                            
                            if (data.responses) {
                                for (const [dateId, availability] of Object.entries(data.responses)) {
                                    const radio = document.getElementById(`${availability}_${dateId}`);
                                    if (radio) {
                                        radio.checked = true;
                                    }
                                }
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
            
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
        });
    </script>
</body>
</html>
