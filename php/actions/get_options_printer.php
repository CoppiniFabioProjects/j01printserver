<?php
require_once '../cups.php';
header('Content-Type: application/json');
$printerName = $_GET['printer_name'] ?? '';
if (empty($printerName)) {
    echo json_encode(['error' => _t('Nome stampante mancante')]);
    exit;
}
$printerObj = new Printer();
$printer = $printerObj->getPrinterOptions($printerName);
if ($printer && $printer['success']) {
    echo json_encode($printer['options']);
} else {
    echo json_encode(['error' => $printer['message'] ?? _t('Stampante non trovata')]);
}
exit(); 