
<?php

// Classe per la gestione della configurazione del server di stampa
class ConfigManager {
    private $configFile;
    private $config;
    private $backupDir;

    public function __construct($configFile, $backupDir) {
        $this->configFile = $configFile;
        $this->backupDir = $backupDir;

        // Controlliamo se il file di configurazione esiste e carichiamolo
        if (!file_exists($configFile)) {
            throw new Exception("<?php echo _t('File di configurazione non trovato'); ?>: {$configFile}");
        }

        $this->loadConfig();
    }

    // Carica la configurazione dal file XML
    public function loadConfig() {
        $this->config = simplexml_load_file($this->configFile);
        if ($this->config === false) {
            throw new Exception("<?php echo _t('Impossibile caricare il file XML di configurazione'); ?>.");
        }
    }

    // Salva la configurazione nel file XML
    public function saveConfig() {
        $saved = $this->config->asXML($this->configFile);
        if (!$saved) {
            throw new Exception("<?php echo _t('Impossibile salvare il file di configurazione'); ?>.");
        }
        return $saved;
    }

    // Ottieni le informazioni di connessione al database dal file j01printserver-config.xml
    public function getDbConnectionInfo($config) {
        // Inizializziamo l'array con chiavi attese per la connessione
        $info = [
            'user' => null,
            'password' => null,
            'serverName' => null,
            'libraries' => null,
            'azienda'    => null,
        ];

        // Cicliamo su tutti i set-property per assegnare i valori trovati
        foreach ($config->datasource->{'set-property'} as $setProperty) {
            $property = (string)$setProperty['property'];
            $value = (string)$setProperty['value'];
            if (array_key_exists($property, $info)) {
                $info[$property] = $value;
            }
        }
        return $info;
    }

    // Crea una connessione ODBC usando le info lette dal file
    public function getOdbcConnection() {
        $dbInfo = $this->getDbConnectionInfo($this->config);

        // Puoi usare *LOCAL se sei su IBM i, altrimenti dbInfo['serverName']
        $dsn = '*LOCAL';
        $user = $dbInfo['user'];
        $password = $dbInfo['password'];

        // Verifica che tutti i dati necessari siano presenti
        if (empty($dsn) || empty($user) || empty($password)) {
            throw new Exception("<?php echo _t('Informazioni mancanti per la connessione ODBC'); ?>.");
        }

        // Tentativo di connessione ODBC
        $conn = odbc_connect($dsn, $user, $password);

        if (!$conn) {
            throw new Exception("<?php echo _t('Connessione ODBC fallita'); ?>: " . odbc_errormsg());
        }

        return $conn;
    }
	
	public function checkOdbcConnectionForUsers($user, $password) {
		$dbInfo = $this->getDbConnectionInfo($this->config);

		// Puoi usare *LOCAL se sei su IBM i, altrimenti dbInfo['serverName']
		$dsn = '*LOCAL';
		// Verifica che tutti i dati necessari siano presenti
		if (empty($dsn) || empty($user) || empty($password)) {
			error_log("Dati connessione mancanti: DSN=$dsn, User=$user");
			return false;
		}

		// Tentativo di connessione ODBC
		$conn = @odbc_connect($dsn, $user, $password); // Sopprime warning

		// Logga dump connessione e eventuale messaggio errore
		if (!$conn) {
			// Connessione fallita
			return false;
		} else {
			// Esegue query di test valida su DB2 per IBM i
			$result = odbc_exec($conn, "SELECT 1 AS TEST FROM SYSIBM.SYSDUMMY1");
			if ($result) {
				while (odbc_fetch_row($result)) {
					return true;
				}
			} else {
				return false;
			}
		}
	}

	public function checkOdbcConnectionForUsersMBUT02($userMBUT02, $passwordMBUT02) {
		$dbInfo = $this->getDbConnectionInfo($this->config);

		// Puoi usare *LOCAL se sei su IBM i, altrimenti dbInfo['serverName']
		$dsn = '*LOCAL';
        $user = $dbInfo['user'];
        $password = $dbInfo['password'];
		$libraries = $dbInfo['libraries'];
		// Verifica che tutti i dati necessari siano presenti
		if (empty($dsn) || empty($user) || empty($password) || empty($userMBUT02) || empty($passwordMBUT02)) {
			error_log("Dati connessione mancanti: DSN=$dsn, User=$user");
			return false;
		}

		// Tentativo di connessione ODBC
		$conn = @odbc_connect($dsn, $user, $password); // Sopprime warning

		// Logga dump connessione e eventuale messaggio errore
		if (!$conn) {
			// Connessione fallita
			return false;
		} else {
			// Esegue query di test valida su DB2 per IBM i
			$libraries_n = explode(" ", $libraries);
            // Costruisci la query con filtro su azienda (evita SQL injection via parametri fissi)
            foreach ($libraries_n as $lib) {
				$sql = "SELECT 1 FROM $lib.mbut02 WHERE mbut02cod='".addslashes($userMBUT02). "' and DECRYPT_CHAR(mbut02ps2, 'qwertyuiop')='" . addslashes($passwordMBUT02) . "'";
				$result = odbc_exec($conn, $sql);
				if ($result) {
					while (odbc_fetch_row($result)) {
						return true;
					}
				} 
			}
			return false;
		}
	}

