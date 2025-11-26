<?php
include_once("views/traduzioni.php");
class SystemController {
    // Ottiene le informazioni sul container Docker dal file README.txt
    public static function getDockerInfo($readmePath = null) {
        include("conf.php");
        if ($readmePath === null) {
            $readmePath = $readme;
        }
        if (!file_exists($readmePath)) {
            throw new Exception("README.txt " . _t('non trovato ') . dirname(getcwd()) . $readme);
        }
        $info = [
            'container' => null,
            'http' => null,
            'https' => null,
			'cups' => null,
        ];
        $lines = file($readmePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) == 2) {
                $key = strtolower(trim($parts[0]));
                $value = trim($parts[1]);
                if (strpos($key, 'container name') !== false) {
                    $info['container'] = $value;
                } elseif (strpos($key, 'http port') !== false) {
                    $info['http'] = $value;
                } elseif (strpos($key, 'https port') !== false) {
                    $info['https'] = $value;
				} elseif (strpos($key, 'cups port') !== false) {
                    $info['cups'] = $value;
                }
            }
        }
        if (!$info['container']) {
            throw new Exception(_t('Nome container non trovato'));
        }
        return $info;
    }

    // Riavvia un container Docker
    public static function restartDocker(string $containerName) {
        include "conf.php"; 
        $COMMAND = "/bin/docker restart {$containerName}";
        $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);
        sleep(5);
        if ($result === false) {
            return ['success' => true, 'message' => _t('Errore durante il riavvio docker')];
        } else {
            return ['success' => true, 'message' => _t('Docker riavviata con successo!'). " 🐳"];
        }

        //$command = escapeshellcmd("docker restart {$containerName}");
        //exec($command, $output, $status);
        //error_log("status".$status);
        //if ($status !== 0) {
        //    throw new Exception("Errore nel riavvio del container Docker.");
        //}
        //return true;
    }
	
	// Log errori
    public static function logErrori() {
        include "conf.php"; 
        if (!file_exists($logPath)) {
			return "⚠️" . _t('Log file non trovato');
		}
		$logContent = file_get_contents($logPath);
		return htmlspecialchars($logContent); // Sicurezza
	}
}
?>