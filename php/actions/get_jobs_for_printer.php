<?php
require_once '../cups.php';
header('Content-Type: application/json');
$printerName = $_POST['printer_name'] ?? '';
if (!$printerName || empty($printerName)) {
    echo json_encode(['error' => _t('Nome stampante mancante')]);
    exit;
}
$printerObj = new Printer();
$result = $printerObj->getQueueByPrinter($printerName);
// Restituisci il risultato come JSON puro, senza echo di altro
echo json_encode($result);
exit;