    // Carica i dispositivi dal database
    public function caricaDispositiviDaDatabase(&$dbData, &$dbErrors) {
        $dbData = [];
        $dbErrors = null;

        try {
            $conn = $this->getOdbcConnection(); // Ottieni connessione ODBC
            $dbInfo = $this->getDbConnectionInfo($this->config);
            $azienda = trim($dbInfo['azienda']); // Ricava il valore da <set-property property="azienda">
            $libraries = trim($dbInfo['libraries']); // Ricava il valore da <set-property property="libraries">

			$libraries_n = explode(" ", $libraries);
			
            // Costruisci la query con filtro su azienda (evita SQL injection via parametri fissi)
            foreach ($libraries_n as $lib) {
				$sql = "SELECT * FROM $lib.mbws02 WHERE mbws02tip IN (2, 3, 4)";
				if ($azienda !== '') {
					// Sanificazione basilare (non serve escaping perché non concateni nulla pericoloso)
					$aziendaSafe = str_replace("'", "''", $azienda);
					$sql .= " AND mbws02azi = '$aziendaSafe'";
				}

				$sql .= " ORDER BY mbws02tip, mbws02cod";
				$rs = odbc_exec($conn, $sql);

				if (!$rs) {
					continue;
				}

				// Preleva tutte le righe risultanti dalla query
				while ($row = odbc_fetch_array($rs)) {
					$dbData[] = array_change_key_case($row, CASE_UPPER);
				}
			}
            odbc_close($conn); // Chiudi la connessione ODBC
        } catch (Exception $e) {
            $dbErrors = $e->getMessage();
        }
    }

    // Esegui un backup con un timestamp (aggiunge una parte unica al nome del backup)
    public function createBackup() {
        // Crea la directory backup se non esiste
        if (!is_dir($this->backupDir)) {
            if (!mkdir($this->backupDir, 0777, true)) {
                throw new Exception("<?php echo _t('Impossibile creare la directory di backup'); ?>.");
            }
        }

		$timezone_file = '/etc/timezone';
		if (file_exists($timezone_file)) {
			$timezone = trim(file_get_contents($timezone_file));
			if (in_array($timezone, timezone_identifiers_list())) {
				date_default_timezone_set($timezone);
			} else {
				date_default_timezone_set('Europe/Rome');
			}
		} else {
			date_default_timezone_set('Europe/Rome');
		}
        // Prendi timestamp intero per date()
        $timeInt = (int) microtime(true);

        // Prendi microsecondi come stringa di 6 cifre
        $micro = sprintf("%06d", (int)(microtime(true) * 1000000) % 1000000);

        // Costruisci il nome backup usando il timestamp intero + microsecondi
        $backupName = $this->backupDir . '/j01printserver-config_' . date('Ymd_His', $timeInt) . '_' . $micro . '.bak';
		
		/*
		if (is_dir($this->backupDir)) {
			if (is_writable($this->backupDir)) {
				echo "La directory di backup è scrivibile.\n";
			} else {
				echo "La directory di backup NON è scrivibile.\n";
			}
		} else {
			echo "La directory di backup NON esiste.\n";
		}

		if (file_exists($this->configFile)) {
			if (is_readable($this->configFile)) {
				echo "Il file di configurazione è leggibile.\n";
			} else {
				echo "Il file di configurazione NON è leggibile.\n";
			}
		} else {
			echo "Il file di configurazione NON esiste.\n";
		}
		*/

        // Copia il file di configurazione nel backup
        if (!copy($this->configFile, $backupName)) {
			throw new Exception($backupName);
            //throw new Exception("Impossibile eseguire il backup del file di configurazione.");
        }

        return $backupName;  // Restituisce il percorso del backup creato
    }

    // Ripristina la configurazione da un backup
    public function restoreBackup($backupFile) {
        if (!file_exists($backupFile)) {
            throw new Exception("<?php echo _t('Il backup selezionato non esiste'); ?>: {$backupFile}");
        }

        // Backup corrente di sicurezza prima di ripristinare
        $preRestoreBackup = $this->backupDir . '/j01printserver-config_' . date('Ymd_His') . '.bak';
        if (!copy($this->configFile, $preRestoreBackup)) {
            throw new Exception("<?php echo _t('Impossibile creare un backup prima del ripristino'); ?>.");
        }

        // Ripristino della configurazione
        if (!copy($backupFile, $this->configFile)) {
            throw new Exception("<?php echo _t('Impossibile ripristinare il file di configurazione dal backup'); ?>.");
        }

        // Ricarica la configurazione aggiornata
        $this->loadConfig();

        return true;
    }

    // Ottieni la lista dei backup disponibili
    public function getBackups() {

        $backups = [];
        if (is_dir($this->backupDir)) {
            $files = scandir($this->backupDir);
            foreach ($files as $file) {
                // Nota: ho corretto il regex per corrispondere solo al nome file, non al path completo
                if (preg_match('/^j01printserver-config_\d{8}_\d{6}_\d{6}\.bak$/', $file)) {
                    $filepath = $this->backupDir . '/' . $file;
                    $backups[] = [
                        'name' => $file,
                        'mtime' => filemtime($filepath),
                        'path' => $filepath
                    ];
                }
            }

            // Ordina i backup in ordine cronologico (dal più recente al più vecchio)
            usort($backups, function($a, $b) {
                return $b['mtime'] - $a['mtime'];
            });
        }

        return $backups;
    }

    // Restituisci la configurazione caricata
    public function getConfig() {
        return $this->config;
    }
}