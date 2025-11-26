<?php
require_once '../cups.php';
header('Content-Type: application/json');
$printerName = $_GET['printer_name'] ?? '';
error_log('Nome stampante ricevuto: ' . $printerName);
if (empty($printerName)) {
    echo json_encode(['error' => _t('Nome stampante mancante')]);
    exit;
}
$printerObj = new Printer();
$printer = $printerObj->managePrintQueue('cancelAllJobs', $printerName, null, null);
echo json_encode($printer);
exit;