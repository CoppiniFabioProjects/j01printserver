<?php

session_start();

//utenti non 01
$isUtonto = $_SESSION['utonto'] ?? false;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

if (!isset($_SESSION['user']) || !isset($_SESSION['csrf_token'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

include("conf.php");
$odbcIniFile = "/etc/odbc.ini";

$message = null;
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

require_once 'config_manager.php';
require_once("views/traduzioni.php");

try {
    $configManager = new ConfigManager(configFile: $configFile, backupDir: $backupDir);
} catch (Exception $e) {
	die(_t("Errore during l'inizializzazione della configurazione:") . $e->getMessage());
}

// Routing pagina: se c'è POST (azione) prendo da POST, altrimenti da GET, default a 'home'
$page = $_POST['page'] ?? $_GET['page'] ?? 'home';

// Pagine permesse agli utenti "utonto"
$allowedPagesForUtonto = ['home', 'cups', 'device', 'log',];

// Blocca accesso a pagine riservate agli admin
if ($isUtonto && !in_array($page, $allowedPagesForUtonto)) {
    $_SESSION['message'] = _t("Accesso negato: funzionalità riservata all'amministratore.");
    header("Location: manager.php?page=home");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	
    try {
        // Decidiamo se creare backup solo per alcune azioni
        $doBackup = false;

        switch ($page) {
            case 'system':
            case 'configurazione':
            case 'device':
                $doBackup = true;
                break;
            case 'datasource':
                $doBackup = true;
                break;

            case 'notifica':
                if (isset($_POST['applicationserver_notifica']) && is_array($_POST['applicationserver_notifica'])) {
                    $doBackup = true;
                }
                break;

            case 'restore':
                // restore non richiede backup prima del ripristino
                break;

            default:
                // azione pagina non riconosciuta, niente backup
                break;
        }

        if ($doBackup) {
            $configManager->createBackup();
        }

        // Carichiamo config attuale per modifiche
        $config = $configManager->getConfig();

        switch ($page) {
            case 'system':
                $config->system->host = $_POST['host'] ?? $config->system->host;
                $config->system->username = $_POST['username'] ?? $config->system->username;

                if (isset($_POST['password']) && $_POST['password'] !== '') {
                    $config->system->password = $_POST['password'];
                }

                $configManager->saveConfig();
                $_SESSION['message'] = _t('System aggiornato con successo'); 
                header("Location: manager.php?page=system");
                exit();

            case 'configurazione':
                $attributes = [
                    'listenPort',
                    'listenBackLog',
                    'forcedPollingMillis',
                    'rinnovaWebConfigMillis',
                    'serverVersion',
                    'disabilitaAcrobat'
                ];
                $configContent = file_get_contents($configFile);
                foreach ($attributes as $attr) {
                    if (!isset($_POST[$attr])) continue;
                    $newValue = $_POST[$attr];

                    if ($attr === 'disabilitaAcrobat') {
                        $pattern = '/<!ATTLIST\s+j01printserver-config\s+' . preg_quote($attr, '/') . '\s+\(true\s*\|\s*false\)\s*"[^"]*"\s*>/';
                        $replacement = '<!ATTLIST j01printserver-config ' . $attr . ' (true | false) "' . $newValue . '">';
                    } else {
                        $pattern = '/<!ATTLIST\s+j01printserver-config\s+' . preg_quote($attr, '/') . '\s+CDATA\s+"[^"]*"\s*>/';
                        $replacement = '<!ATTLIST j01printserver-config ' . $attr . ' CDATA "' . $newValue . '">';
                    }

                    $configContent = preg_replace($pattern, $replacement, $configContent);
                }
                file_put_contents($configFile, $configContent);

                $message = _t('Configurazione aggiornata con successo');
                break;

            case 'device':

                $mailParams = [
                    "mail.transport.protocol", "mail.smtps.host", "mail.smtps.auth",
                    "mail.debug", "mail.smtps.user", "mail.smtps.password",
                    "mail.smtps.port", "mail.smtp.starttls.enable", "mail.from"
                ];

                $selectedDevices = $_POST['selectedDevices'] ?? []; // Array dei device spuntati
                $errors = [];

                // 1. Carichiamo tutto (DB e XML)
                $configManager->caricaDispositiviDaDatabase($dbData, $dbErrors);
                if ($dbErrors) {
                    $errors[] = _t("Errore nel caricamento dati da DB: ") . $dbErrors;
                    break;
                }
                
                $dom = new DOMDocument();
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                $dom->load($configFile);
                $xpath = new DOMXPath($dom);

                $deviceListNode = $xpath->query('//device-list')->item(0);
                if (!$deviceListNode) {
                    $deviceListNode = $dom->createElement('device-list');
                    $dom->documentElement->appendChild($deviceListNode);
                }
                
                // 2. Iteriamo su TUTTI i dispositivi del config
                foreach ($dbData as $device) {
                    $deviceCode = trim($device['MBWS02COD']);
                    $deviceType = (int)$device['MBWS02TIP'];
                    $isChecked = in_array($deviceCode, $selectedDevices);

                    // Cerca se il nodo esiste GIA' nell'XML
                    $existingDeviceNode = $xpath->query('//device-list/device[@name="' . $deviceCode . '"]')->item(0);

                    if (!$isChecked) {
                        // --- CASO 1: CASELLA NON SPUNTATA ---
                        // Logica di RIMOZIONE unificata per tutti i tipi.
                        // Se non è spuntato, ma esiste nell'XML, va rimosso.
                        if ($existingDeviceNode) {
                            $deviceListNode->removeChild($existingDeviceNode);
                        }
                    } else {
                        // --- CASO 2: CASELLA SPUNTATA ---
                        // Logica di Aggiunta/Aggiornamento

                        if (in_array($deviceType, [3, 4])) {
                            // Aggiungi PEC/Email, solo se non esiste già
                            if (!$existingDeviceNode) {
                                $deviceNode = $dom->createElement('device');
                                $deviceNode->setAttribute('name', $deviceCode);
                                foreach ($mailParams as $param) {
                                    $deviceConfig = $dom->createElement('device-config', '');
                                    $deviceConfig->setAttribute('param', $param);
                                    $deviceNode->appendChild($deviceConfig);
                                }
                                $deviceListNode->appendChild($deviceNode);
                            }
                        } 
                        elseif ($deviceType == 2) {
                            // È una stampante (Tipo 2)
                            $printerName = $_POST['printer_' . $deviceCode] ?? '';

                            if ($printerName === '') {
                                // ERRORE: Casella spuntata ma select stampante vuota
                                $errors[] = sprintf(
                                    _t("Errore per '%s': La casella è spuntata ma non è stata selezionata nessuna stampante."),
                                    $deviceCode
                                );
                                // Per sicurezza, rimuoviamo se esisteva
                                if ($existingDeviceNode) {
                                    $deviceListNode->removeChild($existingDeviceNode);
                                }
                            } else {
                                // OK: Aggiungi o Aggiorna la stampante
                                if ($existingDeviceNode) {
                                    // Aggiorna
                                    $deviceConfig = $xpath->query('device-config[@param="printerName"]', $existingDeviceNode)->item(0);
                                    if ($deviceConfig) {
                                        $deviceConfig->nodeValue = htmlspecialchars($printerName);
                                    } else {
                                        $newConfig = $dom->createElement('device-config', htmlspecialchars($printerName));
                                        $newConfig->setAttribute('param', 'printerName');
                                        $existingDeviceNode->appendChild($newConfig);
                                    }
                                } else {
                                    // Crea nuovo
                                    $deviceNode = $dom->createElement('device');
                                    $deviceNode->setAttribute('name', $deviceCode);
                                    $deviceConfig = $dom->createElement('device-config', htmlspecialchars($printerName));
                                    $deviceConfig->setAttribute('param', 'printerName');
                                    $deviceNode->appendChild($deviceConfig);
                                    $deviceListNode->appendChild($deviceNode);
                                }
                            }
                        }
                    }
                } // Fine foreach $dbData

                // 3. Salva (o mostra errori)
                if (!empty($errors)) {
                    // Se ci sono errori, NON salvare e mostra i messaggi
                    $message = implode("<br>", $errors);
                    // Ricarica la config originale per evitare confusione
                    $configManager->loadConfig(); 
                } else {
                    // Salva le modifiche sull'XML
                    $dom->save($configFile);
                    $message = _t('Dispositivi aggiornati correttamente');
                }

                break;
            case 'datasource':
                $odbcUpdates = []; // Array per memorizzare gli aggiornamenti per odbc.ini

				foreach ($config->datasource->{'set-property'} as $setProperty) {
					$propertyName = (string)$setProperty['property'];
					if (isset($_POST[$propertyName])) {
						if ($propertyName === 'password') {
							// Aggiorna solo se è stato inserito qualcosa
							if ($_POST[$propertyName] !== '') {
								$setProperty['value'] = $_POST[$propertyName];
                                $odbcUpdates['Password'] = $_POST[$propertyName]; // Salva per odbc.ini
								error_log("Password aggiornata per '$propertyName'");
							}
						} elseif ($propertyName === 'naming') {
                            // Non fa nulla come da logica precedente
						} else {
							$setProperty['value'] = $_POST[$propertyName];
                            // Salva gli altri valori per odbc.ini
                            if ($propertyName === 'user') {
                                $odbcUpdates['UserID'] = $_POST[$propertyName];
                            } elseif ($propertyName === 'serverName') {
                                $odbcUpdates['System'] = $_POST[$propertyName];
                            }
						}
					}
				}

				$configManager->saveConfig(); // Salva il file XML
				$message = _t('Datasource aggiornato con successo');
				
                // ---  MODIFICA ODBC.INI (Metodo robusto) ---
                if (!empty($odbcUpdates) && file_exists($odbcIniFile)) {
                    
                    // Aggiungiamo un controllo di sicurezza in più
                    if (!is_writable($odbcIniFile)) {
                        $message .= " " . _t("(Attenzione: impossibile scrivere su odbc.ini, permessi insufficienti)");
                        // Aggiungiamo i messaggi di debug per chiarezza
                        $message .= " [DEBUG: " . implode(" | ", $debugMessages) . "]";

                    } else {
                        $lines = file($odbcIniFile);
                        if ($lines === false) {
                            error_log("Impossibile leggere il file odbc.ini: " . $odbcIniFile);
                            $message .= " " . _t("(Attenzione: impossibile leggere odbc.ini)");
                        } else {
                            $newLines = [];
                            foreach ($lines as $line) {
                                $trimmedLine = trim($line);
                                if (isset($odbcUpdates['System']) && preg_match('/^System\s*=/i', $trimmedLine)) {
                                    $newLines[] = 'System = ' . $odbcUpdates['System'] . "\n";
                                } elseif (isset($odbcUpdates['UserID']) && preg_match('/^UserID\s*=/i', $trimmedLine)) {
                                    $newLines[] = 'UserID = ' . $odbcUpdates['UserID'] . "\n";
                                } elseif (isset($odbcUpdates['Password']) && preg_match('/^Password\s*=/i', $trimmedLine)) {
                                    $newLines[] = 'Password = ' . $odbcUpdates['Password'] . "\n";
                                } else {
                                    $newLines[] = $line; // Mantieni la riga originale
                                }
                            }

                            $newIniContent = implode('', $newLines);
                            
                            if (file_put_contents($odbcIniFile, $newIniContent) === false) {
                                error_log("Impossibile scrivere nel file odbc.ini: " . $odbcIniFile);
                                $message .= " " . _t("(Attenzione: aggiornamento odbc.ini fallito durante la scrittura)");
                            } else {
                                $message .= " " . _t("(odbc.ini aggiornato con successo)");
                            }
                        }
                    }
                } elseif (!empty($odbcUpdates)) {
                    error_log("File odbc.ini non trovato: " . $odbcIniFile);
                    $message .= " " . _t("(Attenzione: file odbc.ini non trovato)");
                    // Aggiungiamo i messaggi di debug per chiarezza
                    $message .= " [DEBUG: " . implode(" | ", $debugMessages) . "]";
                }
                // --- FINE MODIFICA ODBC.INI ---
				break;

            case 'notifica':
                if (isset($_POST['applicationserver_notifica']) && is_array($_POST['applicationserver_notifica'])) {
                    $notifichePulite = array_filter(
                        array_map('trim', $_POST['applicationserver_notifica']),
                        fn($v) => $v !== ''
                    );

                    // Validazione URL
                    $valid_urls = [];
                    foreach ($notifichePulite as $url) {
                        if (filter_var($url, FILTER_VALIDATE_URL)) {
                            $valid_urls[] = $url;
                        }
                    }

                    // Rimozione duplicati
                    $valid_urls = array_unique($valid_urls);

                    // Carica con DOMDocument per manipolazione pulita
                    $dom = new DOMDocument();
                    $dom->preserveWhiteSpace = false;
                    $dom->formatOutput = true;
                    $dom->load($configFile);

                    $xpath = new DOMXPath($dom);

                    // Rimuovi tutti i nodi esistenti
                    foreach ($xpath->query('//applicationserver-notifica') as $oldNode) {
                        $oldNode->parentNode->removeChild($oldNode);
                    }

                    // Aggiungi nuovi nodi
                    $root = $dom->documentElement;
                    foreach ($valid_urls as $url) {
                        $newNode = $dom->createElement('applicationserver-notifica', $url);
                        $root->appendChild($newNode);
                    }

                    // Salva
                    $dom->save($configFile);

                    $message = _t('Application server notifica aggiornato con successo');
                }
                break;

            case 'restore':
                if (isset($_POST['restore_backup'])) {
                    echo _t("Ripristino del backup selezionato...");
                    $selectedBackup = basename($_POST['restore_backup']);
                    $backupFile = $backupDir . '/' . $selectedBackup;
                    if (file_exists($backupFile)) {
                        $configManager->restoreBackup($backupFile);
                        header("Location: manager.php?page=restore&restored=" . urlencode($selectedBackup));
                        exit();
                    } else {
                        $message = _t('Backup selezionato non trovato');
                    }
                    
                }
                break;  
            case "cups":
                require 'cups.php';
                break;
            default:
                $message = _t('Azione non riconosciuta');
        }

        // Ricarichiamo la config aggiornata
        $configManager->loadConfig();
        $config = $configManager->getConfig();

    } catch (Exception $e) {
		$message = _t("Errore during l'operazione: ") . $e->getMessage();
    }
} else {
    // Se GET o altro, carichiamo config per visualizzazione
    $config = $configManager->getConfig();
}

switch ($page) {
	case 'log':
		include 'views/log_preview.php';
		break;
	case 'restore':
		$backups = $configManager->getBackups();
		include 'views/restore_backups_view.php';
		break;

	case 'system':
		$systemConfig = $config->system; 
		include 'views/system_view.php';
		break;

	case 'cups':
		
		// Carica i nomi dei dispositivi esistenti dal file di configurazione per controllare il vincolo tra cups e il device. Se almeno una cups è assegnata a un device non si fa la cancellazione
		$existingDeviceNames = [];

		$dom = new DOMDocument();
		$dom->load($configFile);
		$xpath = new DOMXPath($dom);
		$deviceNodes = $xpath->query('/j01printserver-config/device-list/device');

		foreach ($deviceNodes as $deviceNode) {
			$name = $deviceNode->getAttribute('name');
			if ($name) {
				$existingDeviceNames[] = trim($name);
			}
		}

		//Assegnazione della select
		$existingDeviceMappings = []; // es: ['DEV001' => 'HP_LaserJet']

		foreach ($deviceNodes as $deviceNode) {
			$name = $deviceNode->getAttribute('name');
			$trimmedName = trim($name);
			if ($trimmedName) {
				$deviceConfigNode = null;
				foreach ($deviceNode->childNodes as $child) {
					if ($child->nodeName === 'device-config' && $child->getAttribute('param') === 'printerName') {
						$existingDeviceMappings[$trimmedName] = $child->nodeValue;
						break;
					}
				}
			}
		}

		include 'views/cups_view.php';
		break;

	case 'configurazione':

		include 'views/configurazione_view.php';
		break;

	case 'device':
		$configManager->caricaDispositiviDaDatabase($dbData, $dbErrors);

		// Carica i nomi dei dispositivi esistenti dal file di configurazione
		$existingDeviceNames = [];

		$dom = new DOMDocument();
		$dom->load($configFile);
		$xpath = new DOMXPath($dom);
		$deviceNodes = $xpath->query('/j01printserver-config/device-list/device');

		foreach ($deviceNodes as $deviceNode) {
			$name = $deviceNode->getAttribute('name');
			if ($name) {
				$existingDeviceNames[] = trim($name);
			}
		}

		//Assegnazione della select
		$existingDeviceMappings = []; // es: ['DEV001' => 'HP_LaserJet']

		foreach ($deviceNodes as $deviceNode) {
			$name = $deviceNode->getAttribute('name');
			$trimmedName = trim($name);
			if ($trimmedName) {
				$deviceConfigNode = null;
				foreach ($deviceNode->childNodes as $child) {
					if ($child->nodeName === 'device-config' && $child->getAttribute('param') === 'printerName') {
						$existingDeviceMappings[$trimmedName] = $child->nodeValue;
						break;
					}
				}
			}
		}

		require_once 'cups.php';

		$printerObj = new Printer();
		$elenco = $printerObj->getPrinters();

		$cupsPrinters = [];

		foreach ($elenco['printers'] as $printer) {
			$cupsPrinters[] = [
				'name' => trim(string: $printer['name']),
				'status' => $printer['status']
			];
		}

		include 'views/device_view.php';
		break;

	case 'datasource':
		include 'views/datasource_view.php';
		break;

	case 'notifica':
		include 'views/notifica_view.php';
		break;

	case 'easter-egg':
		if (
			isset($_SERVER['HTTP_REFERER']) &&
			str_contains($_SERVER['HTTP_REFERER'], 'manager.php?page=home')
		) {
			// Accesso valido: redirect reale alla pagina html standalone
			header("Location: easter-egg/index.html");
		} else {
			include 'views/home_view.php';
		}
		break;

	default:
		include 'views/home_view.php';
		break;
}

