<?php
// ... (tutta la logica di scansione PHP all'inizio rimane invariata) ...
// Inizializza le variabili.
$scan_results = null;
$auto_add_message = null;
// Array per contenere tutti i dispositivi trovati, strutturato per la tabella.
$found_devices = [];
$tutt_apposto = null;

// Aumenta il tempo massimo di esecuzione per consentire a nmap e snmp di completare.
ini_set('max_execution_time', 300); // 5 minuti
set_time_limit(300);

// Controlla se il form inviato è quello per la scansione della rete.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'scan-network') {

	unset($_SESSION['message']);
	
    // Esegui la scansione solo se nmap e snmpget sono disponibili.
    $nmap_path = trim(shell_exec('which nmap'));
    $snmpget_path = trim(shell_exec('which snmpget')); //Controlla se snmpget è presente.

    if (empty($nmap_path)) {
        // Imposta un messaggio di errore se nmap non viene trovato.
        $_SESSION['message'] = _t("Comando nmap non trovato. Assicurati che nmap sia installato sul server e accessibile dal server web");
    } elseif (empty($snmpget_path)) {
        // Imposta un messaggio di errore se snmpget non viene trovato.
        $_SESSION['message'] = _t("Comando snmpget non trovato. Assicurati che il pacchetto snmp sia installato sul server e accessibile dal server web");
    } else {
        
        // --- INIZIO NUOVA VALIDAZIONE ROBUSTA ---
        
        $ip_input_raw = trim($_POST['host'] ?? '');
        $validation_passed = false; // Flag per avviare la scansione
        $network_range = ''; // Inizializza il range finale
            
        if (empty($ip_input_raw)) {
            // Controllo 1: Input vuoto
            $_SESSION['message'] = _t("Nessun indirizzo IP fornito per la scansione. Inserisci un IP nel campo di scansione");
        
        } else {
            $ip_input = explode(':', $ip_input_raw)[0]; // Rimuove eventuale porta
            $ip_to_validate = $ip_input;
            $cidr_suffix_str = ''; 
            $is_cidr = false;
            
            // Controlla se l'utente ha inserito un range CIDR (es. 192.168.1.0/24)
            if (strpos($ip_input, '/') !== false) {
                $is_cidr = true;
                $cidr_parts = explode('/', $ip_input);

                // Controllo 2: Formato CIDR valido (es. non "192.168.1.0/" o "/24" o "1.2.3.4/24/altro")
                if (count($cidr_parts) !== 2 || empty($cidr_parts[0]) || empty($cidr_parts[1])) {
                    $_SESSION['message'] = _t("Formato range non valido. Deve essere [IP]/[SUBNET] (es. 192.168.1.0/24)") . " (Input: " . htmlspecialchars($ip_input_raw) . ")";
                } else {
                    $ip_to_validate = $cidr_parts[0]; // La parte IP
                    $cidr_suffix_str = $cidr_parts[1]; // La parte subnet

                    // Controllo 2b: Subnet valida (numero tra 0 e 32)
                    if (!is_numeric($cidr_suffix_str) || $cidr_suffix_str < 0 || $cidr_suffix_str > 32) {
                        $_SESSION['message'] = _t("Subnet del range non valida. La parte dopo il / deve essere un numero tra 0 e 32 (es. /24)") . " (Input: /" . htmlspecialchars($cidr_suffix_str) . ")";
                    } else {
                        $cidr_suffix_str = '/' . $cidr_suffix_str; // Formato corretto
                    }
                }
            } // Fine controlli CIDR
            
            // Se non ci sono ancora errori, procedi a validare la parte IP
            if (!isset($_SESSION['message'])) {
                
                // Controllo 3: Caratteri non validi (solo numeri e punti)
                if (!preg_match('/^[\d\.]+$/', $ip_to_validate)) {
                    $_SESSION['message'] = _t("Caratteri non validi in indirizzo IP. Inserisci solo numeri e punti") . " (Input: " . htmlspecialchars($ip_to_validate) . ")";
                
                } else {
                    $ip_parts = explode('.', $ip_to_validate);
                    
                    // Controllo 4: Deve avere 4 parti
                    if (count($ip_parts) !== 4) {
                        $_SESSION['message'] = _t("Formato IP non valido. Indirizzo IP deve avere 4 parti separate da punti (es. 192.168.1.10)") . " (Input: " . htmlspecialchars($ip_to_validate) . ")";
                    
                    } else {
                        // Controllo 5 & 6: Ogni parte deve essere un numero 0-255 e non vuota
                        $all_parts_valid = true;
                        foreach ($ip_parts as $part) {
                            // is_numeric accetta stringhe numeriche, filter_var è più stretto ma qui va bene
                            // Controllo per "192..1.1" (parte vuota) e "192.a.1.1"
                            if ($part === '' || !is_numeric($part) || $part < 0 || $part > 255) {
                                $all_parts_valid = false;
                                $_SESSION['message'] = _t("Valore IP non valido. Ogni numero in IP deve essere tra 0 e 255 e non può essere vuoto") . " (Valore errato: '" . htmlspecialchars($part) . "')";
                                break; // Esce dal foreach, inutile continuare
                            }
                        } // Fine foreach
                        
                        if ($all_parts_valid) {
                            // --- SUCCESSO VALIDAZIONE ---
                            $validation_passed = true;
                            
                            if ($is_cidr) {
                                // Usa il CIDR fornito dall'utente (es. 192.168.1.0/24)
                                $network_range = $ip_to_validate . $cidr_suffix_str;
                            } else {
                                // Costruisci il range .0/24 dall'IP singolo
                                $network_base = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
                                $network_range = $network_base . '.0/24';
                            }
                        }
                    }
                }
            }
        }
        // --- FINE NUOVA VALIDAZIONE ROBUSTA ---


        // Esegui la scansione Nmap SOLO se la validazione è passata
        if ($validation_passed && !empty($network_range)) {

            // Esegue il comando nmap.
            $command = "nmap -T4 -n -p 80,443,631,9100,515 --open " . escapeshellarg($network_range);
            $scan_results = shell_exec($command);

            if (empty($scan_results)) {
                    // Messaggio se la scansione non produce risultati.
                    $scan_results = _t("Scansione completata. Nessun dispositivo trovato con le porte specificate aperte nel range") . htmlspecialchars($network_range);
                } else {
                    // --- LOGICA DI PARSING CON SNMP ---
                    // Divide l'output di nmap in blocchi, uno per ogni host.
                    $reports = preg_split("/Nmap scan report for/", $scan_results);

                    foreach ($reports as $report) {
                        if (empty(trim($report))) continue;

                        $ip = '';
                        // Estrae l'indirizzo IP dalla prima riga di ogni blocco.
                        $first_line = strtok($report, "\n");
                        if (preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $first_line, $ip_matches)) {
                            $ip = $ip_matches[0];
                        }

                        if ($ip) {
                            // Esegue snmpget per ottenere più informazioni dal dispositivo ---
                            $snmp_command = "snmpget -v1 -c public " . escapeshellarg($ip) . " iso.3.6.1.2.1.1.1.0 iso.3.6.1.2.1.1.5.0 iso.3.6.1.2.1.25.3.2.1.3.1 iso.3.6.1.2.1.43.15.1.1.5.1.1";
                            $snmp_command_with_timeout = "timeout 2 " . $snmp_command;
                            $snmp_result = shell_exec($snmp_command_with_timeout);

                            // Estrae tutti i nomi, li unisce e identifica produttore/modello ---
                            if (!empty($snmp_result) && preg_match_all('/STRING: "([^"]+)"/', $snmp_result, $name_matches)) {
                                
                                if(!empty($name_matches[1])) {
                                    $full_device_name = implode(' - ', $name_matches[1]);
                                    
                                    $manufacturer = '';
                                    $model_to_set = ''; 

                                    // --- LOGICA DI RILEVAMENTO ---
                                    if (stripos($full_device_name, 'Brother') !== false) {
                                        $manufacturer = 'Brother';
                                        
                                        // Caricamento dinamico dei modelli Brother ---
                                        // la variabile $manufacturers_models (usata anche dal frontend JS) è disponibile in questo scope
                                        $known_brother_models = [];
                                        if (isset($manufacturers_models) && is_array($manufacturers_models) && isset($manufacturers_models['Brother'])) {
                                            // Usa array_unique per essere coerente con la logica Javascript che usa Set
                                            $known_brother_models = array_unique($manufacturers_models['Brother']);
                                        }
                                        
                                        foreach ($known_brother_models as $model) {
                                            if (stripos($full_device_name, $model) !== false) {
                                                $model_to_set = $model;
                                                break;
                                            }
                                        }
                                        
                                        // Se è una Brother ma non un modello specifico, e contiene PCL, diventa Generic PCL
                                        if (empty($model_to_set) && stripos($full_device_name, 'PCL') !== false) {
                                            $manufacturer = 'Generic';
                                            $model_to_set = 'PCL Laser Printer';
                                        }

                                    } elseif (stripos($full_device_name, 'Zebra') !== false) {
                                        // Logica per Zebra
                                        $manufacturer = 'Raw';
                                        $model_to_set = 'Queue';
                                    } elseif (stripos($full_device_name, 'Generic') !== false) {
                                        $manufacturer = 'Generic';
                                        if (stripos($full_device_name, 'PCL') !== false) {
                                            $model_to_set = 'PCL Laser Printer';
                                        }
                                    } else {
                                        // Logica di fallback per altri produttori
                                        $known_manufacturers = ['Dymo', 'Epson', 'Fuji', 'Hp', 'Index', 'Intellitech', 'Oki', 'Raw', 'Ricoh', 'Ipp'];
                                        foreach ($known_manufacturers as $m) {
                                            if (stripos($full_device_name, $m) !== false) {
                                                $manufacturer = ($m === 'Hewlett-Packard') ? 'HP' : $m;
                                                break;
                                            }
                                        }
                                    }

                                    // --- NUOVA STRUTTURA DATI PER UNA RIGA PER DISPOSITIVO ---
                                    $device_info = [
                                        'name'         => $full_device_name,
                                        'manufacturer' => $manufacturer,
                                        'model'        => $model_to_set,
                                        'port_515'     => '',
                                        'port_631'     => '',
                                        'port_9100'    => ''
                                    ];
                                    $ports_found = false;

                                    // Estrae tutte le righe con porte aperte.
                                    if (preg_match_all("/(\d+)\/tcp\s+open\s+(\S+)/", $report, $port_matches, PREG_SET_ORDER)) {
                                        foreach ($port_matches as $match) {
                                            $port = $match[1];
                                            $service = $match[2];
                                            
                                            switch ($port) {
                                                case '515':
                                                    $device_info['port_515'] = $service;
                                                    $ports_found = true;
                                                    break;
                                                case '631':
                                                    $device_info['port_631'] = $service;
                                                    $ports_found = true;
                                                    break;
                                                case '9100':
                                                    $device_info['port_9100'] = $service;
                                                    $ports_found = true;
                                                    break;
                                            }
                                        }
                                    }

                                    // Aggiunge il dispositivo all'array solo se ha un nome E almeno una delle porte di stampa rilevanti.
                                    if ($ports_found) {
                                        $found_devices[$ip] = $device_info;
                                    }
                                }
                            }
                            // Se il nome non viene trovato tramite SNMP, l'IP viene semplicemente ignorato.
                        }
                    }
                    // --- FINE LOGICA DI PARSING ---
            }
        
        } elseif (!isset($_SESSION['message'])) {
            // Fallback: Se la validazione fallisce ma nessun messaggio è stato impostato (non dovrebbe succedere)
            $_SESSION['message'] = _t("Indirizzo IP o range non valido. Impossibile determinare il range di rete") . " (Input: " . htmlspecialchars($ip_input_raw ?? '') . ")";
        }
        
        // Se la validazione è fallita, $tutt_apposto non viene impostato e il messaggio di errore apparirà
        if ($validation_passed && empty($_SESSION['message'])) {
            $tutt_apposto = _t("Scansione eseguita correttamente.");
        }
        
        // Rimossa la vecchia logica if/else che è stata sostituita
    }
}

