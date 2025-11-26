<?php

// 1. Includiamo il file delle traduzioni UNA SOLA VOLTA all'inizio.
// Ora la classe Printer è autonoma e sa cos'è la funzione _t().
require_once __DIR__ . '/views/traduzioni.php';

class Printer {

    // 3. (Bonus) Includiamo il file di configurazione nel costruttore
    // per non ripeterlo in ogni funzione.
    public function __construct() {
        include "conf.php";
    }

    //basandosi sul risultato di lpinfo
    public function getCupsManufacturersAndModels() {
        $output = $this->runCommand('lpinfo -m');
        $manufacturers = [];
        $drivers = []; // Inizializziamo l'array dei driver

        $isbrother = false;

        foreach ($output as $line) {
            $parts = preg_split('/\s+/', $line, 2);

            if (count($parts) < 2) {
                continue;
            }

            list($ppdPath, $modelDesc) = $parts;

            // Estrai produttore e modello dalla descrizione (es: "Zebra EPL2 Label Printer")
            $modelWords = preg_split('/\s+/', trim($modelDesc), 2);

            if (count($modelWords) < 2) {
                $manufacturer = ucfirst(strtolower($modelWords[0]));
                $model = ''; // nessuna descrizione extra
            } else {
                list($manufacturer, $model) = $modelWords;
                $manufacturer = ucfirst(strtolower($manufacturer));
            }

            if ($manufacturer == 'Brother') $isbrother = true;
            if (!isset($manufacturers[$manufacturer])) {
                $manufacturers[$manufacturer] = [];
            }

            $manufacturers[$manufacturer][] = $model;

            // Salva il percorso .ppd associato al produttore e modello
            $key = trim($manufacturer . ' ' . $model);
            $drivers[$key] = $ppdPath;
        }

        if (!$isbrother) {
            $manufacturers['Brother'] = [];
        }

        // Restituisci sia i produttori/modelli che i percorsi .ppd
        return [
            'manufacturers' => $manufacturers,
            'drivers' => $drivers
        ];
    }


    //addPrinters
    public function addPrinter(
        string $printerName,
        string $deviceUri = '',
        string $description = '',
        string $location = '',
        string $ppdPath = ''
    ) {
        include "conf.php";
        if (!$printerName) {
            return ['success' => false, 'message' => _t("Nome stampante mancante")];
        }
        if (file_exists($printer_file)) {
            if (unlink($printer_file)) {
            } else {
                echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer.txt")]);
                exit;
            }
        }
        if (file_exists($printer_log)) {
            if (unlink($printer_log)) {

            } else {
				echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer_log.txt")]);
                exit;
            }
        }
        $printerName = str_replace(" ", '_',trim($printerName));

        $cmdParts = [
            '/sbin/lpadmin',
            '-p', $printerName,
            '-E',
            '-m', $ppdPath,
        ];

        if (trim($deviceUri) !== '') {
            $cmdParts[] = '-v';
            $cmdParts[] = $deviceUri;
        }

        if (trim($description) !== '') {
            $description = str_replace(" ", '_',trim($description));

            $cmdParts[] = '-D';
            $cmdParts[] = $description;
        }

        if (trim($location) !== '') {
            $location = str_replace(" ", '_',trim($location));
           $cmdParts[] = '-L';
            $cmdParts[] = $location;
        }

        $COMMAND = implode(' ', $cmdParts);

        $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);

        sleep(5);

