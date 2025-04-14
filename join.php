<?php
// join.php - イベント参加ページ
require_once 'layout.php';
require_once 'functions.php';

$message = '';
$event = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'] ?? '';
    
    if (!empty($event_id)) {
        header("Location: event.php?id=" . $event_id);
        exit;
    } else {
        $message = '<div class="alert alert-danger">イベントIDを入力してください</div>';
    }
}

renderHeader('イベントに参加 - 調整さんアプリ');
?>

<h2 class="mb-4">イベントに参加</h2>

<?php echo $message; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">イベントIDを入力</div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="event_id" class="form-label">イベントID</label>
                        <input type="text" class="form-control" id="event_id" name="event_id" placeholder="例: abc123">
                    </div>
                    <button type="submit" class="btn btn-primary">参加する</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">URLから参加</div>
            <div class="card-body">
                <p>イベントのURLを受け取った場合は、そのリンクをクリックするだけで参加できます。</p>
                <p>例: <code>https://example.com/event.php?id=abc123</code></p>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <p>新しいイベントを作成したい場合は、<a href="create.php">イベント作成ページ</a>へ移動してください。</p>
</div>

<?php renderFooter(); ?>
