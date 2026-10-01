<?php
// Kiểm tra phân quyền: Nếu không phải Quản trị viên (type = 1) thì chặn truy cập
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 1) {
    echo "<div class='alert alert-danger p-3 font-weight-bold'>
            ⚠ Bạn không có quyền truy cập hoặc thay đổi Khuyến mãi! 
            <br><small class='font-weight-normal'>Tính năng này chỉ dành cho tài khoản Quản trị viên (Admin).</small>
          </div>";
    return;
}

// 1. Xử lý Thêm khuyến mãi mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_promo'])) {
    $name_promo  = trim($_POST['name_promo']);
    $discount    = floatval($_POST['discount']);
    $create_date = $_POST['create_date'];
    $end_date    = $_POST['end_date'];

    if (!empty($name_promo) && $discount > 0) {
        $stmt = $conn->prepare("INSERT INTO promo (name_promo, discount, create_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $name_promo, $discount, $create_date, $end_date);
        if ($stmt->execute()) {
            echo "<script>alert('Thêm mã khuyến mãi thành công!'); window.location.href='index.php?page=promo';</script>";
            exit();
        } else {
            echo "<div class='alert alert-danger'>Lỗi thêm khuyến mãi: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Vui lòng điền đầy đủ thông tin khuyến mãi!</div>";
    }
}

// 2. Xử lý Cập nhật / Sửa khuyến mãi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_promo'])) {
    $promo_id   = intval($_POST['promo_id']);
    $name_promo  = trim($_POST['name_promo']);
    $discount    = floatval($_POST['discount']);
    $create_date = $_POST['create_date'];
    $end_date    = $_POST['end_date'];

    if ($promo_id > 0 && !empty($name_promo)) {
        $stmt = $conn->prepare("UPDATE promo SET name_promo = ?, discount = ?, create_date = ?, end_date = ? WHERE id = ?");
        $stmt->bind_param("sdssi", $name_promo, $discount, $create_date, $end_date, $promo_id);
        if ($stmt->execute()) {
            echo "<script>alert('Cập nhật khuyến mãi thành công!'); window.location.href='index.php?page=promo';</script>";
            exit();
        } else {
            echo "<div class='alert alert-danger'>Lỗi cập nhật: " . $conn->error . "</div>";
        }
    }
}

// 3. Xử lý Xóa mã khuyến mãi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    
    // Kiểm tra xem mã khuyến mãi đã từng được áp dụng cho hóa đơn bán vé nào chưa
    $check = $conn->query("SELECT COUNT(*) as count FROM ticket_list WHERE promo_id = $delete_id");
    $row_check = $check ? $check->fetch_assoc() : ['count' => 0];

    if ($row_check['count'] > 0) {
        echo "<script>alert('Không thể xóa! Mã khuyến mãi này đã được sử dụng trong hóa đơn bán vé.'); window.location.href='index.php?page=promo';</script>";
    } else {
        $stmt = $conn->prepare("DELETE FROM promo WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            echo "<script>alert('Đã xóa mã khuyến mãi thành công!'); window.location.href='index.php?page=promo';</script>";
            exit();
        } else {
            echo "<div class='alert alert-danger'>Lỗi khi xóa: " . $conn->error . "</div>";
        }
    }
}

// Lấy thông tin mã khuyến mãi cần sửa (nếu có)
$edit_promo = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $edit_id = intval($_GET['id']);
    $res = $conn->query("SELECT * FROM promo WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_promo = $res->fetch_assoc();
    }
}

// Lấy danh sách khuyến mãi
$promos = $conn->query("SELECT * FROM promo ORDER BY id DESC");
?>

<!-- Form Thêm / Sửa Khuyến mãi -->
<div class="card p-4 mb-4 shadow-sm border-0">
    <h4 class="text-primary font-weight-bold">
        <?= $edit_promo ? 'Chỉnh sửa chương trình khuyến mãi' : 'Thêm mã khuyến mãi mới' ?>
    </h4>
    <form method="POST" action="index.php?page=promo" class="mt-3">
        <?php if ($edit_promo): ?>
            <input type="hidden" name="promo_id" value="<?= $edit_promo['id'] ?>">
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Tên chương trình / Mã KM</label>
                <input type="text" name="name_promo" class="form-control" 
                       value="<?= htmlspecialchars($edit_promo['name_promo'] ?? '') ?>" 
                       placeholder="Chương trình giảm giá..." required>
            </div>
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Mức giảm giá (%)</label>
                <input type="number" step="0.1" name="discount" class="form-control" 
                       value="<?= htmlspecialchars($edit_promo['discount'] ?? '') ?>" 
                       placeholder="Nhập số % giảm (VD: 10)" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Ngày bắt đầu</label>
                <input type="datetime-local" name="create_date" class="form-control" 
                       value="<?= isset($edit_promo['create_date']) ? date('Y-m-d\TH:i', strtotime($edit_promo['create_date'])) : '' ?>" required>
            </div>
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Ngày kết thúc</label>
                <input type="datetime-local" name="end_date" class="form-control" 
                       value="<?= isset($edit_promo['end_date']) ? date('Y-m-d\TH:i', strtotime($edit_promo['end_date'])) : '' ?>" required>
            </div>
        </div>

        <div>
            <?php if ($edit_promo): ?>
                <button type="submit" name="update_promo" class="btn btn-warning font-weight-bold">Lưu thay đổi</button>
                <a href="index.php?page=promo" class="btn btn-secondary">Hủy</a>
            <?php else: ?>
                <button type="submit" name="add_promo" class="btn btn-info font-weight-bold">Tạo khuyến mãi</button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Bảng Danh sách Khuyến mãi -->
<div class="card p-4 shadow-sm border-0">
    <h4 class="font-weight-bold text-dark mb-3">Danh sách khuyến mãi (Promo List)</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="thead-dark">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Tên khuyến mãi</th>
                    <th>Mức giảm giá</th>
                    <th>Ngày bắt đầu</th>
                    <th>Ngày kết thúc</th>
                    <th style="width: 150px;" class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($promos && $promos->num_rows > 0): ?>
                    <?php $stt = 1; while ($pr = $promos->fetch_assoc()): ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td class="font-weight-bold text-primary"><?= htmlspecialchars($pr['name_promo']) ?></td>
                        <td><span class="badge badge-success p-2" style="font-size: 0.9rem;"><?= $pr['discount'] ?>%</span></td>
                        <td><?= date('d/m/Y H:i', strtotime($pr['create_date'])) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($pr['end_date'])) ?></td>
                        <td class="text-center">
                            <a href="index.php?page=promo&action=edit&id=<?= $pr['id'] ?>" 
                               class="btn btn-sm btn-info mr-1">Sửa</a>
                            <a href="index.php?page=promo&action=delete&id=<?= $pr['id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa mã khuyến mãi này không?')">Xóa</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Chưa có chương trình khuyến mãi nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>