        if ($result === false) {
            return ['success' => false, 'message' => _t("Errore durante l'aggiunta della stampante")];
        } else {
            return ['success' => true, 'message' => _t('Stampante aggiunta con successo') . ' ' . $printerName . '.'];
        }

    }

    //delPrinters
    public function deletePrinter($printerName) {
        include "conf.php";
        if (empty($printerName)) {
			return ['success' => false, 'message' => _t('Nome stampante mancante')];
        }
        if (file_exists($printer_file)) {
            if (unlink($printer_file)) {
            } else {
				echo json_encode(['success' => false, 'message' => _t('Fallita la cancellazione del file printer.txt')]);
                exit;
            }
        }
        if (file_exists($printer_log)) {
            if (unlink($printer_log)) {
                
            } else {
				echo json_encode(['success' => false, 'message' => _t('Fallita la cancellazione del file printer_log.txt')]);
                exit;
            }
        }

        $COMMAND = "/sbin/lpadmin -x " . $printerName;

        $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);

        sleep(5);

        if ($result === false) {
			return ['success' => false, 'message' => _t('Errore durante la rimozione della stampante') . '.'];
        } else {
			return ['success' => true, 'message' => _t('Stampante rimossa con successo:') . ' ' . $printerName . '.'];
        }

    }


    //editPrinters
    public function editPrinter(string $printerName,
        string $deviceUri = '',
        string $description = '',
        string $location = '',
        string $ppdPath = ''
    ) {
        include "conf.php";

        if (!$printerName) {
			return ['success' => false, 'message' => _t("Nome stampante mancante")];
        }
        if (file_exists($printer_file)) {
            if (unlink($printer_file)) {
                
            } else {
				echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer.txt")]);
                exit;
            }
        }
        if (file_exists($printer_log)) {
            if (unlink($printer_log)) {
                
            } else {
				echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer_log.txt")]);
                exit;
            }
        }
           $printerName = str_replace(" ", '_',trim($printerName));

        $cmdParts = [
            '/sbin/lpadmin',
            '-p', $printerName,
            '-E',
            '-m', $ppdPath,
        ];

        if (trim($deviceUri) !== '') {
            $cmdParts[] = '-v';
            $cmdParts[] = $deviceUri;
        }

        if (trim($description) !== '') {
            $description = str_replace(" ", '_',trim($description));

            $cmdParts[] = '-D';
            $cmdParts[] = $description;
        }

        if (trim($location) !== '') {
            $location = str_replace(" ", '_',trim($location));
           $cmdParts[] = '-L';
            $cmdParts[] = $location;
        }

        $COMMAND = implode(' ', $cmdParts);
        
        $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);

        sleep(5);

        if ($result === false) {
			return ['success' => false, 'message' => _t('Errore durante la modifica della stampante') . '.'];
        } else {
			return ['success' => true, 'message' => _t('Stampante modificata con successo: ') . $printerName . '.'];
        }

    }


    //getPrinters
    public function getPrinters() {
        include "conf.php";
        $basicInfo = $this->runCommand("lpstat -p | grep '^printer'");
        $printers = [];

        foreach ($basicInfo as $row) {
            // Estrai il nome della stampante
            preg_match('/printer\s+(\S+)/', $row, $printerMatch);
            $name = $printerMatch[1] ?? null;
            if (!$name) continue;

            // Estrai lo stato in modo più robusto (es. "idle", "disabled", "printing", "not responding")
            // Considera di cercare parole chiave dopo il nome
            if (preg_match('/is\s+([a-zA-Z\s]+)/', $row, $statusMatch)) {
                $status = 'enabled';
            } else {
                $status = 'disabled';
            }

            // Estrai la data abilitazione: cerca "enabled since" o altri pattern
            if (preg_match('/enabled since\s+([^\-]+)/', $row, $enabledSinceMatch)) {
                $enabled_since = trim($enabledSinceMatch[1]);
            } elseif (preg_match('/since\s+([^\-]+)/', $row, $enabledSinceMatch)) {
                $enabled_since = trim($enabledSinceMatch[1]);
            } else {
                $enabled_since = 'disabled';
            }

            // Recupera URI di connessione
            $uriCmd = $this->runCommand('lpstat -v ' . escapeshellarg($name));
            preg_match('/device for .*?: (.+)/', implode("\n", $uriCmd), $uriMatch);
            $connectionUrl = $uriMatch[1] ?? '';
            $connectionType = explode(':', $connectionUrl)[0] ?? 'unknown';

            // Recupera descrizione e location
            $printerDetails = $this->runCommand('lpstat -l -p ' . escapeshellarg($name));
            $description = '';
            $location = '';

            foreach ($printerDetails as $detailRow) {
                if (stripos($detailRow, 'Description:') !== false) {
                    $description = trim(str_replace('Description:', '', $detailRow));
                } elseif (stripos($detailRow, 'Location:') !== false) {
                    $location = trim(str_replace('Location:', '', $detailRow));
                }
            }

            // Leggi PPD per manufacturer e model
            $ppdPath = "/etc/cups/ppd/" . $name . ".ppd";
            $manufacturer = '';
            $model = '';

            if (file_exists($ppdPath)) {
                $lines = file($ppdPath);
                foreach ($lines as $line) {
                    if (stripos($line, '*NickName:') !== false) {
                        preg_match('/\*NickName:\s*"(.*?)"/', $line, $match);
                        if (isset($match[1])) {
                            $words = explode(' ', $match[1], 2);
                            $manufacturer = $words[0];
                            $model = $words[1] ?? '';
                        }
                        break;
                    }
                }
            }
		
            $printers[] = [
                'name' => $name,
                'status' => $status,
                'enabled_since' => $enabled_since,
                'connection_type' => $connectionType,
                'connection_url' => $connectionUrl,
                'description' => $description,
                'location' => $location,
                'manufacturer' => $manufacturer,
                'model' => $model
            ];
        }

        return ['printers' => $printers];
    }


    //getPrinterByName per modifica della stampante.
    public function getPrinterByName(string $name) {
        include "conf.php";

        // Ottieni lo stato e info base della singola stampante
        $basicInfo = $this->runCommand('lpstat -p ' . escapeshellarg($name));
        if (empty($basicInfo)) {
            return ['error' => _t('Stampante non trovata: ') . $name . "."];
        }

        $row = $basicInfo[0] ?? '';

        // Estrai lo stato in modo più robusto (es. "idle", "disabled", "printing", "not responding")
        if (preg_match('/is\s+([a-zA-Z\s]+)/', $row, $statusMatch)) {
            $status = trim($statusMatch[1]);
        } else {
            $status = 'unknown';
        }

        // Estrai la data abilitazione
        if (preg_match('/enabled since\s+([^\-]+)/i', $row, $enabledSinceMatch)) {
            $enabled_since = trim($enabledSinceMatch[1]);
        } elseif (preg_match('/since\s+([^\-]+)/i', $row, $enabledSinceMatch)) {
            $enabled_since = trim($enabledSinceMatch[1]);
        } else {
            $enabled_since = 'disabled';
        }

        // Recupera URI di connessione
        $uriCmd = $this->runCommand('lpstat -v ' . escapeshellarg($name));
        $connectionUrl = '';
        if (!empty($uriCmd)) {
            preg_match('/device for .*?: (.+)/', implode("\n", $uriCmd), $uriMatch);
            $connectionUrl = $uriMatch[1] ?? '';
        }
        $connectionType = explode(':', $connectionUrl)[0] ?? 'unknown';

        // Recupera descrizione e location
        $printerDetails = $this->runCommand('lpstat -l -p ' . escapeshellarg($name));
        $description = '';
        $location = '';

        foreach ($printerDetails as $detailRow) {
            if (stripos($detailRow, 'Description:') !== false) {
                $description = trim(str_ireplace('Description:', '', $detailRow));
            } elseif (stripos($detailRow, 'Location:') !== false) {
                $location = trim(str_ireplace('Location:', '', $detailRow));
            }
        }

        // Leggi PPD per manufacturer e model
        $ppdPath = "/etc/cups/ppd/" . $name . ".ppd";
        $manufacturer = '';
        $model = '';

            if (file_exists($ppdPath)) {
                $lines = file($ppdPath);
                foreach ($lines as $line) {
                    if (stripos($line, '*NickName:') !== false) {
                        preg_match('/\*NickName:\s*"(.*?)"/', $line, $match);
                        if (isset($match[1])) {
                            $words = explode(' ', $match[1], 2);
                            $manufacturer = $words[0];
                            $model = $words[1] ?? '';
                        }
                        break;
                    }
                }
            }
        return [
            'name' => $name,
            'status' => $status,
            'enabled_since' => $enabled_since,
            'connection_type' => $connectionType,
            'connection_url' => $connectionUrl,
            'description' => $description,
            'location' => $location,
            'manufacturer' => $manufacturer,
            'model' => $model
        ];
    }


    /*GESTIONE CODE STAMPA*/


    // Funzione per ottenere la coda di stampa di tutte le stampanti
    public function getQueue(string $filter = 'all'): array {
		$command = 'lpstat -l ';

		// Decidiamo il parametro -W in base al filtro
		if ($filter === 'completed') {
			$command .= '-W completed ';
		} elseif ($filter === 'not-completed') {
			$command .= '-W not-completed ';
		} else {
			$command .= '-W all '; // o senza parametro
		}

		// Aggiungi il flag -o per ottenere tutte le informazioni necessarie
		$command .= '-o';

		// Esegui il comando
		$output = $this->runCommand($command, $returnVar);

		// Se c'è stato un errore o non ci sono risultati
		if ($returnVar !== 0 || empty($output)) {
			return ['error' => _t("Nessun lavoro in coda o errore durante l'esecuzione del comando") . '.'];
		}

		// Inizializzazione per il parsing dei lavori
		$jobs = [];
		$currentJob = null;
		$status = null;
		$alert = null;

		// Analizziamo ogni riga dell'output
		foreach ($output as $line) {
			$line = trim($line);
			if ($line === '') continue;

			// Controlla se la riga rappresenta un nuovo lavoro
			if (preg_match('/^(\S+)\s+(\S+)\s+(\d+)\s+(.*)$/', $line, $matches)) {
				// Se c'è un lavoro precedente, aggiungilo all'array
				if ($currentJob !== null) {
					$jobs[] = $currentJob;
				}

				// Estrazione dell'ID del lavoro e del nome della stampante
				$jobId = $matches[1];
				$printerName = null;
				if (strpos($jobId, '-') !== false) {
					$parts = explode('-', $jobId, 2);
					$printerName = $parts[0];
				}

				// Nuovo lavoro
				$currentJob = [
					'job_id'      => $jobId,
					'user'        => $matches[2],
					'size'        => $matches[3] . ' bytes',
					'datetime'    => $matches[4],
					'printer_name'=> $printerName,
					'title'       => null,
					'status'      => null,
					'alert'       => null,
				];
			}
			// Estrazione del titolo del lavoro
			elseif (strpos($line, 'title =') === 0) {
				if ($currentJob !== null) {
					$title = trim(substr($line, strlen('title =')));
					$currentJob['title'] = $title;
				}
			}
			// Estrazione dello stato del lavoro
			elseif (strpos($line, 'Status:') === 0) {
				if ($currentJob !== null) {
					$status = trim(substr($line, strlen('Status:')));
					$currentJob['status'] = $status;
				}
			}
			// Estrazione degli avvisi (Alerts)
			elseif (strpos($line, 'Alerts:') === 0) {
				if ($currentJob !== null) {
					$alert = trim(substr($line, strlen('Alerts:')));
					$currentJob['alert'] = $alert;
				}
			}
		}

		// Aggiungi l'ultimo lavoro (se presente)
		if ($currentJob !== null) {
			$jobs[] = $currentJob;
		}

		return ['jobs' => $jobs];
	}

    // Funzione per ottenere la coda di stampa di una singola stampante
    public function getQueueByPrinter(string $printerName): array {
        if (empty($printerName)) {
			return ['error' => _t("Nome stampante mancante") . '.'];
        }
        $output = $this->runCommand("lpq -P " . escapeshellarg($printerName), $returnVar);
        $jobs = [];
        foreach ($output as $line) {
            $line = trim($line);
            if ($line === '' || str_contains($line, 'Rank') || str_contains($line, $printerName)) {
                continue;
            }
            if (preg_match('/^(\S+)\s+(\S+)\s+(\d+)\s+(.+?)\s+(\d+\s+bytes)$/', $line, $matches)) {
                $jobs[] = [
                    'rank'   => $matches[1],
                    'user'   => $matches[2],
                    'job_id' => $matches[3],
                    'file'   => $matches[4],
                    'size'   => $matches[5],
                ];
            }
        }
        return [
            'printer_name' => $printerName,
            'jobs' => $jobs
        ];
    }


    /**
     * /
     * @param string $action
     * @param mixed $printerName
     * @param mixed $jobId
     * @param mixed $destinationPrinter
     * @return array{error: string|array{message: string, success: bool}}
     */
    //Comandi cups per la gestione delle code di stampa
    public function managePrintQueue(
        string $action, 
        ?string $printerName = null, 
        ?string $jobId = null, 
        ?string $destinationPrinter = null
    ): array {
		$return = ['error' => _t("Azione non riconosciuta") . '.'];
        switch ($action) {

            case 'moveJob':
                include "conf.php";

                if (empty($jobId) || empty($destinationPrinter)) {
					return ['success' => false, 'message' => _t("ID lavoro o stampante di destinazione mancanti.")];
                }

                if (file_exists($printer_file)) {
                    unlink($printer_file);
                }
                if (file_exists($printer_log)) {
                    unlink($printer_log);
                }

                $COMMAND = "/sbin/lpmove " . $jobId . " " . $destinationPrinter;

                $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);

                if ($result === false) {
					return ['success' => false, 'message' => _t("Errore durante lo spostamento del job ") . $jobId . "."]; 
                } else {
					return ['success' => true, 'message' => _t('Spostamento riuscito: ') . $jobId . ' --> ' . $destinationPrinter . '.'];
                }

            // Cancella TUTTI i lavori (tutte le stampanti) + restart CUPS
			case 'clearAllJobs':

				include "conf.php";

				// Pulisce i file precedenti
				if (file_exists($printer_file)) {
					if (!unlink($printer_file)) {
						echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer.txt")]);
						exit;
					}
				}

				if (file_exists($printer_log)) {
					if (!unlink($printer_log)) {
						echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer_log.txt")]);
						exit;
					}
				}

				$COMMAND = "/usr/bin/cancel -a";

				// Scrive nel file dei comandi
				$result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);

				if ($result === false) {
					return ['success' => false, 'message' => _t('Errore durante la cancellazione di tutte le code') . '.'];
				} else {
					return ['success' => true, 'message' => _t('Tutte le code sono state cancellate e CUPS è stato riavviato con successo.')];
				}

            // Disabilita la coda di stampa (sospende la stampante)
            case 'disablePrinter':
                include "conf.php"; 
                if (empty($printerName)) {
					return ['success' => false, 'message' => _t('Nome stampante mancante') . '.'];
                }
                if (file_exists($printer_file)) {
                    if (unlink($printer_file)) {
                        
                    } else {
						echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer.txt")]);
                        exit;
                    }
                }
                if (file_exists($printer_log)) {
                    if (unlink($printer_log)) {
                        
                    } else {
						echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer_log.txt")]);
                        exit;
                    }
                }
                $COMMAND = "/sbin/cupsdisable " . $printerName;
                $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);
                if ($result === false) {
					return [
						'success' => false,
						'message' => _t('Errore durante il tentativo di disabilitare la stampante ') . $printerName . '.'
					];
                } else {
					return [
						'success' => true,
						'message' => _t('Stampante disabilitata con successo: ') . $printerName . '.'
					];
                }
            // Abilita la coda di stampa (riattiva la stampante)
            case 'enablePrinter':
                include "conf.php"; 
                if (empty($printerName)) {
					return [
						'success' => false,
						'message' => _t('Nome stampante mancante') . '.'
					];                      
				}
                if (file_exists($printer_file)) {
                    if (unlink($printer_file)) {
                        
                    } else {
						echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer.txt")]);
                        exit;
                    }
                }
                if (file_exists($printer_log)) {
                    if (unlink($printer_log)) {
                        
                    } else {
						echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer_log.txt")]);
                        exit;
                    }
                }
                $COMMAND = "/sbin/cupsenable " . $printerName;
                $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);
                if ($result === false) {
					return ['success' => false, 'message' => _t('Errore durante il tentativo di abilitare la stampante ') . $printerName . '.'];
                } else {
					return ['success' => true, 'message' => _t('Stampante abilitata con successo: ') . $printerName . '.'];
                }
            // Metti in pausa un lavoro di stampa
            case 'pauseJob':
                include "conf.php";
                if (empty($jobId)) {
					return ['success' => false, 'message' => _t('ID lavoro non valido o mancante') . '.'];
				}
                if (file_exists($printer_file)) {
                    if (unlink($printer_file)) {
                        
                    } else {
						echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer.txt")]);
                        exit;
                    }
                }
                if (file_exists($printer_log)) {
                    if (unlink($printer_log)) {
                        
                    } else {
						echo json_encode(['success' => false, 'message' => _t("Fallita la cancellazione del file printer_log.txt")]);
                        exit;
                    }
                }
                $COMMAND = "/usr/bin/lp -i " . $jobId . " -H hold";
                $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);
                if ($result === false) {
                    return ['success' => false, 'message' => _t("Errore durante lo stop del job: ") . $jobId . '.'];
                } else {
                    return ['success' => true, 'message' => _t("Job messo in pausa con successo: ") . $jobId . '.'];
                }
            // Riprendi un lavoro di stampa in pausa
            case 'resumeJob':
                include "conf.php"; 

                if (empty($jobId)) {
                    return ['success' => false, 'message' => _t("ID lavoro non valido o mancante") . '.'];
                }
                
                if (file_exists($printer_file)) {
                    if (unlink($printer_file)) {
                        
                    } else {
                        echo json_encode(['success'=> false, 'message'=> _t("Fallita la cancellazione del file printer.txt")]);
                        exit;
                    }
                }
                if (file_exists($printer_log)) {
                    if (unlink($printer_log)) {
                        
                    } else {
                        echo json_encode(['success'=> false, 'message'=> _t("Fallita la cancellazione del file printer_log.txt")]);
                        exit;
                    }
                }
                $COMMAND = "/usr/bin/lp -i " . $jobId . " -H resume";
                $result = file_put_contents($printer_file, $COMMAND, FILE_APPEND);
                if ($result === false) {
                    return ['success' => false, 'message' => _t('Errore durante la cancellazione del job: ') . "$jobId."];
                } else {
                    return ['success' => true, 'message' => _t('Job cancellato con successo:') . "$jobId."];
                }
            default:
                return $return;
        }
    }



    // Funzione per ottenere le opzioni di una stampante
    public function getPrinterOptions($printerName) {
        $output = $this->runCommand('lpoptions -p' . $printerName . ' -l');
        if ($output === false) {
            return ['success' => false, 'message' => _t('Errore durante la get opzioni della stampante') . '.'];
        } else {
            // Output è un array di linee, unisci in stringa
            $output = implode("\n", $output);

            $options = [];
            $lines = explode("\n", trim($output));

            foreach ($lines as $line) {
                if (strpos($line, ':') === false) continue;
                list($key, $valuesString) = explode(':', $line, 2);
                $key = trim($key);
                $valuesString = trim($valuesString);
                $rawValues = preg_split('/\s+/', $valuesString);
                $values = [];
                foreach ($rawValues as $v) {
                    $selected = false;
                    if (strpos($v, '*') === 0) {
                        $selected = true;
                        $v = substr($v, 1);
                    }
                    $values[] = ['value' => $v, 'selected' => $selected];
                }

                $options[$key] = $values;
            }

            return [
                'success' => true, 'message' => _t('Opzioni ottenute con successo: ') . " $printerName.",
                'options' => $options
            ];
        }
    }


    // Funzione per settare le opzioni di una stampante
    public function setPrinterOptions($printerName, $options) {
        include "conf.php";  // contiene $printer_file, ecc.

        if (empty($printerName)) {
            return ['success' => false, 'message' => _t("Nome stampante mancante")];
        }

        if (!is_array($options) || empty($options)) {
            return ['success' => false, 'message' => _t("Nessuna opzione da impostare")];
        }

        // Cancella eventuali file precedenti
        if (file_exists($printer_file)) unlink($printer_file);
        if (file_exists($printer_log)) unlink($printer_log);

        // Costruisci la stringa delle opzioni
        $optionsString = '';
        foreach ($options as $key => $value) {
            // Usa solo la parte prima dello slash, se esiste
            $cleanKey = explode('/', $key)[0];
            $optionsString .= ' -o ' . "$cleanKey=$value";
        }

        //$result = file_put_contents($printer_file, $optionsString . PHP_EOL, FILE_APPEND);
        
        // Comando finale
        $COMMAND = "/usr/bin/lpoptions -p " . $printerName . $optionsString;

        sleep(5);

        // Salva il comando nel file (se usi questo metodo per esecuzione esterna)
        $result = file_put_contents($printer_file, $COMMAND . PHP_EOL, FILE_APPEND);

        if ($result === false) {
            return ['success' => false, 'message' => _t('Errore durante il salvataggio del comando per: ') . "$printerName."];
        }else{
            return ['success' => true, 'message' => _t('Opzioni modificate con successo: ') . "$printerName."];
        }
    }

    public function runCommand(string $command, int &$returnVar = null): array {
        exec($command . ' 2>&1', $output, $returnVar);
        return $output;
    }
}
