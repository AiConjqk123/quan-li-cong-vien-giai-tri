<?php
// Truy vấn danh sách vé kết hợp lấy tên nhân viên thực hiện bán vé
$tickets = $conn->query("
    SELECT t.*, 
           p.name as ticket_name, 
           pm.type as payment_type, 
           pr.name_promo,
           CONCAT(u.firstname, ' ', u.lastname) as seller_name
    FROM ticket_list t
    LEFT JOIN pricing p ON t.pricing_id = p.id
    LEFT JOIN payment pm ON t.payment_id = pm.id
    LEFT JOIN promo pr ON t.promo_id = pr.id
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.id DESC
");
?>

<div class="card p-4 shadow-sm border-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="font-weight-bold text-primary mb-0">Danh sách vé đã bán </h4>
        <a href="index.php?page=tickets" class="btn btn-success font-weight-bold">+ Bán vé mới</a>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle mb-0">
            <thead class="thead-dark">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Ngày bán</th>
                    <th>Tên khách hàng</th>
                    <th>Loại vé</th>
                    <th>Số lượng</th>
                    <th>Thanh toán</th>
                    <th>Khuyến mãi</th>
                    <th>Người bán</th>
                    <th>Tổng tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tickets && $tickets->num_rows > 0): ?>
                    <?php $stt = 1; while ($row = $tickets->fetch_assoc()): ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td><?= date('d/m/Y H:i:s', strtotime($row['date_created'])) ?></td>
                        <td class="font-weight-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['ticket_name'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge badge-secondary p-2">
                                <?= $row['no_adult'] ?> NL / <?= $row['no_child'] ?> TE
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-info p-2">
                                <?= htmlspecialchars($row['payment_type'] ?? 'Tiền mặt') ?>
                            </span>
                        </td>
                        <td>
                            <?= $row['name_promo'] ? '<span class="badge badge-success p-2">'.htmlspecialchars($row['name_promo']).'</span>' : '<span class="text-muted">Không áp dụng</span>' ?>
                        </td>
                        <td class="font-weight-bold text-primary">
                            👤 <?= htmlspecialchars($row['seller_name'] ?? 'Hệ thống') ?>
                        </td>
                        <td class="text-danger font-weight-bold">
                            <?= number_format($row['amount'], 0, '.', ',') ?> VNĐ
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">Chưa có giao dịch bán vé nào trong hệ thống.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>