<?php
// 1. Xử lý Thêm người dùng mới[cite: 1]
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $type = intval($_POST['type']); // 1 = Admin, 2 = Staff[cite: 1]

    if (!empty($email) && !empty($password)) {
        // Kiểm tra xem email đã tồn tại chưa
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $res_check = $check_email->get_result();

        if ($res_check->num_rows > 0) {
            echo "<div class='alert alert-danger'>Email này đã tồn tại trên hệ thống!</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $firstname, $lastname, $email, $password, $type);
            if ($stmt->execute()) {
                echo "<script>alert('Tạo người dùng mới thành công!'); window.location.href='index.php?page=users';</script>";
                exit();
            } else {
                echo "<div class='alert alert-danger'>Lỗi thêm người dùng: " . $conn->error . "</div>";
            }
        }
    } else {
        echo "<div class='alert alert-warning'>Vui lòng nhập đầy đủ Email và Mật khẩu!</div>";
    }
}

// 2. Xử lý Cập nhật người dùng[cite: 1]
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $user_id = intval($_POST['user_id']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $type = intval($_POST['type']);

    if ($user_id > 0 && !empty($email)) {
        if (!empty($password)) {
            // Nếu có nhập mật khẩu mới -> cập nhật cả mật khẩu
            $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, password = ?, type = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $firstname, $lastname, $email, $password, $type, $user_id);
        } else {
            // Nếu để trống mật khẩu -> giữ nguyên mật khẩu cũ
            $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, type = ? WHERE id = ?");
            $stmt->bind_param("sssii", $firstname, $lastname, $email, $type, $user_id);
        }

        if ($stmt->execute()) {
            echo "<script>alert('Cập nhật thông tin thành công!'); window.location.href='index.php?page=users';</script>";
            exit();
        } else {
            echo "<div class='alert alert-danger'>Lỗi cập nhật: " . $conn->error . "</div>";
        }
    }
}

// 3. Xử lý Xóa người dùng
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    // Bảo vệ: Không cho phép tự xóa tài khoản đang đăng nhập
    if ($delete_id == $_SESSION['user_id']) {
        echo "<script>alert('Bạn không thể tự xóa tài khoản đang đăng nhập!'); window.location.href='index.php?page=users';</script>";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            echo "<script>alert('Xóa người dùng thành công!'); window.location.href='index.php?page=users';</script>";
            exit();
        }
    }
}

// Lấy thông tin người dùng cần sửa (nếu có)[cite: 1]
$edit_user = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM users WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_user = $res->fetch_assoc();
    }
}

// Lấy danh sách người dùng[cite: 1]
$users_list = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<!-- Form Thêm / Sửa Người dùng -->
<div class="card p-4 mb-4 shadow-sm border-0">
    <h4 class="text-primary font-weight-bold">
        <?= $edit_user ? 'Chỉnh sửa tài khoản' : 'Thêm người dùng mới' ?>
    </h4>
    <form method="POST" action="index.php?page=users" class="mt-3">
        <?php if ($edit_user): ?>
            <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Họ</label>
                <input type="text" name="firstname" class="form-control" 
                       value="<?= htmlspecialchars($edit_user['firstname'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Tên</label>
                <input type="text" name="lastname" class="form-control" 
                       value="<?= htmlspecialchars($edit_user['lastname'] ?? '') ?>" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Email</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Mật khẩu</label>
                <input type="password" name="password" class="form-control" 
                       placeholder="<?= $edit_user ? 'Để trống nếu giữ nguyên mật khẩu cũ' : 'Nhập mật khẩu...' ?>" 
                       <?= $edit_user ? '' : 'required' ?>>
            </div>
        </div>

        <div class="form-group">
            <label class="font-weight-bold">Vai trò người dùng</label>
            <select name="type" class="form-control">
                <option value="2" <?= (isset($edit_user) && $edit_user['type'] == 2) ? 'selected' : '' ?>>Nhân viên (Staff)</option>
                <option value="1" <?= (isset($edit_user) && $edit_user['type'] == 1) ? 'selected' : '' ?>>Quản Trị Viên (Admin)</option>
            </select>
        </div>

        <div>
            <?php if ($edit_user): ?>
                <button type="submit" name="update_user" class="btn btn-warning font-weight-bold">Lưu thay đổi</button>
                <a href="index.php?page=users" class="btn btn-secondary">Hủy</a>
            <?php else: ?>
                <button type="submit" name="add_user" class="btn btn-primary font-weight-bold">Tạo tài khoản</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Bảng Danh sách người dùng -->
<div class="card p-4 shadow-sm border-0">
    <h4 class="font-weight-bold text-dark mb-3">Danh sách người dùng (User List)</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="thead-dark">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Tên người dùng</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th style="width: 180px;">Ngày tạo</th>
                    <th style="width: 150px;" class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_list && $users_list->num_rows > 0): ?>
                    <?php $stt = 1; while ($u = $users_list->fetch_assoc()): ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td class="font-weight-bold text-primary"><?= htmlspecialchars($u['firstname'] . ' ' . $u['lastname']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php if ($u['type'] == 1): ?>
                                <span class="badge badge-danger p-2">Quản trị viên</span>
                            <?php else: ?>
                                <span class="badge badge-info p-2">Nhân viên</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($u['date_created'])) ?></td>
                        <td class="text-center">
                            <a href="index.php?page=users&action=edit&id=<?= $u['id'] ?>" 
                               class="btn btn-sm btn-info mr-1">Sửa</a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <a href="index.php?page=users&action=delete&id=<?= $u['id'] ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này không?')">Xóa</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Chưa có người dùng nào trong hệ thống.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>