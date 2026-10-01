<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/db_connect.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Truy vấn tài khoản người dùng[cite: 1]
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
        $_SESSION['user_type'] = $user['type']; // 1 = Admin, 2 = Staff[cite: 1]
        header("Location: index.php");
        exit();
    } else {
        $error = "Email hoặc mật khẩu không chính xác!";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Quản lý Công viên Giải trí</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { background: #eef2f7; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .card-login { width: 380px; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>
<div class="card-login">
    <h4 class="text-center text-primary mb-4 font-weight-bold">Hệ thống quản lý vé công viên giải trí</h4>
    <?php if($error): ?>
        <div class="alert alert-danger p-2 small"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required placeholder="admin@gmail.com">
        </div>
        <div class="form-group">
            <label>Mật khẩu</label>
            <input type="password" name="password" class="form-control" required placeholder="••••••">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Đăng Nhập</button>
    </form>
</div>
</body>
</html>