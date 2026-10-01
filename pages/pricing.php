<?php
// Kiểm tra phân quyền: Nếu không phải Quản trị viên (type = 1) thì chặn truy cập
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 1) {
    echo "<div class='alert alert-danger p-3 font-weight-bold'>
            ⚠ Bạn không có quyền truy cập hoặc điều chỉnh Giá bán vé! 
            <br><small class='font-weight-normal'>Tính năng này chỉ dành cho tài khoản Quản trị viên (Admin).</small>
          </div>";
    return;
}

// 1. Xử lý Thêm bảng giá mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_pricing'])) {
    $name        = trim($_POST['name']);
    $game_id     = !empty($_POST['game_id']) ? intval($_POST['game_id']) : NULL;
    $adult_price = floatval($_POST['adult_price']);
    $child_price = floatval($_POST['child_price']);

    if (!empty($name) && $adult_price >= 0) {
        $stmt = $conn->prepare("INSERT INTO pricing (name, game_id, adult_price, child_price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sidd", $name, $game_id, $adult_price, $child_price);
        if ($stmt->execute()) {
            echo "<script>alert('Thêm bảng giá vé thành công!'); window.location.href='index.php?page=pricing';</script>";
            exit();
        } else {
            echo "<div class='alert alert-danger'>Lỗi thêm bảng giá: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Vui lòng nhập tên vé và mức giá hợp lệ!</div>";
    }
}

// 2. Xử lý Cập nhật / Sửa bảng giá
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pricing'])) {
    $pricing_id  = intval($_POST['pricing_id']);
    $name        = trim($_POST['name']);
    $game_id     = !empty($_POST['game_id']) ? intval($_POST['game_id']) : NULL;
    $adult_price = floatval($_POST['adult_price']);
    $child_price = floatval($_POST['child_price']);

    if ($pricing_id > 0 && !empty($name)) {
        $stmt = $conn->prepare("UPDATE pricing SET name = ?, game_id = ?, adult_price = ?, child_price = ? WHERE id = ?");
        $stmt->bind_param("siddi", $name, $game_id, $adult_price, $child_price, $pricing_id);
        if ($stmt->execute()) {
            echo "<script>alert('Cập nhật bảng giá thành công!'); window.location.href='index.php?page=pricing';</script>";
            exit();
        } else {
            echo "<div class='alert alert-danger'>Lỗi cập nhật: " . $conn->error . "</div>";
        }
    }
}

// 3. Xử lý Xóa bảng giá (Đã xử lý xóa các hóa đơn liên quan trước để tránh lỗi NOT NULL)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    // Tắt kiểm tra khóa ngoại tạm thời để xóa sạch dữ liệu liên quan không bị chặn
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Xóa các cuống vé và vé liên quan đến giá này
    $conn->query("DELETE FROM ticket_items WHERE ticket_id IN (SELECT id FROM ticket_list WHERE pricing_id = $delete_id)");
    $conn->query("DELETE FROM ticket_list WHERE pricing_id = $delete_id");

    // Tiến hành xóa giá vé
    $stmt = $conn->prepare("DELETE FROM pricing WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $executed = $stmt->execute();

    // Bật lại kiểm tra khóa ngoại
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    if ($executed) {
        echo "<script>alert('Đã xóa bảng giá thành công!'); window.location.href='index.php?page=pricing';</script>";
        exit();
    } else {
        echo "<div class='alert alert-danger'>Lỗi khi xóa: " . $conn->error . "</div>";
    }
}

// Lấy thông tin bảng giá cần sửa (nếu có)
$edit_pricing = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM pricing WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_pricing = $res->fetch_assoc();
    }
}

// Lấy danh sách trò chơi và bảng giá
$games = $conn->query("SELECT * FROM games ORDER BY game ASC");
$pricing_list = $conn->query("SELECT p.*, g.game FROM pricing p LEFT JOIN games g ON p.game_id = g.id ORDER BY p.id DESC");
?>

