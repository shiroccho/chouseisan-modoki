<?php
require_once 'layout.php';
renderHeader();
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="card-title mb-4">スケジュール調整アプリへようこそ</h1>
                    <p class="card-text">
                        このアプリを使って、イベントやミーティングの日程調整を簡単に行いましょう。
                        複数の候補日を登録し、参加者から都合の良い日を集めることができます。
                    </p>
                    <div class="mt-4">
                        <a href="create.php" class="btn btn-primary btn-lg">イベントを作成する</a>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h2 class="mb-0 h5">使い方</h2>
                </div>
                <div class="card-body">
                    <ol>
                        <li class="mb-2">「イベントを作成する」ボタンをクリックして、新しいイベントを作成します。</li>
                        <li class="mb-2">イベント名、説明、主催者情報を入力します。</li>
                        <li class="mb-2">候補となる日程を追加します。</li>
                        <li class="mb-2">作成したイベントのURLを参加者に共有します。</li>
                        <li class="mb-2">参加者は各日程について、参加可能かどうかを回答します。</li>
                        <li>集計結果を確認して、最適な日程を決定します。</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
