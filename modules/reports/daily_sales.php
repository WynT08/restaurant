<?php
$page_title = 'Báo cáo doanh thu';
include '../../includes/header.php';
requirePermission('manager');

// Get date range
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Get sales data
$query = "SELECT 
            DATE(created_at) as sale_date,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            SUM(subtotal) as subtotal,
            SUM(tax_amount) as total_tax,
            SUM(discount_amount) as total_discount,
            AVG(total_amount) as avg_order_value
          FROM orders
          WHERE DATE(created_at) BETWEEN :from_date AND :to_date
          AND payment_status = 'paid'
          GROUP BY DATE(created_at)
          ORDER BY sale_date DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':from_date', $from_date);
$stmt->bindParam(':to_date', $to_date);
$stmt->execute();
$sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totals = [
    'orders' => 0,
    'revenue' => 0,
    'tax' => 0,
    'discount' => 0
];

foreach ($sales_data as $row) {
    $totals['orders'] += $row['total_orders'];
    $totals['revenue'] += $row['total_revenue'];
    $totals['tax'] += $row['total_tax'];
    $totals['discount'] += $row['total_discount'];
}

// Get payment methods breakdown (schema payments table holds method)
$query = "SELECT 
                        p.payment_method,
                        COUNT(*) as count,
                        SUM(p.amount) as total
                    FROM payments p
                    JOIN orders o ON p.order_id = o.order_id
                    WHERE DATE(o.created_at) BETWEEN :from_date AND :to_date
                    AND o.payment_status = 'paid'
                    GROUP BY p.payment_method";

$stmt = $db->prepare($query);
$stmt->bindParam(':from_date', $from_date);
$stmt->bindParam(':to_date', $to_date);
$stmt->execute();
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get best selling items
$query = "SELECT 
            mi.item_name,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.subtotal) as total_revenue
          FROM order_items oi
          JOIN menu_items mi ON oi.item_id = mi.item_id
          JOIN orders o ON oi.order_id = o.order_id
          WHERE DATE(o.created_at) BETWEEN :from_date AND :to_date
          AND o.payment_status = 'paid'
          GROUP BY oi.item_id
          ORDER BY total_quantity DESC
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->bindParam(':from_date', $from_date);
$stmt->bindParam(':to_date', $to_date);
$stmt->execute();
$best_sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Báo cáo doanh thu</h1>
        <div>
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </button>
            <button class="btn btn-danger" onclick="exportToPDF()">
                <i class="fas fa-file-pdf"></i> Xuất PDF
            </button>
        </div>
    </div>
    
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end g-3">
                <div class="col-md-3">
                    <label class="form-label">Từ ngày</label>
                          <input type="date" name="from_date" class="form-control" 
                              value="<?php echo $from_date; ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Đến ngày</label>
                    <input type="date" name="to_date" class="form-control" 
                           value="<?php echo $to_date; ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Xem báo cáo
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="setToday()">
                        Hôm nay
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Tổng doanh thu</p>
                            <h3 class="mb-0 text-primary"><?php echo formatMoney($totals['revenue']); ?></h3>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Tổng đơn hàng</p>
                            <h3 class="mb-0 text-success"><?php echo $totals['orders']; ?></h3>
                        </div>
                        <i class="fas fa-receipt fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Trung bình/đơn</p>
                            <h3 class="mb-0 text-info">
                                <?php echo formatMoney($totals['orders'] > 0 ? $totals['revenue'] / $totals['orders'] : 0); ?>
                            </h3>
                        </div>
                        <i class="fas fa-chart-line fa-2x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Tổng giảm giá</p>
                            <h3 class="mb-0 text-warning"><?php echo formatMoney($totals['discount']); ?></h3>
                        </div>
                        <i class="fas fa-tag fa-2x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Sales by Date -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Doanh thu theo ngày</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="80"></canvas>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Chi tiết theo ngày</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Số đơn</th>
                                    <th>Doanh thu</th>
                                    <th>Thuế</th>
                                    <th>Giảm giá</th>
                                    <th>TB/Đơn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sales_data as $row): ?>
                                <tr>
                                    <td><?php echo formatDate($row['sale_date']); ?></td>
                                    <td><?php echo $row['total_orders']; ?></td>
                                    <td><strong><?php echo formatMoney($row['total_revenue']); ?></strong></td>
                                    <td><?php echo formatMoney($row['total_tax']); ?></td>
                                    <td><?php echo formatMoney($row['total_discount']); ?></td>
                                    <td><?php echo formatMoney($row['avg_order_value']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-info">
                                <tr>
                                    <th>Tổng cộng</th>
                                    <th><?php echo $totals['orders']; ?></th>
                                    <th><strong><?php echo formatMoney($totals['revenue']); ?></strong></th>
                                    <th><?php echo formatMoney($totals['tax']); ?></th>
                                    <th><?php echo formatMoney($totals['discount']); ?></th>
                                    <th>-</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4 mb-4">
            <!-- Payment Methods -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Phương thức thanh toán</h6>
                </div>
                <div class="card-body">
                    <canvas id="paymentChart" height="200"></canvas>
                    <div class="mt-3">
                        <?php 
                        $payment_labels = [
                            'cash' => 'Tiền mặt',
                            'card' => 'Thẻ',
                            'momo' => 'MoMo',
                            'bank_transfer' => 'Chuyển khoản'
                        ];
                        foreach ($payment_methods as $pm): 
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?php echo $payment_labels[$pm['payment_method']] ?? $pm['payment_method']; ?></span>
                            <div>
                                <span class="badge bg-primary"><?php echo $pm['count']; ?> đơn</span>
                                <strong class="ms-2"><?php echo formatMoney($pm['total']); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Best Sellers -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Top 10 món bán chạy</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($best_sellers as $index => $item): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-secondary me-2"><?php echo $index + 1; ?></span>
                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo $item['total_quantity']; ?> phần | 
                                        <?php echo formatMoney($item['total_revenue']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type:  'line',
    data:  {
        labels: <?php echo json_encode(array_column(array_reverse($sales_data), 'sale_date')); ?>,
        datasets: [{
            label: 'Doanh thu',
            data: <?php echo json_encode(array_column(array_reverse($sales_data), 'total_revenue')); ?>,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.3,
            fill: true
        }]
    },
    options:  {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value. toLocaleString('vi-VN') + 'đ';
                    }
                }
            }
        }
    }
});

// Payment Methods Chart
const paymentCtx = document.getElementById('paymentChart').getContext('2d');
const paymentChart = new Chart(paymentCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(function($pm) {
            $labels = ['cash' => 'Tiền mặt', 'card' => 'Thẻ', 'momo' => 'MoMo', 'bank_transfer' => 'Chuyển khoản'];
            return $labels[$pm['payment_method']] ?? $pm['payment_method'];
        }, $payment_methods)); ?>,
        datasets:  [{
            data: <?php echo json_encode(array_column($payment_methods, 'total')); ?>,
            backgroundColor:  [
                'rgb(255, 99, 132)',
                'rgb(54, 162, 235)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

function setToday() {
    const today = new Date().toISOString().split('T')[0];
    $('input[name="from_date"]').val(today);
    $('input[name="to_date"]').val(today);
    $('form').submit();
}

function exportToExcel() {
    window.location.href = 'export_excel.php?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>';
}

function exportToPDF() {
    window.location.href = 'export_pdf.php?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>';
}
</script>

<?php include '../../includes/footer.php'; ?>