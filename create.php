<?php
require_once 'layout.php';
renderHeader('イベント作成 - 調整さんアプリ');
?>

<div class="container mt-5">
    <h1 class="mb-4">イベント日程調整</h1>
    
    <div class="card">
        <div class="card-header">
            新しいイベントを作成
        </div>
        <div class="card-body">
            <form action="create_event.php" method="post" id="eventForm">
                <div class="mb-3">
                    <label for="title" class="form-label">イベント名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">イベント詳細</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="creator_name" class="form-label">主催者名 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="creator_name" name="creator_name" required>
                </div>
                
                <div class="mb-3">
                    <label for="creator_email" class="form-label">主催者メールアドレス <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="creator_email" name="creator_email" required>
                </div>
                
                <hr>
                
                <h5>日程候補</h5>
                <div id="dateOptions">
                    <div class="date-option mb-3 row">
                        <div class="col-md-4">
                            <label class="form-label">日付 <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="dates[]" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">開始時間</label>
                            <input type="time" class="form-control" name="start_times[]">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">終了時間</label>
                            <input type="time" class="form-control" name="end_times[]">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger mb-3 remove-date" style="display: none;">削除</button>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-secondary mb-3" id="addDateBtn">日程を追加</button>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">イベントを作成</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateOptions = document.getElementById('dateOptions');
        const addDateBtn = document.getElementById('addDateBtn');
        
        // 日程追加ボタンのイベントリスナー
        addDateBtn.addEventListener('click', function() {
            const dateOptionTemplate = document.querySelector('.date-option').cloneNode(true);
            dateOptionTemplate.querySelector('input[name="dates[]"]').value = '';
            dateOptionTemplate.querySelector('input[name="start_times[]"]').value = '';
            dateOptionTemplate.querySelector('input[name="end_times[]"]').value = '';
            
            const removeBtn = dateOptionTemplate.querySelector('.remove-date');
            removeBtn.style.display = 'block';
            removeBtn.addEventListener('click', function() {
                dateOptionTemplate.remove();
            });
            
            dateOptions.appendChild(dateOptionTemplate);
        });
        
        // フォーム送信前の検証
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            const dateInputs = document.querySelectorAll('input[name="dates[]"]');
            let validDates = 0;
            
            dateInputs.forEach(input => {
                if (input.value) {
                    validDates++;
                }
            });
            
            if (validDates === 0) {
                e.preventDefault();
                alert('少なくとも1つの日程候補を入力してください');
            }
        });
    });
</script>

<?php renderFooter(); ?>
