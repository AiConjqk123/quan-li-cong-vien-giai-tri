<?php
$games_count = 0;
$tickets_count = 0;
$today_revenue = 0;

// 1. Thống kê số lượng trò chơi
$res1 = $conn->query("SELECT COUNT(*) as total FROM games");
if ($res1) {
    $games_count = $res1->fetch_assoc()['total'];
}

// 2. Thống kê số lượng vé bán hôm nay
$res2 = $conn->query("SELECT COUNT(*) as total FROM ticket_list WHERE DATE(date_created) = CURDATE()");
if ($res2) {
    $tickets_count = $res2->fetch_assoc()['total'];
}

// 3. Thống kê tổng doanh thu tiền vé hôm nay
$res3 = $conn->query("SELECT SUM(amount) as total_amount FROM ticket_list WHERE DATE(date_created) = CURDATE()");
if ($res3) {
    $row3 = $res3->fetch_assoc();
    $today_revenue = $row3['total_amount'] ? floatval($row3['total_amount']) : 0;
}
?>

<style>
/* Khung chứa nền ảnh trang chủ */
.home-wallpaper {
    background: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), 
                url('https://photo.znews.vn/w1920/Uploaded/ayhunwa/2023_07_06/fw3.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    border-radius: 12px;
    padding: 30px;
    min-height: 80vh;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Kiểu dáng làm mờ và làm nổi bật các ô thống kê */
.stat-card {
    background: rgba(255, 255, 255, 0.92) !important;
    backdrop-filter: blur(8px);
    border-radius: 10px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25) !important;
}
</style>

<div class="home-wallpaper">
    <div class="d-flex justify-content-between align-items-center border-bottom border-light pb-2 mb-4">
        <h3 class="text-white font-weight-bold mb-0">Trang chủ</h3>
        <span class="text-light">Xin chào, <b><?= htmlspecialchars($_SESSION['user_name'] ?? 'Người dùng') ?></b>!</span>
    </div>

    <div class="row">
        <!-- Thẻ 1: Các trò chơi -->
        <div class="col-md-4 mb-4">
            <div class="card stat-card border-0 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="display-4 font-weight-bold text-primary mb-0"><?= $games_count ?></h1>
                        <p class="text-muted mb-0 font-weight-bold">Các trò chơi</p>
                    </div>
                    <div style="font-size: 3rem;">🎮</div>
                </div>
            </div>
        </div>

        <!-- Thẻ 2: Số vé bán hôm nay -->
        <div class="col-md-4 mb-4">
            <div class="card stat-card border-0 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h1 class="display-4 font-weight-bold text-info mb-0"><?= $tickets_count ?></h1>
                        <p class="text-muted mb-0 font-weight-bold">Lượt vé bán hôm nay</p>
                    </div>
                    <div style="font-size: 3rem;">🎟️</div>
                </div>
            </div>
        </div>

        <!-- Thẻ 3: Tổng doanh thu hôm nay -->
        <div class="col-md-4 mb-4">
            <div class="card stat-card border-0 p-3 shadow-sm h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="font-weight-bold text-success mb-1" style="font-size: 2rem;">
                            <?= number_format($today_revenue, 0, '.', ',') ?> VNĐ
                        </h2>
                        <p class="text-muted mb-0 font-weight-bold">Doanh thu hôm nay</p>
                    </div>
                    <div style="font-size: 3rem;">💰</div>
                </div>
            </div>
        </div>
    </div>
</div>