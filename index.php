<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra nếu chưa đăng nhập thì chuyển hướng về login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db_connect.php';

// Xác định trang cần hiển thị (Mặc định là 'home')
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hệ thống Quản lý Công viên Giải trí</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-content { min-height: 100vh; }
    </style>
</head>
<body>
<div class="d-flex">
    <!-- Nạp Menu Sidebar bên trái -->
    <?php 
        if (file_exists('includes/navbar.php')) {
            include 'includes/navbar.php'; 
        } else {
            echo "<div class='p-3 bg-dark text-white'>Thiếu file includes/navbar.php</div>";
        }
    ?>

    <!-- Khung nội dung hiển thị các trang -->
    <div class="flex-grow-1 p-4 main-content">
        <?php 
            $file = "pages/" . $page . ".php";
            if (file_exists($file)) {
                include $file;
            } else {
                echo "<div class='alert alert-warning'>Trang <b>" . htmlspecialchars($page) . "</b> chưa được khởi tạo!</div>";
            }
        ?>
    </div>
</div>
</body>
</html>