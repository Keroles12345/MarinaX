<?php
require dirname(__DIR__, 2) . '/vendor/autoload.php';

$html = file_get_contents(__DIR__ . '/system-summary.html');
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
file_put_contents(dirname(__DIR__, 2) . '/output/pdf/system-summary-ar.pdf', $dompdf->output());
