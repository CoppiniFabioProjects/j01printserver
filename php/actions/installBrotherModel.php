<?php
include "../conf.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$logFile = __DIR__ . '/debug_log.txt';
file_put_contents($logFile, "=== REQUEST START ===\n", FILE_APPEND);
header('Content-Type: application/json');
require_once '../cups.php';  // include la classe Printer
// Controllo metodo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $msg = _t('Metodo non consentito');
    echo json_encode(['success' => false, 'message' => $msg]);
    file_put_contents($logFile, "$msg\n", FILE_APPEND);
    exit;
}
$model = trim($_POST['model'] ?? '');
file_put_contents($logFile, "Model ricevuto: '$model'\n", FILE_APPEND);
if ($model === '') {
    $msg = _t('Modello mancante');
    echo json_encode(['success' => false, 'message' => $msg]);
    file_put_contents($logFile, "Errore: $msg\n", FILE_APPEND);
    exit;
}
// Validazione base modello
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $model)) {
    $msg = _t('Modello contiene caratteri non validi');
    echo json_encode(['success' => false, 'message' => $msg]);
    file_put_contents($logFile, "Errore: $msg\n", FILE_APPEND);
    exit;
}
$model = str_replace(' ', '-', $model);
// Cancellazione file se esistono
if (file_exists($printer_file)) {
    if (!unlink($printer_file)) {
        $msg = _t('Fallita la cancellazione del file printer.txt');
        echo json_encode(['success'=> false, 'message'=> $msg]);
        file_put_contents($logFile, "Errore: $msg\n", FILE_APPEND);
        exit;
    }
}
if (file_exists($printer_log)) {
    if (!unlink($printer_log)) {
        $msg = _t('Fallita la cancellazione del file printer_log.txt');
        echo json_encode(['success'=> false, 'message'=> $msg]);
        file_put_contents($logFile, "Errore: $msg\n", FILE_APPEND);
        exit;
    }
}
// Scrittura comando nel file
$COMMAND = "$INSTALLER_SCRIPT -p $model";
$result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);
if ($result === false) {
    $msg = _t('Errore durante la scrittura nel file.');
    echo json_encode(['success' => false, 'message' => $msg]);
    file_put_contents($logFile, "Errore: $msg\n", FILE_APPEND);
    exit;
} else {
    $msg = _t("Comando installazione inviato per il modello") . " '$model'.";
    echo json_encode(['success' => true, 'message' => $msg]);
    file_put_contents($logFile, "Comando scritto correttamente: $COMMAND\n", FILE_APPEND);
}
?>
