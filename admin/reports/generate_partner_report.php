<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}
if (!$auth->isAdmin()) {
    http_response_code(403);
    die('Acceso denegado');
}
$db = getDB();

function pdfEscape($text) {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string)$text);
}

function addPdfLine(&$stream, &$y, $text, $size = 10, $bold = false) {
    if ($y < 50) {
        return;
    }
    $font = $bold ? 'F2' : 'F1';
    $stream .= "BT /{$font} {$size} Tf 50 {$y} Td (" . pdfEscape($text) . ") Tj ET\n";
    $y -= ($size + 7);
}

$totals = $db->query("SELECT
    (SELECT COUNT(*) FROM site_visits) AS total_visits,
    (SELECT COUNT(*) FROM site_visits WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) AS month_visits,
    (SELECT COUNT(*) FROM djs WHERE active = 1) AS total_djs,
    (SELECT COUNT(*) FROM djs WHERE plan = 'pro' AND subscription_status = 'active' AND subscription_end >= NOW()) AS pro_active,
    (SELECT COUNT(*) FROM djs WHERE subscription_status = 'expired' OR (plan = 'pro' AND subscription_end < NOW())) AS expired_djs,
    (SELECT COALESCE(SUM(amount), 0) FROM dj_payments) AS yappy_income,
    (SELECT COALESCE(SUM(plays), 0) FROM statistics) AS total_plays")->fetch(PDO::FETCH_ASSOC);

$topDjs = $db->query("SELECT d.name, COUNT(sv.id) AS visits
    FROM djs d
    LEFT JOIN site_visits sv ON sv.dj_id = d.id
    WHERE d.active = 1
    GROUP BY d.id
    ORDER BY visits DESC
    LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$topMixes = $db->query("SELECT m.title, m.dj, COALESCE(SUM(s.plays), 0) AS plays
    FROM mixes m
    LEFT JOIN statistics s ON s.item_id = m.id AND s.item_type = 'mix'
    WHERE m.active = 1
    GROUP BY m.id
    ORDER BY plays DESC
    LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$topPages = $db->query("SELECT page_type, COUNT(*) AS visits
    FROM site_visits
    GROUP BY page_type
    ORDER BY visits DESC
    LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

$stream = "";
$y = 790;
addPdfLine($stream, $y, 'Reporte General - Panda Truck Reloaded', 18, true);
addPdfLine($stream, $y, 'Fecha del reporte: ' . date('d/m/Y H:i'), 10);
addPdfLine($stream, $y, 'Resumen ejecutivo', 13, true);
addPdfLine($stream, $y, 'Total de visitas: ' . number_format((int)$totals['total_visits']));
addPdfLine($stream, $y, 'Visitas del mes: ' . number_format((int)$totals['month_visits']));
addPdfLine($stream, $y, 'DJs registrados: ' . number_format((int)$totals['total_djs']));
addPdfLine($stream, $y, 'DJs PRO activos: ' . number_format((int)$totals['pro_active']));
addPdfLine($stream, $y, 'DJs vencidos: ' . number_format((int)$totals['expired_djs']));
addPdfLine($stream, $y, 'Ingresos registrados por Yappy: $' . number_format((float)$totals['yappy_income'], 2));
addPdfLine($stream, $y, 'Total de reproducciones: ' . number_format((int)$totals['total_plays']));
$y -= 8;

addPdfLine($stream, $y, 'Top 10 DJs mas visitados', 13, true);
foreach ($topDjs as $i => $row) {
    addPdfLine($stream, $y, ($i + 1) . '. ' . $row['name'] . ' - ' . number_format((int)$row['visits']) . ' visitas');
}
$y -= 8;

addPdfLine($stream, $y, 'Top 10 mixes mas escuchados', 13, true);
foreach ($topMixes as $i => $row) {
    addPdfLine($stream, $y, ($i + 1) . '. ' . $row['title'] . ' / ' . $row['dj'] . ' - ' . number_format((int)$row['plays']) . ' plays');
}
$y -= 8;

addPdfLine($stream, $y, 'Top paginas visitadas', 13, true);
foreach ($topPages as $i => $row) {
    addPdfLine($stream, $y, ($i + 1) . '. ' . $row['page_type'] . ' - ' . number_format((int)$row['visits']) . ' visitas');
}
$y -= 8;

addPdfLine($stream, $y, 'Observaciones', 13, true);
addPdfLine($stream, $y, 'El reporte incluye datos internos de visitas, DJs PRO, pagos manuales Yappy y reproducciones.');
addPdfLine($stream, $y, 'Las metricas de CDN pueden agregarse manualmente cuando BunnyCDN este configurado.');

$objects = [];
$objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
$objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
$objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>";
$objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
$objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
$objects[] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}endstream";

$pdf = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $i => $obj) {
    $offsets[] = strlen($pdf);
    $pdf .= ($i + 1) . " 0 obj\n{$obj}\nendobj\n";
}
$xref = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) {
    $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
}
$pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="reporte_socios_panda_truck_' . date('Y-m-d') . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
exit;
?>
