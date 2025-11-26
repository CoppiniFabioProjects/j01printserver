<?php
require_once '../cups.php';
header('Content-Type: application/json');
$printerName = $_GET['printer_name'] ?? '';
if ($printerName) {
    $printerObj = new Printer();
    $printer = $printerObj->getPrinterByName($printerName);
    echo json_encode($printer);
} else {
    echo json_encode(['error' => _t('Nome stampante mancante')]);
}
exit;