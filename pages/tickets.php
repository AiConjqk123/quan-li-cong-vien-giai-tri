<?php
// Xử lý khi nhấn nút Thanh Toán (Hoàn tất giao dịch)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_payment'])) {
    $customer_name = trim($_POST['customer_name']);
    $pricing_id    = intval($_POST['pricing_id']);
    $no_adult      = intval($_POST['no_adult']);
    $no_child      = intval($_POST['no_child']);
    $payment_id    = intval($_POST['payment_id']);
    $promo_id      = !empty($_POST['promo_id']) ? intval($_POST['promo_id']) : NULL;
    $amount        = floatval($_POST['amount']);
    $tendered      = floatval($_POST['tendered']);
    $user_id       = $_SESSION['user_id'];

    if ($tendered < $amount) {
        echo "<script>alert('Số tiền khách đưa (" . number_format($tendered, 0, '.', ',') . " VNĐ) nhỏ hơn tổng tiền phải trả (" . number_format($amount, 0, '.', ',') . " VNĐ)!'); history.back();</script>";
        exit();
    }

    // 1. Lưu hóa đơn vào ticket_list
    $stmt = $conn->prepare("INSERT INTO ticket_list (name, no_adult, no_child, pricing_id, amount, tendered, payment_id, promo_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiiddiii", $customer_name, $no_adult, $no_child, $pricing_id, $amount, $tendered, $payment_id, $promo_id, $user_id);

    if ($stmt->execute()) {
        $ticket_id = $conn->insert_id;

        // 2. Lấy game_id để sinh cuống vé chi tiết vào ticket_items
        $res_price = $conn->query("SELECT game_id FROM pricing WHERE id = $pricing_id");
        $game_id   = ($res_price && $res_price->num_rows > 0) ? $res_price->fetch_assoc()['game_id'] : NULL;

        if ($game_id) {
            for ($i = 0; $i < $no_adult; $i++) {
                $ticket_no = 'TKT-A-' . date('Ymd') . '-' . rand(1000, 9999);
                $stmt_item = $conn->prepare("INSERT INTO ticket_items (ticket_no, game_id, type, ticket_id) VALUES (?, ?, 1, ?)");
                $stmt_item->bind_param("sii", $ticket_no, $game_id, $ticket_id);
                $stmt_item->execute();
            }
            for ($j = 0; $j < $no_child; $j++) {
                $ticket_no = 'TKT-C-' . date('Ymd') . '-' . rand(1000, 9999);
                $stmt_item = $conn->prepare("INSERT INTO ticket_items (ticket_no, game_id, type, ticket_id) VALUES (?, ?, 2, ?)");
                $stmt_item->bind_param("sii", $ticket_no, $game_id, $ticket_id);
                $stmt_item->execute();
            }
        }

        $change = $tendered - $amount;
        // Hiển thị thông báo Alert chính xác số tiền cần trả lại khách hàng
        echo "<script>
            alert('Thanh toán thành công!\\n--------------------------------\\nTổng tiền phải trả: " . number_format($amount, 0, '.', ',') . " VNĐ\\nTiền khách đưa: " . number_format($tendered, 0, '.', ',') . " VNĐ\\nSố tiền trả lại khách: " . number_format($change, 0, '.', ',') . " VNĐ');
            window.location.href='index.php?page=ticket_list';
        </script>";
        exit();
    } else {
        echo "<div class='alert alert-danger'>Lỗi khi lưu vé: " . $conn->error . "</div>";
    }
}

// Lấy dữ liệu loại vé, phương thức thanh toán, khuyến mãi
$pricings = $conn->query("SELECT p.*, g.game FROM pricing p LEFT JOIN games g ON p.game_id = g.id ORDER BY p.id DESC");
$payments = $conn->query("SELECT * FROM payment");
$promos    = $conn->query("SELECT * FROM promo WHERE end_date >= NOW() OR end_date IS NULL");
?>

