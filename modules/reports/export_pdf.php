<?php
// Export daily sales to PDF using Dompdf
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
            SUM(tax_amount) AS total_tax,
            SUM(discount_amount) AS total_discount,
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

// Ensure Dompdf is available
$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Thiếu thư viện Dompdf. Cài đặt bằng composer require dompdf/dompdf';
    exit;
}
require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$rows_html = '';
foreach ($rows as $r) {
    $rows_html .= '<tr>'
        . '<td>' . htmlspecialchars($r['sale_date']) . '</td>'
        . '<td>' . (int)$r['total_orders'] . '</td>'
        . '<td>' . number_format((float)$r['total_revenue'], 0, ',', '.') . '</td>'
        . '<td>' . number_format((float)$r['total_tax'], 0, ',', '.') . '</td>'
        . '<td>' . number_format((float)$r['total_discount'], 0, ',', '.') . '</td>'
        . '<td>' . number_format((float)$r['avg_order_value'], 0, ',', '.') . '</td>'
        . '</tr>';
}

$html = "<!DOCTYPE html>
<html lang='vi'>
<head>
<meta charset='UTF-8'>
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
h1 { font-size: 18px; margin-bottom: 10px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
th { background: #f2f2f2; }
</style>
</head>
<body>
<h1>Báo cáo doanh thu</h1>
<div>Khoảng thời gian: " . htmlspecialchars($from_date) . " đến " . htmlspecialchars($to_date) . "</div>
<table>
<thead>
<tr>
<th>Ngày</th>
<th>Số đơn</th>
<th>Doanh thu</th>
<th>Thuế</th>
<th>Giảm giá</th>
<th>Trung bình/đơn</th>
</tr>
</thead>
<tbody>
$rows_html
</tbody>
</table>
</body>
</html>";

$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="daily_sales_' . $from_date . '_to_' . $to_date . '.pdf"');
echo $dompdf->output();
exit;
