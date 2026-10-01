<div class="bg-dark text-white p-3 sidebar">
    <h5 class="text-uppercase border-bottom pb-2 text-warning font-weight-bold">
        <?= (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1) ? 'QUẢN TRỊ' : 'NHÂN VIÊN' ?>
    </h5>
    <ul class="nav flex-column mt-3">
        <li class="nav-item mb-2">
            <a href="index.php?page=home" class="nav-link text-white">🏠 Trang chủ</a>
        </li>
        
        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 1): ?>
            <li class="nav-item mb-2">
                <a href="index.php?page=games" class="nav-link text-white">🎮 Trò chơi</a>
            </li>
            <li class="nav-item mb-2">
                <a href="index.php?page=pricing" class="nav-link text-white">💵 Giá Cả</a>
            </li>
            <li class="nav-item mb-2">
                <a href="index.php?page=promo" class="nav-link text-white">🎁 Khuyến mãi</a>
            </li>
            <li class="nav-item mb-2">
                <a href="index.php?page=reports" class="nav-link text-white">📊 Báo Cáo Bán Hàng</a>
            </li>
            <li class="nav-item mb-2">
                <a href="index.php?page=users" class="nav-link text-white">👤 Người Dùng</a>
            </li>
        <?php endif; ?>

        <li class="nav-item mb-2">
            <a href="index.php?page=tickets" class="nav-link text-white">🎟️ Bán vé</a>
        </li>
        <li class="nav-item mb-2">
            <a href="index.php?page=ticket_list" class="nav-link text-white">📋 Danh sách vé</a>
        </li>
        <li class="nav-item mt-5 border-top pt-3">
            <a href="logout.php" class="nav-link text-danger">🚪 Đăng xuất</a>
        </li>
    </ul>
</div>