<!-- ĐỊNH DẠNG CSS GIAO DIỆN TỐI (DARK MODE) -->
<style>
    .card-dark { background-color: #1a1a1a; color: #ffffff; border: 1px solid #333333; }
    .card-inner { background-color: #262626; border: 1px solid #3a3a3a; }
    .form-control-dark { background-color: #121212 !important; color: #ffffff !important; border: 1px solid #444444 !important; }
    .form-control-dark:focus { border-color: #0d6efd !important; box-shadow: none; }
</style>

<div class="card p-4 shadow-sm border-0 card-dark">
    <h4 class="text-primary font-weight-bold mb-3">Bán Vé & Thanh Toán</h4>
    
    <form method="POST" action="index.php?page=tickets" id="ticketForm">
        <!-- BƯỚC 1: THÔNG TIN VÉ & XÁC NHẬN -->
        <div class="card p-3 mb-3 card-inner">
            <h6 class="font-weight-bold text-light border-bottom border-secondary pb-2 mb-3">Bước 1: Chọn Thông Tin & Xác Nhận Vé</h6>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="font-weight-bold">Tên Khách Hàng</label>
                    <input type="text" name="customer_name" id="customer_name" class="form-control form-control-dark" required placeholder="Nhập tên khách hàng...">
                </div>
                <div class="col-md-6 form-group">
                    <label class="font-weight-bold">Loại Vé / Trò Chơi</label>
                    <select name="pricing_id" id="pricing_select" class="form-control form-control-dark" required>
                        <option value="" data-adult="0" data-child="0">-- Chọn loại vé --</option>
                        <?php while($p = $pricings->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" data-adult="<?= $p['adult_price'] ?>" data-child="<?= $p['child_price'] ?>">
                                <?= htmlspecialchars($p['name']) ?> 
                                (NL: <?= number_format($p['adult_price'], 0, '.', ',') ?>đ | TE: <?= number_format($p['child_price'], 0, '.', ',') ?>đ)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 form-group">
                    <label class="font-weight-bold">Số Lượng Người Lớn</label>
                    <input type="number" name="no_adult" id="no_adult" class="form-control form-control-dark" value="1" min="0" required>
                </div>
                <div class="col-md-4 form-group">
                    <label class="font-weight-bold">Số Lượng Trẻ Em</label>
                    <input type="number" name="no_child" id="no_child" class="form-control form-control-dark" value="0" min="0" required>
                </div>
                <div class="col-md-4 form-group">
                    <label class="font-weight-bold">Mã Khuyến Mãi</label>
                    <select name="promo_id" id="promo_select" class="form-control form-control-dark">
                        <option value="" data-discount="0">-- Không áp dụng --</option>
                        <?php while($pr = $promos->fetch_assoc()): ?>
                            <option value="<?= $pr['id'] ?>" data-discount="<?= $pr['discount'] ?>">
                                <?= htmlspecialchars($pr['name_promo']) ?> (Giảm <?= $pr['discount'] ?>%)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <button type="button" id="btn_confirm_ticket" class="btn btn-primary font-weight-bold btn-block mt-2">
                🔍 Xác Nhận Vé & Tính Tiền
            </button>
        </div>

        <!-- BƯỚC 2: BẢNG BÊN DƯỚI HIỂN THỊ SỐ TIỀN VÀ Ô NHẬP TIỀN KHÁCH ĐƯA -->
        <div id="payment_section" class="card p-3 mb-3 card-inner border-primary shadow-sm" style="display: none;">
            <h6 class="font-weight-bold text-warning border-bottom border-secondary pb-2 mb-3">Bước 2: Chi Tiết Thanh Toán</h6>
            
            <div class="row align-items-center">
                <!-- SỐ TIỀN PHẢI TRẢ -->
                <div class="col-md-6 form-group">
                    <label class="font-weight-bold text-light mb-1">Số Tiền Phải Trả</label>
                    <h2 class="text-danger font-weight-bold mb-0">
                        <span id="total_amount_text">0</span> VNĐ
                    </h2>
                    <input type="hidden" name="amount" id="amount" value="0">
                </div>

                <!-- SỐ TIỀN KHÁCH ĐƯA -->
                <div class="col-md-6 form-group">
                    <label class="font-weight-bold text-light mb-1">Số Tiền Khách Đưa (VNĐ)</label>
                    <input type="number" step="1000" name="tendered" id="tendered" class="form-control form-control-lg form-control-dark text-warning font-weight-bold" placeholder="Nhập số tiền khách đưa..." required disabled>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-6 form-group">
                    <label class="font-weight-bold">Hình Thức Thanh Toán</label>
                    <select name="payment_id" class="form-control form-control-dark" required>
                        <?php while($pm = $payments->fetch_assoc()): ?>
                            <option value="<?= $pm['id'] ?>"><?= htmlspecialchars($pm['type']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 form-group d-flex align-items-end">
                    <button type="submit" name="complete_payment" id="btn_submit_payment" class="btn btn-success btn-block btn-lg font-weight-bold">
                        💵 Thanh Toán
                    </button>
                </div>
            </div>
            
            <div class="text-right mt-2">
                <button type="button" id="btn_edit_ticket" class="btn btn-link text-warning btn-sm p-0">✏️ Chọn lại loại vé khác</button>
            </div>
        </div>
    </form>
</div>

<!-- SCRIPT JS THUẦN (VANILLA JS - CHẠY CHẮC CHẮN 100%) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    var btnConfirm = document.getElementById('btn_confirm_ticket');
    var btnEdit = document.getElementById('btn_edit_ticket');
    var paymentSection = document.getElementById('payment_section');
    var tenderedInput = document.getElementById('tendered');

    // 1. Hàm tính số tiền vé
    function calculateTotal() {
        var pricingSelect = document.getElementById('pricing_select');
        var selectedOption = pricingSelect.options[pricingSelect.selectedIndex];

        var adultPrice = parseFloat(selectedOption.getAttribute('data-adult')) || 0;
        var childPrice = parseFloat(selectedOption.getAttribute('data-child')) || 0;

        var noAdult = parseInt(document.getElementById('no_adult').value) || 0;
        var noChild = parseInt(document.getElementById('no_child').value) || 0;

        var promoSelect = document.getElementById('promo_select');
        var promoOption = promoSelect.options[promoSelect.selectedIndex];
        var discountPercent = parseFloat(promoOption.getAttribute('data-discount')) || 0;

        var subtotal = (adultPrice * noAdult) + (childPrice * noChild);
        var totalAmount = subtotal - (subtotal * (discountPercent / 100));
        if (totalAmount < 0) totalAmount = 0;

        document.getElementById('total_amount_text').innerText = totalAmount.toLocaleString('en-US');
        document.getElementById('amount').value = totalAmount;
    }

    // 2. Bấm nút Xác Nhận Vé & Tính Tiền
    btnConfirm.addEventListener('click', function() {
        var customerName = document.getElementById('customer_name').value.trim();
        var pricingId = document.getElementById('pricing_select').value;

        if (customerName === '') {
            alert('Vui lòng nhập tên khách hàng!');
            document.getElementById('customer_name').focus();
            return;
        }

        if (!pricingId) {
            alert('Vui lòng chọn loại vé!');
            document.getElementById('pricing_select').focus();
            return;
        }

        // Tính tiền & Hiện bảng Bước 2 bên dưới
        calculateTotal();
        paymentSection.style.display = 'block';
        tenderedInput.disabled = false;
        tenderedInput.focus();

        // Khóa bớt thông tin Bước 1
        btnConfirm.disabled = true;
    });

    // 3. Bấm nút Chọn lại loại vé khác
    btnEdit.addEventListener('click', function() {
        paymentSection.style.display = 'none';
        tenderedInput.disabled = true;
        tenderedInput.value = '';
        btnConfirm.disabled = false;
    });
});
</script>