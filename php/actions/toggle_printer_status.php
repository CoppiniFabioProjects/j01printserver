<?php
require_once '../cups.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$printerName = $_GET['printer_name'] ?? '';

// Validazione parametri
if (!in_array($action, ['enablePrinter', 'disablePrinter']) || empty($printerName)) {
    echo json_encode([
        'success' => false,
        'message' => _t('Azione o stampante non valida.')
    ]);
    exit;
}

// Esecuzione azione sulla coda di stampa
$printerObj = new Printer();
$response = $printerObj->managePrintQueue($action, $printerName);

echo json_encode($response);
exit;
