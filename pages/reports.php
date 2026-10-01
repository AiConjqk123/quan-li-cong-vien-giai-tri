<?php
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

$query = "SELECT t.*, p.name as ticket_name, pm.type as payment_type, u.firstname 
          FROM ticket_list t
          LEFT JOIN pricing p ON t.pricing_id = p.id
          LEFT JOIN payment pm ON t.payment_id = pm.id
          LEFT JOIN users u ON t.user_id = u.id
          WHERE DATE(t.date_created) BETWEEN '$from_date' AND '$to_date'
          ORDER BY t.id DESC";
$result = $conn->query($query);
?>

<div class="card p-4 shadow-sm border-0">
    <h4 class="font-weight-bold text-primary">Báo cáo bán hàng</h4>
    <form method="GET" class="form-inline my-3">
        <input type="hidden" name="page" value="reports">
        <label class="mr-2">Từ ngày:</label>
        <input type="date" name="from_date" class="form-control mr-3" value="<?= $from_date ?>">
        <label class="mr-2">Đến ngày:</label>
        <input type="date" name="to_date" class="form-control mr-3" value="<?= $to_date ?>">
        <button type="submit" class="btn btn-primary mr-2">Xem Báo Cáo</button>
        <button type="button" onclick="window.print()" class="btn btn-success">🖨️ In Báo Cáo</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Ngày</th>
                <th>Khách hàng</th>
                <th>Vé cho</th>
                <th>Số lượng</th>
                <th>Trạng thái thanh toán</th>
                <th>Người bán</th>
                <th>Tổng số tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1; $total_sum = 0;
            while($row = $result->fetch_assoc()): 
                $total_sum += $row['amount'];
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= $row['date_created'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['ticket_name']) ?></td>
                <td><?= $row['no_adult'] ?> Lớn / <?= $row['no_child'] ?> Trẻ</td>
                <td><?= htmlspecialchars($row['payment_type']) ?></td>
                <td><?= htmlspecialchars($row['firstname']) ?></td>
                <td><?= number_format($row['amount'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="7" class="text-right">Tổng doanh số:</th>
                <th class="text-danger font-weight-bold"><?= number_format($total_sum, 2) ?> VNĐ</th>
            </tr>
        </tfoot>
    </table>
</div>