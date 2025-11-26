<?php
session_start();

include("../conf.php");
include("../views/traduzioni.php");
// Genera un token CSRF se non esiste
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verifica token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
	$str = _t('Token CSRF non valido.');
    throw new Exception($str);
}

// --- IMPOSTAZIONI ---
if (file_exists($configFile)) {
    $config = simplexml_load_file($configFile);
} else {
    throw new Exception(_t("❌ File di config non trovato."));
}

// Impostazioni FTP
$ftpServer = 'codice01.01Informatica.it';
$ftpUser = (string)$config->system->username;
$ftpPass = (string)$config->system->password;
$remoteJarPath = '/codice01/jenkins/j01printserver/j01printserver.jar';

$jarDir = '/codice01/j01printserver/info01/';
$currentJar = $jarDir . 'j01printserver.jar';
$backupJar = $jarDir . 'j01printserver.jar.old';
$tempJar = 'j01printserver.jar';
$tempDir = '/php/actions/';

try {
    // 1. Connessione FTP
    $connId = ftp_connect($ftpServer);
    if ($connId === false) {
        throw new Exception(_t("Impossibile connettersi al server FTP: ") . $ftpServer);
    }

    // 2. Login FTP
    if (!ftp_login($connId, $ftpUser, $ftpPass)) {
        ftp_close($connId);
        throw new Exception(_t("Login FTP fallito per l'utente") . " '$ftpUser'.");
    }

    ftp_pasv($connId, true);

    // 3. Download del file
    if (!ftp_get($connId, $tempJar, $remoteJarPath, FTP_BINARY)) {
        ftp_close($connId);
        throw new Exception(_t("Errore durante il download del file via FTP da") . " '$remoteJarPath' → '$tempJar'.");
    }

    ftp_close($connId);

    // 5. Backup del file attuale
    if (file_exists($currentJar)) {
        if (file_exists($backupJar)) {
            $COMMAND = "rm $backupJar";
            $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);
            sleep(5);
            if ($result === false) {
                throw new Exception(_t("Errore nella rimozione di") . " $backupJar.");
            }
        } else {
            $COMMAND = "mv $currentJar $backupJar";
            $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);
            sleep(5);
            if ($result === false) {
                throw new Exception(_t("Impossibile creare il backup del file attuale") . ": $currentJar.");
            }
        }
    }

    // 6. Spostamento del nuovo file
    $COMMAND = "mv $tempDir$tempJar $currentJar";
    $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);
    sleep(5);
    if ($result === false) {
        throw new Exception(_t("Errore durante la sostituzione del vecchio file JAR con quello nuovo") . ": $tempDir$tempJar → $currentJar.");
    }

    // 7. Successo
    $_SESSION['message'] = _t("PrintServer aggiornato con successo via FTP. ✅");

    header('Location: restart_after_update.php');
    exit;

} catch (Exception $e) {
    $_SESSION['message'] = _t("❌ Errore durante l'aggiornamento") . ": " . $e->getMessage();

    if (file_exists($tempJar)) {
        unlink($tempJar);
    }

    header('Location: ../manager.php');
    exit;
}
