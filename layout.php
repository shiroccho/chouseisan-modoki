<?php
require_once 'config.php';

function renderHeader($title = SITE_NAME) {
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .availability-0 { color: #dc3545; } /* 不可 */
        .availability-1 { color: #ffc107; } /* 未定 */
        .availability-2 { color: #198754; } /* 可能 */
        .table-responsive { overflow-x: auto; }
        .navbar-brand { font-weight: bold; }
        .footer { margin-top: 50px; padding: 20px 0; background-color: #f8f9fa; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">ホーム</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create.php">イベント作成</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php
}

function renderFooter() {
?>
    <footer class="footer">
        <div class="container">
            <div class="text-center">
                <p>© <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - All Rights Reserved</p>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}

// アラートメッセージを表示する関数
function showAlert($message, $type = 'info') {
    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
    echo $message;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}