$systemConfig = $config->system;

// --- NUOVA LOGICA PER VALORE "STICKY" ---
// Determina quale IP mostrare nell'input.
// Se la pagina è stata caricata dopo un submit (POST), mostra l'ultimo IP usato.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['host'])) {
    $ip_to_display = htmlspecialchars($_POST['host']);
} else {
    // Altrimenti (primo caricamento, GET), calcola il range /24 dal config.
    $default_host_raw = $systemConfig->host ?? '';
    
    if (empty($default_host_raw)) {
        $ip_to_display = '';
    } else {
        // Rimuove eventuale porta
        $default_host = explode(':', $default_host_raw)[0];
        
        // Se il default è già un range, usalo
        if (strpos($default_host, '/') !== false) {
            $ip_to_display = htmlspecialchars($default_host);
        } else {
            // Se è un IP singolo, calcola il .0/24
            $ip_parts = explode('.', $default_host);
            if (count($ip_parts) === 4) {
                $network_base = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
                $ip_to_display = htmlspecialchars($network_base . '.0/24');
            } else {
                // Se il valore di default è strano (es. 'localhost'), mostralo com'è
                $ip_to_display = htmlspecialchars($default_host);
            }
        }
    }
}
// --- FINE LOGICA "STICKY" ---

?>

