<?php
// Kiểm tra phân quyền: Nếu không phải Quản trị viên (type = 1) thì chặn truy cập
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 1) {
    echo "<div class='alert alert-danger p-3 font-weight-bold'>
            ⚠ Bạn không có quyền truy cập hoặc chỉnh sửa danh mục Trò chơi! 
            <br><small class='font-weight-normal'>Tính năng này chỉ dành cho tài khoản Quản trị viên (Admin).</small>
          </div>";
    return;
}

// 1. Xử lý Thêm trò chơi mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_game'])) {
    $game_name = trim($_POST['game_name']);
    $description = trim($_POST['description']);

    if (!empty($game_name)) {
        $stmt = $conn->prepare("INSERT INTO games (game, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $game_name, $description);
        if ($stmt->execute()) {
            echo "<script>alert('Thêm trò chơi thành công!'); window.location.href='index.php?page=games';</script>";
            exit();
        } else {
            echo "<div class='alert alert-danger'>Lỗi thêm trò chơi: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Vui lòng nhập tên trò chơi!</div>";
    }
}

// 2. Xử lý Cập nhật / Sửa trò chơi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_game'])) {
    $game_id = intval($_POST['game_id']);
    $game_name = trim($_POST['game_name']);
    $description = trim($_POST['description']);

    if ($game_id > 0 && !empty($game_name)) {
        $stmt = $conn->prepare("UPDATE games SET game = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $game_name, $description, $game_id);
        if ($stmt->execute()) {
            echo "<script>alert('Cập nhật trò chơi thành công!'); window.location.href='index.php?page=games';</script>";
            exit();
        } else {
            echo "<div class='alert alert-danger'>Lỗi cập nhật: " . $conn->error . "</div>";
        }
    }
}

// 3. Xử lý Xóa trò chơi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    // Kiểm tra ràng buộc khóa ngoại trước khi xóa
    $check = $conn->query("SELECT COUNT(*) as count FROM pricing WHERE game_id = $delete_id");
    $row_check = $check ? $check->fetch_assoc() : ['count' => 0];

    if ($row_check['count'] > 0) {
        echo "<script>alert('Không thể xóa! Trò chơi này đang có trong bảng Giá vé.'); window.location.href='index.php?page=games';</script>";
    } else {
        $stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            echo "<script>alert('Đã xóa trò chơi thành công!'); window.location.href='index.php?page=games';</script>";
            exit();
        }
    }
}

// Lấy thông tin trò chơi cần sửa (nếu có)
$edit_game = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM games WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_game = $res->fetch_assoc();
    }
}

// Lấy danh sách trò chơi
$games_list = $conn->query("SELECT * FROM games ORDER BY id DESC");
?>

<!-- Form Thêm / Sửa trò chơi -->
<div class="card p-4 mb-4 shadow-sm border-0">
    <h4 class="text-primary font-weight-bold">
        <?= $edit_game ? 'Chỉnh sửa trò chơi' : 'Thêm trò chơi mới' ?>
    </h4>
    <form method="POST" action="index.php?page=games" class="mt-3">
        <?php if ($edit_game): ?>
            <input type="hidden" name="game_id" value="<?= $edit_game['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label class="font-weight-bold">Tên trò chơi</label>
            <input type="text" name="game_name" class="form-control" 
                   value="<?= htmlspecialchars($edit_game['game'] ?? '') ?>" 
                   placeholder="Nhập tên trò chơi..." required>
        </div>
        
        <div class="form-group">
            <label class="font-weight-bold">Mô tả</label>
            <textarea name="description" class="form-control" rows="3" 
                      placeholder="Nhập mô tả chi tiết..." required><?= htmlspecialchars($edit_game['description'] ?? '') ?></textarea>
        </div>

        <div>
            <?php if ($edit_game): ?>
                <button type="submit" name="update_game" class="btn btn-warning font-weight-bold">Lưu thay đổi</button>
                <a href="index.php?page=games" class="btn btn-secondary">Hủy</a>
            <?php else: ?>
                <button type="submit" name="add_game" class="btn btn-success font-weight-bold">Thêm trò chơi</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Bảng Danh sách trò chơi -->
<div class="card p-4 shadow-sm border-0">
    <h4 class="font-weight-bold text-dark mb-3">Danh sách trò chơi (Games List)</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="thead-dark">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Tên trò chơi</th>
                    <th>Mô tả</th>
                    <th style="width: 180px;">Ngày tạo</th>
                    <th style="width: 150px;" class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($games_list && $games_list->num_rows > 0): ?>
                    <?php $stt = 1; while ($row = $games_list->fetch_assoc()): ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td class="font-weight-bold text-primary"><?= htmlspecialchars($row['game']) ?></td>
                        <td><?= htmlspecialchars($row['description']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['date_created'])) ?></td>
                        <td class="text-center">
                            <a href="index.php?page=games&action=edit&id=<?= $row['id'] ?>" 
                               class="btn btn-sm btn-info mr-1">Sửa</a>
                            <a href="index.php?page=games&action=delete&id=<?= $row['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa trò chơi này không?')">Xóa</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">Chưa có trò chơi nào trong hệ thống.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>