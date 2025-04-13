<?php
// index.php - トップページ
require_once 'layout.php';
renderHeader();
?>

<div class="px-4 py-5 my-5 text-center">
    <h1 class="display-5 fw-bold">簡単日程調整</h1>
    <div class="col-lg-6 mx-auto">
        <p class="lead mb-4">
            複数人での日程調整を簡単に。イベントを作成して参加者と予定を合わせましょう。
        </p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <a href="create.php" class="btn btn-primary btn-lg px-4 gap-3">イベントを作成する</a>
            <a href="join.php" class="btn btn-outline-secondary btn-lg px-4">イベントに参加する</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">簡単作成</h5>
                <p class="card-text">イベント名と候補日を入力するだけで、すぐに日程調整を始められます。</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">簡単共有</h5>
                <p class="card-text">イベントURLを共有するだけで、誰でも簡単に回答できます。登録不要です。</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">結果が一目瞭然</h5>
                <p class="card-text">参加者の回答がリアルタイムで集計され、最適な日程がすぐにわかります。</p>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