<style>
    #host {
        font-family: "Times New Roman", serif;
        font-weight: 400;
        font-size: 1rem;
        /* width: 100%; */ /* Nota: 'width: 100%' potrebbe rompere il layout del form. */
        border-width: 1px;
        border-style: solid;
        border-color: #ccc; /* Fallback per browser che non supportano border-image */
        border-image: linear-gradient(45deg, rgb(36, 97, 163), rgb(255, 0, 0), rgb(255, 230, 0)) 1 / 1 / 0 stretch;
        padding: 0.8rem; /* Ho usato 0.8rem, 1rem sembrava un po' grande */
        outline: none;
        margin-left: 0.5rem; /* Aggiunto spazio */
    }
    
    #host:focus {
        border-image: linear-gradient(45deg, rgb(36, 97, 163), rgb(255, 0, 0), rgb(255, 230, 0)) 1 / 1 / 0 stretch;
        outline: none;
    }
</style>


<div id="add-printer" class="content">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;">
        <h1 class="page-title"><?php echo _t("Aggiungi Una Stampante"); ?></h1>
    </div>
	
    <!-- Form per la selezione manuale -->
    <form id="selectPrinterForm" method="POST" action="" style="margin-top:10px;">
        <input type="hidden" name="csrf_token" value="<?= isset($csrf_token) ? htmlspecialchars($csrf_token) : '' ?>">
        <input type="hidden" name="form_action" value="add-printer">
        <fieldset>
            <select name="printer" required>
                <option value="" disabled selected><?php echo _t("Seleziona una procedura di aggiunta stampante"); ?></option>
                <option value="AppSocket/HP JetDirect">AppSocket/HP JetDirect - 9100</option>
                <option value="LPD/LPR Host">LPD/LPR Host - 515</option>
                <option value="Internet Printing Protocol (ipp)">Internet Printing Protocol (ipp) - 631</option>
                <option value="Internet Printing Protocol (ipps)">Internet Printing Protocol (ipps) - 631</option>
                <option value="Internet Printing Protocol (http)">Internet Printing Protocol (http) - 80</option>
                <option value="Internet Printing Protocol (https)">Internet Printing Protocol (https) - 443</option>
            </select>
        </fieldset>
        <button type="button" id="continueBtn" class="RowBtn" style="background:none; border:none; cursor:pointer; text-align:center">
            <i style="height: 35px; width: 35px;" data-feather="arrow-right-circle"></i>
        </button>
    </form>
	<div class="title-separator"></div>
    <!-- Form per avviare la scansione di rete -->
    <form id="scanNetworkForm" method="POST" action="" style="display: flex; justify-content: center; align-items: center; box-shadow: none; border: none; box-sizing: border-box; text-align: center; margin-bottom: 10px;">
        <input type="hidden" name="csrf_token" value="<?php echo isset($csrf_token) ? htmlspecialchars($csrf_token) : '' ?>">
        <input type="hidden" name="form_action" value="scan-network">
        <span><?php echo _t("Effettua una Scansione della Rete"); ?>:</span>
		<!-- Questo input ora controlla l'IP di scansione E MANTIENE IL VALORE DOPO IL SUBMIT -->
        <input type="text" id="host" name="host" value="<?php echo $ip_to_display; ?>" />
        <button type="submit" title="<?php echo _t("Scansiona la rete per stampanti"); ?>" class="RowBtn" style="box-shadow: none; border: none; background:none; cursor:pointer;">
            <i style="height: 35px; width: 35px; color: #2461a3; text-align: center;" data-feather="search"></i>
        </button>
    </form>


    <!-- Messaggio di errore -->
    <?php if (isset($_SESSION['message']) && !$tutt_apposto): ?>
    <div id="scan-error" style="margin-top: 20px;">
        <h2 style="color: #d9534f; text-align:center;"><?php echo _t("Errore di Scansione"); ?></h2>
        <div class="title-separator"></div>
        <pre style="background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($_SESSION['message']); ?></pre>
    </div>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- --- SEZIONE AGGIORNATA PER LA TABELLA DEI RISULTATI --- -->
    <?php if (!empty($found_devices)): ?>
    <div id="scan-results-table" style="margin-top: 25px; max-width: 1000px; margin-left: auto; margin-right: auto; padding: 20px; background-color: #fdfdfd; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-radius: 8px; overflow-x: auto;">
        <h2 style="color: #2461a3; border-bottom: 2px solid #e9ecef; padding-bottom: 10px; margin-top: 0;"><?php echo _t("Dispositivi Trovati in Rete"); ?></h2>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-family: sans-serif; font-size: 16px;">
            <thead>
                <tr style="background-color: #2461a3; color: white;">
                    <th style="padding: 16px; text-align: left;"><?php echo _t("Indirizzo IP"); ?></th>
                    <th style="padding: 16px; text-align: left;"><?php echo _t("Nome Dispositivo"); ?></th>
                    <th style="padding: 16px; text-align: left;"><?php echo _t("Porta 515"); ?></th>
                    <th style="padding: 16px; text-align: left;"><?php echo _t("Porta 631"); ?></th>
                    <th style="padding: 16px; text-align: left;"><?php echo _t("Porta 9100"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($found_devices as $ip => $device_data): ?>
                    <tr class="device-row" onmouseover="this.style.backgroundColor='#f1f1f1';" onmouseout="this.style.backgroundColor='';">
                        <td style="padding: 16px; border-bottom: 1px solid #ddd; font-weight: bold;"><?php echo htmlspecialchars($ip); ?></td>
                        <td style="padding: 16px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($device_data['name']); ?></td>
                        <td style="padding: 16px; border-bottom: 1px solid #ddd;">
                            <?php if (!empty($device_data['port_515'])): ?>
                                <?php echo htmlspecialchars($device_data['port_515']); ?>
                                <button type="button" class="RowBtn add-from-scan-btn" title="<?php echo _t('Aggiungi stampante con questa porta'); ?>" style="background:none; border:none; cursor:pointer; vertical-align: middle; margin-left: 10px;"
                                        data-ip="<?php echo htmlspecialchars($ip); ?>"
                                        data-name="<?php echo htmlspecialchars($device_data['name']); ?>"
                                        data-port="515"
                                        data-manufacturer="<?php echo htmlspecialchars($device_data['manufacturer']); ?>"
                                        data-model="<?php echo htmlspecialchars($device_data['model']); ?>">
                                    <i style="height: 24px; width: 24px;" data-feather="arrow-right-circle"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px; border-bottom: 1px solid #ddd;">
                             <?php if (!empty($device_data['port_631'])): ?>
                                <?php echo htmlspecialchars($device_data['port_631']); ?>
                                <button type="button" class="RowBtn add-from-scan-btn" title="<?php echo _t('Aggiungi stampante con questa porta'); ?>" style="background:none; border:none; cursor:pointer; vertical-align: middle; margin-left: 10px;"
                                        data-ip="<?php echo htmlspecialchars($ip); ?>"
                                        data-name="<?php echo htmlspecialchars($device_data['name']); ?>"
                                        data-port="631"
                                        data-manufacturer="<?php echo htmlspecialchars($device_data['manufacturer']); ?>"
                                        data-model="<?php echo htmlspecialchars($device_data['model']); ?>">
                                    <i style="height: 24px; width: 24px;" data-feather="arrow-right-circle"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 16px; border-bottom: 1px solid #ddd;">
                            <?php if (!empty($device_data['port_9100'])): ?>
                                <?php echo htmlspecialchars($device_data['port_9100']); ?>
                                <button type="button" class="RowBtn add-from-scan-btn" title="<?php echo _t('Aggiungi stampante con questa porta'); ?>" style="background:none; border:none; cursor:pointer; vertical-align: middle; margin-left: 10px;"
                                        data-ip="<?php echo htmlspecialchars($ip); ?>"
                                        data-name="<?php echo htmlspecialchars($device_data['name']); ?>"
                                        data-port="9100"
                                        data-manufacturer="<?php echo htmlspecialchars($device_data['manufacturer']); ?>"
                                        data-model="<?php echo htmlspecialchars($device_data['model']); ?>">
                                    <i style="height: 24px; width: 24px;" data-feather="arrow-right-circle"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif ($scan_results): ?>
    <!-- Mantiene la visualizzazione dell'output grezzo come fallback -->
    <div id="scan-results-raw" style="margin-top: 20px;">
        <h2 style="text-align:center;"><?php echo _t("Risultati Scansione Rete"); ?></h2>
        <div class="title-separator"></div>
        <pre style="background-color: #f4f4f4; border: 1px solid #ddd; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word;"><?php echo _t(htmlspecialchars(("Nessuna stampante trovata sulla rete"))); ?></pre>
    </div>
    <?php endif; ?>
</div>

<script>

// --- SCRIPT PER GESTIRE I PULSANTI DI AGGIUNTA DALLA SCANSIONE ---
/* FUNZIONAMENTO
Se trova Zebra nella descrizione seleziona come produttore "Raw" e come modello "Queue".
Se trova Generic e l'utente ha cliccato su Ipp" allora seleziona come produttore "Generic" e come modello "IPP Everywhere Printer". 
Quando l'utente clicca su printer o jetdirect e non trova nessun occorrenza di produttore nella descrizione cerca la stringa "PCL" e se c'è seleziona come produttore "Generic" e come modello "PCL Laser Printer".  
Per quanto riguarda le Brother la ricerca è diversa:
	Nella descrizione cerca se c'è brother se c'è la seleziona come produttore
	poi in base ai modelli installati presenti nella lista si cerca la sua occorrenza nella descrizione
	Se la trova allora associa produttore Brother e modello quello trovato.
	Altrimenti se non la trovo ma nella descrizione trova "PCL" allora mette come
	produttore "Generic" e come modello "PCL Laser Printer"
*/
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.add-from-scan-btn').forEach(button => {
        button.addEventListener('click', function() {
            const ip = this.getAttribute('data-ip');
            const name = this.getAttribute('data-name');
            const port = this.getAttribute('data-port');
            let manufacturer = this.getAttribute('data-manufacturer');
            let model = this.getAttribute('data-model');

            if (!ip || !name || !port) {
                _t("Dati della stampante incompleti.", t => alert(t));
                return;
            }

            let tipoConnessione = '';
            let urlPrefix = '';
            
            switch (port) {
                case '515':
                    tipoConnessione = "LPD/LPR Host";
                    urlPrefix = prefixes[tipoConnessione] || 'lpd://';
                    break;
                case '631':
                    tipoConnessione = "Internet Printing Protocol (ipp)";
                    urlPrefix = prefixes[tipoConnessione] || 'ipp://';
                    break;
                case '9100':
                    tipoConnessione = "AppSocket/HP JetDirect";
                    urlPrefix = prefixes[tipoConnessione] || 'socket://';
                    break;
                default:
                    _t("Porta non supportata per l'aggiunta rapida.", t => alert(t));
                    return;
            }

            apriModalAggiungiConNome(tipoConnessione);

            if (form && modal) {
                form.printer_name.value = ''; 
                form.description.value = name; 
                document.getElementById('connectionUrl').value = urlPrefix + ip;

                const manufacturerSelect = document.getElementById('manufacturer');
                const modelSelect = document.getElementById('modello');

                // --- NUOVA LOGICA DI SELEZIONE AUTOMATICA ---

                // La logica per Brother, Zebra e PCL è già stata gestita in PHP.
                // Lo script client-side ora riceve i valori corretti tramite data-attributes.

                // Regola 2 (JS): Se il produttore è 'Generic' e la porta è IPP.
                if (manufacturer === 'Generic' && port === '631') {
                    model = 'IPP Everywhere Printer';
                }

                // Regola 3 (JS): Se non c'è produttore e la porta è LPD o JetDirect, controlla per 'PCL'.
                if (!manufacturer && (port === '515' || port === '9100')) {
                    if (name.toLowerCase().includes('pcl')) {
                        manufacturer = 'Generic';
                        model = 'PCL Laser Printer';
                    }
                }
                
                // Regola 4 (Fallback JS): Se non c'è produttore e la porta è IPP (e non è il caso Generic).
                if (!manufacturer && port === '631') {
                    manufacturer = 'Ipp';
                    model = 'Everywhere';
                }

                // --- APPLICA I VALORI AL FORM ---
                if (manufacturerSelect && manufacturer) {
                    manufacturerSelect.value = manufacturer;
                    // Attiva l'evento 'change' per caricare i modelli corrispondenti
                    manufacturerSelect.dispatchEvent(new Event('change'));

                    if (modelSelect && model) {
                        // Aspetta che la lista dei modelli sia popolata, poi seleziona quello corretto.
                        setTimeout(() => {
                            modelSelect.value = model;
                            // Attiva un altro change event per aggiornare campi dipendenti come il PPD path
                            modelSelect.dispatchEvent(new Event('change'));
                        }, 200); 
                    }
                }
                
                modal.style.display = 'block';
            } else {
                console.error("Le variabili 'form' o 'modal' non sono definite. Assicurati che lo script principale sia caricato prima di questo.");
            }
        });
    });
});
</script>





