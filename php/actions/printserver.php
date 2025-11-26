<?php

require_once '../system_controller.php';

$action = $_POST['action'] ?? null;

try {
    if ($action === 'docker-restart') {
        $dockerInfo = SystemController::getDockerInfo();
        $containerName = $dockerInfo['container'];
        $restartResult = SystemController::restartDocker($containerName);

        if (isset($_SESSION['message'])) {
            $_SESSION['message'] = $restartResult['message'] . " - " . $_SESSION['message'];
        } else {
            $_SESSION['message'] = $restartResult['message'];
        }

    } else if ($action === 'log-error') {
        $file_log = SystemController::getDockerInfo();
        $_SESSION['message'] = _t("Log errori recuperato correttamente.");
        
    } else {
        $_SESSION['message'] = _t("❓ Azione non riconosciuta.");
    }

} catch (Exception $e) {
    $_SESSION['error'] = _t("❌ Errore: ") . $e->getMessage();
}

// Redirect alla pagina principale
header("Location: /manager.php?page=home");
exit();

/*
try {
    switch ($action) {
        case 'restart':
            SystemController::restartService();
            $_SESSION['message'] = "✅ PrintServer riavviato con successo.";
            break;
        case 'stop':
            SystemController::stopService();
            $_SESSION['message'] = "🛑 PrintServer arrestato.";
            break;
        case 'start':
            SystemController::startService();
            $_SESSION['message'] = "▶️ PrintServer avviato.";
            break;
        case 'docker-restart':
            SystemController::restartDocker();
            $_SESSION['message'] = "🐳 Docker container riavviato.";
            break;
        case 'log-error':
            $logContent = SystemController::getErrorLog();
            $_SESSION['message'] = "<pre>" . htmlspecialchars($logContent) . "</pre>";
            break;
        default:
            $_SESSION['message'] = "❓ Azione non riconosciuta.";
    }
} catch (Exception $e) {
    $_SESSION['message'] = "❌ Errore: " . $e->getMessage();
}
*/