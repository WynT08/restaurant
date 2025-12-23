<?php
// Export daily sales to CSV (Excel-friendly)
require_once '../../config/config.php';
require_once '../../config/database.php';
requirePermission('manager');

$database = new Database();
$db = $database->getConnection();

function sanitizeDate($value) {
    $d = DateTime::createFromFormat('Y-m-d', $value ?? '');
    return $d ? $d->format('Y-m-d') : date('Y-m-d');
}

$from_date = sanitizeDate($_GET['from_date'] ?? null);
$to_date   = sanitizeDate($_GET['to_date'] ?? null);

// Fetch aggregated sales
$stmt = $db->prepare(
        "SELECT DATE(created_at) AS sale_date,
                        COUNT(*) AS total_orders,
                        SUM(total_amount) AS total_revenue,
                        SUM(subtotal) AS subtotal,
                        SUM(tax) AS total_tax,
                        SUM(discount) AS total_discount,
                        AVG(total_amount) AS avg_order_value
         FROM orders
         WHERE DATE(created_at) BETWEEN :from_date AND :to_date
             AND payment_status IN ('paid','completed')
         GROUP BY DATE(created_at)
         ORDER BY sale_date DESC"
);
$stmt->bindParam(':from_date', $from_date);
$stmt->bindParam(':to_date', $to_date);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="daily_sales_' . $from_date . '_to_' . $to_date . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Ngày', 'Số đơn', 'Doanh thu', 'Thuế', 'Giảm giá', 'Trung bình/đơn']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['sale_date'],
        $r['total_orders'],
        $r['total_revenue'],
        $r['total_tax'],
        $r['total_discount'],
        $r['avg_order_value']
    ]);
}
fclose($out);
exit;
