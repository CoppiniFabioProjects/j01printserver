<?php
include_once("traduzioni.php");

if (!isset($_SESSION['user']) || !isset($_SESSION['csrf_token'])) {
    header("Location: login.php");
    exit();
}
// log_preview.php
// Percorso assoluto al file di log 
$logFile = '/codice01/j01printserver/info01/wrapper.log';
if (!file_exists($logFile)) {
    http_response_code(404);
    echo _t("File di log non trovato.");
    exit;
}
// Per sicurezza, limita la dimensione letta (es. max 500KB)
$maxSize = 500 * 1024;
$size = filesize($logFile);
$start = 0;
if ($size > $maxSize) {
    $start = $size - $maxSize; // Leggi solo la parte finale
}
$content = '';
$fp = fopen($logFile, 'r');
if ($fp === false) {
    http_response_code(500);
    echo _t("Errore nell'apertura del file di log.");
    exit;
}
// Se è un file grande, spostati al punto di partenza
if ($start > 0) {
    fseek($fp, $start);
}
// Leggi contenuto
while (!feof($fp)) {
    $content .= fread($fp, 8192);
}
fclose($fp);
// Escape output per sicurezza HTML
$escapedContent = htmlspecialchars($content);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title><?php echo _t("Anteprima Log Errori"); ?></title>
    <style>
        body { font-family: monospace, monospace; white-space: pre-wrap; background: #222; color: #eee; padding: 20px; }
    </style>
</head>
<body>
<h1><?php echo _t("Anteprima Log Errori"); ?></h1>
<pre><?= $escapedContent ?></pre>
</body>
</html>