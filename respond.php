<?php
require_once 'functions.php';
require_once 'layout.php';

// イベントIDの取得
$event_id = $_GET['id'] ?? '';

if (empty($event_id)) {
    renderHeader('エラー - ' . SITE_NAME);
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">イベントIDが指定されていません。</div>';
    echo '<a href="index.php" class="btn btn-primary">ホームに戻る</a>';
    echo '</div>';
    renderFooter();
    exit;
}

// イベント情報の取得
$event = getEvent($event_id);

if (!$event) {
    renderHeader('エラー - ' . SITE_NAME);
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-danger">指定されたイベントは存在しません。</div>';
    echo '<a href="index.php" class="btn btn-primary">ホームに戻る</a>';
    echo '</div>';
    renderFooter();
    exit;
}

// 日程候補の取得
$date_options = getDateOptions($event_id);

// フォームの送信かどうかを確認
$is_edit_mode = isset($_GET['email']) && !empty($_GET['email']);
$participant_name = '';
$participant_email = '';
$participant_responses = [];

if ($is_edit_mode) {
    $participant_email = $_GET['email'];
    $participants = getParticipants($event_id);
    
    foreach ($participants as $participant) {
        if ($participant['participant_email'] === $participant_email) {
            $participant_name = $participant['participant_name'];
            break;
        }
    }
    
    $participant_responses = getParticipantResponses($event_id, $participant_email);
}

renderHeader('回答 - ' . htmlspecialchars($event['title']) . ' - ' . SITE_NAME);
?>

<div class="container mt-5">
    <h1 class="mb-4"><?php echo htmlspecialchars($event['title']); ?>の出欠回答</h1>
    
    <?php if (!empty($date_options)): ?>
        <form action="submit_response.php" method="post">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
            
            <div class="card mb-4">
                <div class="card-header">
                    参加者情報
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="participant_name" class="form-label">お名前 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="participant_name" name="participant_name" value="<?php echo htmlspecialchars($participant_name); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="participant_email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="participant_email" name="participant_email" value="<?php echo htmlspecialchars($participant_email); ?>" required>
                        <div class="form-text">※同じメールアドレスでの回答は上書きされます</div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    日程候補の回答
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="table-light">
                                    <th>日程</th>
                                    <th class="text-center">回答</th>
                                    <th>コメント</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($date_options as $option): 
                                    $response = $participant_responses[$option['id']] ?? ['availability' => null, 'comment' => ''];
                                ?>
                                    <tr>
                                        <td>
                                            <?php echo formatDate($option['date']); ?>
                                            <?php if ($option['start_time'] || $option['end_time']): ?>
                                                <br>
                                                <small>
                                                    <?php echo formatTime($option['start_time']); ?>
                                                    <?php if ($option['end_time']): ?>
                                                        - <?php echo formatTime($option['end_time']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            <?php endif; ?>
                                            <input type="hidden" name="date_option_ids[]" value="<?php echo $option['id']; ?>">
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <input type="radio" class="btn-check" name="availability[<?php echo $option['id']; ?>]" id="available_<?php echo $option['id']; ?>" value="available" <?php echo ($response['availability'] === 'available') ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-success" for="available_<?php echo $option['id']; ?>">○</label>
                                                
                                                <input type="radio" class="btn-check" name="availability[<?php echo $option['id']; ?>]" id="maybe_<?php echo $option['id']; ?>" value="maybe" <?php echo ($response['availability'] === 'maybe') ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-warning" for="maybe_<?php echo $option['id']; ?>">△</label>
                                                
                                                <input type="radio" class="btn-check" name="availability[<?php echo $option['id']; ?>]" id="unavailable_<?php echo $option['id']; ?>" value="unavailable" <?php echo ($response['availability'] === 'unavailable') ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-danger" for="unavailable_<?php echo $option['id']; ?>">×</label>
                                                
                                                <input type="radio" class="btn-check" name="availability[<?php echo $option['id']; ?>]" id="noresponse_<?php echo $option['id']; ?>" value="" <?php echo ($response['availability'] === null) ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-secondary" for="noresponse_<?php echo $option['id']; ?>">未回答</label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="comment[<?php echo $option['id']; ?>]" value="<?php echo htmlspecialchars($response['comment']); ?>" placeholder="備考">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <button type="submit" class="btn btn-primary">回答を送信</button>
                <a href="event.php?id=<?php echo $event_id; ?>" class="btn btn-secondary">戻る</a>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            日程候補がありません。
        </div>
        <a href="event.php?id=<?php echo $event_id; ?>" class="btn btn-primary">イベントページに戻る</a>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>
