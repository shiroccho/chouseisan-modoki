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

// 集計結果の取得
$summary = getSummary($event_id);

// 参加者リストの取得
$participants = getParticipants($event_id);

renderHeader(htmlspecialchars($event['title']) . ' - ' . SITE_NAME);
?>

<div class="container mt-5">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><?php echo htmlspecialchars($event['title']); ?></h1>
            <?php if (!empty($event['description'])): ?>
                <p class="lead"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            <?php endif; ?>
<p>
                主催者: <?php echo htmlspecialchars($event['creator_name']); ?><br>
                作成日時: <?php echo date('Y年n月j日 H:i', strtotime($event['created_at'])); ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="respond.php?id=<?php echo $event_id; ?>" class="btn btn-primary mb-2">回答する</a>
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="shareUrl" value="<?php echo BASE_URL . 'event.php?id=' . $event_id; ?>" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="copyShareUrl()">
                    <i class="fas fa-copy"></i> コピー
                </button>
            </div>
        </div>
    </div>
    
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button" role="tab">集計結果</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail" type="button" role="tab">参加者一覧</button>
        </li>
    </ul>
    
    <div class="tab-content" id="myTabContent">
        <!-- 集計結果タブ -->
        <div class="tab-pane fade show active" id="summary" role="tabpanel" aria-labelledby="summary-tab">
            <?php if (empty($summary)): ?>
                <div class="alert alert-info">日程候補がありません。</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="table-light">
                                <th>日程</th>
                                <th class="text-center">○</th>
                                <th class="text-center">△</th>
                                <th class="text-center">×</th>
                                <th class="text-center">合計</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary as $index => $row): ?>
                                <tr <?php echo ($index === 0) ? 'class="table-success"' : ''; ?>>
                                    <td>
                                        <?php echo formatDate($row['date']); ?>
                                        <?php if ($row['start_time'] || $row['end_time']): ?>
                                            <br>
                                            <small>
                                                <?php echo formatTime($row['start_time']); ?>
                                                <?php if ($row['end_time']): ?>
                                                    - <?php echo formatTime($row['end_time']); ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo $row['available_count']; ?></td>
                                    <td class="text-center"><?php echo $row['maybe_count']; ?></td>
                                    <td class="text-center"><?php echo $row['unavailable_count']; ?></td>
                                    <td class="text-center"><?php echo $row['total_responses']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="respond.php?id=<?php echo $event_id; ?>" class="btn btn-primary">回答する</a>
            </div>
        </div>
        
        <!-- 参加者一覧タブ -->
        <div class="tab-pane fade" id="detail" role="tabpanel" aria-labelledby="detail-tab">
            <?php if (empty($participants)): ?>
                <div class="alert alert-info">まだ回答がありません。</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="table-light">
                                <th>参加者</th>
                                <?php foreach ($date_options as $option): ?>
                                    <th class="text-center">
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
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $participant): 
                                $responses = getParticipantResponses($event_id, $participant['participant_email']);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($participant['participant_name']); ?></td>
                                    <?php foreach ($date_options as $option): 
                                        $response = $responses[$option['id']] ?? ['availability' => null, 'comment' => ''];
                                        $bgClass = '';
                                        switch ($response['availability']) {
                                            case 'available': $bgClass = 'table-success'; break;
                                            case 'maybe': $bgClass = 'table-warning'; break;
                                            case 'unavailable': $bgClass = 'table-danger'; break;
                                            default: $bgClass = ''; break;
                                        }
                                    ?>
                                        <td class="text-center <?php echo $bgClass; ?>" title="<?php echo htmlspecialchars($response['comment']); ?>">
                                            <?php echo getAvailabilityText($response['availability']); ?>
                                            <?php if (!empty($response['comment'])): ?>
                                                <i class="fas fa-comment-dots ms-1"></i>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function copyShareUrl() {
    const shareUrlInput = document.getElementById('shareUrl');
    shareUrlInput.select();
    document.execCommand('copy');
    alert('URLをコピーしました');
}
</script>

<?php renderFooter(); ?>