<!-- Form Thêm / Sửa Giá vé -->
<div class="card p-4 mb-4 shadow-sm border-0">
    <h4 class="text-primary font-weight-bold">
        <?= $edit_pricing ? 'Chỉnh sửa cấu hình giá vé' : 'Thêm bảng giá vé mới' ?>
    </h4>
    <form method="POST" action="index.php?page=pricing" class="mt-3">
        <?php if ($edit_pricing): ?>
            <input type="hidden" name="pricing_id" value="<?= $edit_pricing['id'] ?>">
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Tên gói / Tên loại vé</label>
                <input type="text" name="name" class="form-control" 
                       value="<?= htmlspecialchars($edit_pricing['name'] ?? '') ?>" 
                       placeholder="Ví dụ: Vé Vòng xoay khổng lồ, Vé trọn gói..." required>
            </div>
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Áp dụng cho trò chơi</label>
                <select name="game_id" class="form-control">
                    <option value="">-- Tất cả / Gói trọn gói --</option>
                    <?php 
                    if ($games && $games->num_rows > 0) {
                        mysqli_data_seek($games, 0);
                        while ($g = $games->fetch_assoc()): 
                            $selected = (isset($edit_pricing['game_id']) && $edit_pricing['game_id'] == $g['id']) ? 'selected' : '';
                    ?>
                        <option value="<?= $g['id'] ?>" <?= $selected ?>><?= htmlspecialchars($g['game']) ?></option>
                    <?php 
                        endwhile; 
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Giá người lớn (VNĐ)</label>
                <input type="number" step="5000" name="adult_price" class="form-control" 
                       value="<?= htmlspecialchars($edit_pricing['adult_price'] ?? '') ?>" 
                       placeholder="Ví dụ: 100000" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Giá trẻ em (VNĐ)</label>
                <input type="number" step="5000" name="child_price" class="form-control" 
                       value="<?= htmlspecialchars($edit_pricing['child_price'] ?? '') ?>" 
                       placeholder="Ví dụ: 50000" required>
            </div>
        </div>

        <div>
            <?php if ($edit_pricing): ?>
                <button type="submit" name="update_pricing" class="btn btn-warning font-weight-bold">Lưu thay đổi</button>
                <a href="index.php?page=pricing" class="btn btn-secondary">Hủy</a>
            <?php else: ?>
                <button type="submit" name="add_pricing" class="btn btn-primary font-weight-bold">Lưu bảng giá</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Bảng Danh sách Giá cả -->
<div class="card p-4 shadow-sm border-0">
    <h4 class="font-weight-bold text-dark mb-3">Danh sách giá cả (Pricing List)</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="thead-dark">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Tên vé</th>
                    <th>Trò chơi áp dụng</th>
                    <th>Giá người lớn</th>
                    <th>Giá trẻ em</th>
                    <th style="width: 150px;" class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pricing_list && $pricing_list->num_rows > 0): ?>
                    <?php $stt = 1; while ($p = $pricing_list->fetch_assoc()): ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td class="font-weight-bold text-primary"><?= htmlspecialchars($p['name']) ?></td>
                        <td><?= $p['game'] ? htmlspecialchars($p['game']) : '<span class="badge badge-secondary">Trọn gói</span>' ?></td>
                        <td class="text-success font-weight-bold">
                            <?= number_format($p['adult_price'], 0, '.', ',') ?> VNĐ
                        </td>
                        <td class="text-info font-weight-bold">
                            <?= number_format($p['child_price'], 0, '.', ',') ?> VNĐ
                        </td>
                        <td class="text-center">
                            <a href="index.php?page=pricing&action=edit&id=<?= $p['id'] ?>" 
                               class="btn btn-sm btn-info mr-1">Sửa</a>
                            <a href="index.php?page=pricing&action=delete&id=<?= $p['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa giá vé này không? Tất cả vé đã bán theo giá này cũng sẽ được dọn dẹp!')">Xóa</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Chưa có bảng giá vé nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>