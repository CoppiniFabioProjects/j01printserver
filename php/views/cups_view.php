<?php
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

include 'views/traduzioni.php';
include 'includes/header.php';
include 'conf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //$configManager->createBackup();

    require_once 'cups.php';

    $formAction = $_POST['form_action'] ?? '';    

    if ($formAction === 'add-printer') {

        
        $printerObj = new Printer();

        $getNameUsed = $printerObj->getPrinters();
        $existingNames = array_column($getNameUsed['printers'], 'name');

        $printerName = $_POST['printer_name'] ?? '';
        if (preg_match('/[\/#\s]/', $printerName)) {
			$_SESSION['message'] = _t("Nome stampante non valido: non può contenere /, # o spazi") . '.';
            header("Location: manager.php?page=cups");
            exit();
        }elseif (in_array($printerName, $existingNames)) {
			$_SESSION['message'] = _t("Nome stampante già usato") . '.';
            header("Location: manager.php?page=cups");
            exit();
        }

        $connectionUrl = $_POST['connection_url'] ?? '';
        $connectionType = $_POST['connection_type'] ?? '';
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        $modello = $_POST['modello'] ?? '';

        $prefixes = [
        "Internet Printing Protocol (ipp)" => 'ipp://',
        "Internet Printing Protocol (ipps)" => 'ipps://',
        "Internet Printing Protocol (http)" => 'http://',
        "Internet Printing Protocol (https)" => 'https://',
        "AppSocket/HP JetDirect" => 'socket://',
        "LPD/LPR Host o stampante" => 'lpd://',
        ];

        $expectedPrefix = $prefixes[$connectionType] ?? '';

        //Per quei due tipi di connessione, salta il controllo sul prefisso dell'URL perché non serve.
        if ($connectionType !== "CUPS-PDF (Virtual PDF Printer)" && $connectionType !== "Backend Error Handler") {
            // Validazione URL
            if (!filter_var($connectionUrl, FILTER_VALIDATE_URL)) {
				$_SESSION['message'] = _t("Url Invalido") . ': ' . $connectionUrl;
                header("Location: manager.php?page=cups");
                exit();
            }
            // Controllo del prefisso
            if ($expectedPrefix && strpos($connectionUrl, $expectedPrefix) !== 0) {
				$_SESSION['message'] = _t("Url Invalido") . ': ' . $connectionUrl;
                header("Location: manager.php?page=cups");
                exit();
            }
        }

        $dataDrivers = $printerObj->getCupsManufacturersAndModels();

        //var_dump($dataDrivers);
        //die();
        $manufacturer = $_POST['manufacturer'] ?? '';
        $modello = $_POST['modello'] ?? '';

        $key = trim($manufacturer . ' ' . $modello);
		$ppdPath = $dataDrivers['drivers'][$key] ?? _t("Percorso PPD non trovato");

        $result = $printerObj->addPrinter(
            $printerName,
            $connectionUrl,
            $description,
            $location,
            $ppdPath,
        );
        if ($result) {
			$_SESSION['message'] = _t("Dispositivo aggiunto correttamente: ") . $printerName;
            header("Location: manager.php?page=cups");
            exit();
        } else {
			$_SESSION['message'] = _t("Errore rilevato in aggiunta di: ") . $printerName;
            header("Location: manager.php?page=cups");
            exit();
        }
        
    }elseif ($formAction === 'edit-printer') {

        $printerObj = new Printer();

        $printerName = $_POST['printer_name'] ?? '';
        if (preg_match('/[\/#\s]/', $printerName)) {
			$_SESSION['message'] = _t("Nome stampante non valido: non può contenere /, # o spazi") . '.';
            header("Location: manager.php?page=cups");
            exit();
        }
        $connectionUrl = $_POST['connection_url'] ?? '';
        $connectionType = $_POST['connection_type'] ?? '';
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        $modello = $_POST['modello'] ?? '';

        $prefixes = [
        "Internet Printing Protocol (ipp)" => 'ipp://',
        "Internet Printing Protocol (ipps)" => 'ipps://',
        "Internet Printing Protocol (http)" => 'http://',
        "Internet Printing Protocol (https)" => 'https://',
        "AppSocket/HP JetDirect" => 'socket://',
        "LPD/LPR Host o stampante" => 'lpd://',
        ];

        $expectedPrefix = $prefixes[$connectionType] ?? '';

        //Per quei due tipi di connessione, salta il controllo sul prefisso dell'URL perché non serve.
        if ($connectionType !== "CUPS-PDF (Virtual PDF Printer)" && $connectionType !== "Backend Error Handler") {
            // Validazione URL
            if (!filter_var($connectionUrl, FILTER_VALIDATE_URL)) {
				$_SESSION['message'] = _t("Errore URL non valido");
                header("Location: manager.php?page=cups");
                exit();
            }

            // Controllo del prefisso
            if ($expectedPrefix && strpos($connectionUrl, $expectedPrefix) !== 0) {
                error_log("URL invalido: $connectionUrl");
				$_SESSION['message'] = _t("Errore: L'URL deve avere il prefisso ->") . " " . $expectedPrefix . ".";
                header("Location: manager.php?page=cups");
                exit();
            }
        }

        $dataDrivers = $printerObj->getCupsManufacturersAndModels();

        $manufacturer = $_POST['manufacturer'] ?? '';
        $modello = $_POST['modello'] ?? '';

        $key = trim($manufacturer . ' ' . $modello);
		$ppdPath = $dataDrivers['drivers'][$key] ?? _t("Percorso PPD non trovato");
        
        $result = $printerObj->editPrinter(
            $printerName,
            $connectionUrl,
            $description,
            $location,
            $ppdPath,
        );
        if ($result) {
			$_SESSION['message'] = _t("Dispositivo modificato correttamente: ") . $printerName;
            header("Location: manager.php?page=cups");
            exit();
        } else {
			$_SESSION['message'] = _t("Errore durante la modifica di") . ": " . $printerName . ".";
            header("Location: manager.php?page=cups");
        }
        exit;

    }elseif($formAction === 'delete-printer'){

        //Se c'è vincolo di associazione tra stampante e cups
        $name = trim($_POST['delete_printer_name']);

        if (in_array($name, $existingDeviceMappings, true)) {
			$_SESSION['message'] = $name . ' ' . _t("attualmente associato a una o più stampanti. Verifica la pagina Device List prima di procedere.");
            header("Location: manager.php?page=device");
            exit();
        } else {
            $printer = new Printer();
            $result = $printer->deletePrinter($name);
            if ($result) {
                //Non so perche non riesco a far visualizzare il messaggio di riuscita della cancellazione
				$_SESSION['message'] = _t("Dispositivo eliminato correttamente:") . ' ' . $name . '.';
                header("Location: manager.php?page=cups");
                exit();
            } else {
                $_SESSION['message'] = _t("Errore durante l'eliminazione di ") . $name . '.';
                exit();
            }
        }
    }elseif ($formAction === 'queue-printer') {

        error_log("Form action: $formAction");
        error_log('DEBUG POST: ' . print_r($_POST, true));


        $action = $_POST['queue_action'] ?? '';
        $printerName = $_POST['printer_name'] ?? null;
        $destinationPrinter = $_POST['destination_printer'] ?? null;
        $jobId = null;

        switch ($action) {
            case 'cancelJob':
                $jobId = $_POST['delete_job_id'] ?? null;
                break;
            case 'pauseJob':
                $jobId = $_POST['pause_job_id'] ?? null;
                break;
            case 'resumeJob':
                $jobId = $_POST['resume_job_id'] ?? null;
                break;
            case 'moveJob':
                $jobId = $_POST['move_job_id'] ?? null;
                $printerName = $_POST['printer_name'] ?? null;
                $destinationPrinter = $_POST['destination_printer'] ?? null;
                break;
			case 'cancelAllJobs':
				break;
			case 'clearAllJobs':
				break;
            default:
				$_SESSION['message'] = _t("Azione non riconosciuta") . ': ' . $action;
                break;
        }
        $myQueue = new Printer();
        $response = $myQueue->managePrintQueue($action, $printerName, $jobId, $destinationPrinter);
        // Se la risposta ha dei job, li carico per la vista
        if (isset($response['jobs'])) {
            $queueResponse = $response;
            $jobs = $queueResponse['jobs'];
        }
    } else if ($formAction === 'setting-options-printer') {
        $printerName = $_POST['options_printer_name'] ?? '';
        $options = [];

        foreach ($_POST as $key => $value) {
            if (in_array($key, ['form_action', 'csrf_token', 'options_printer_name'])) continue;
            $options[$key] = $value; // es: ['PageSize' => 'A4']
        }

        $printer = new Printer();
        $response = $printer->setPrinterOptions($printerName, $options);

        // (opzionale) log o redirect o stampa JSON
        if ($response['success']) {
            $message = $response['message'];
        } else {
            $message = $response['message'];
        }
    } else{	
		$_SESSION['message'] = _t("Azione non valida") . '.';
    }
}


require_once 'cups.php';

//Stampanti
$printerObj = new Printer();
$data = $printerObj->getPrinters();

$printerList = [];

foreach ($data['printers'] as $printer) {
    $printerList[] = [
        'name'            => $printer['name'],
        'status'          => $printer['status'],
        'enabled_since'   => $printer['enabled_since'],
        'description'     => $printer['description'],
        'location'        => $printer['location'],
        'connection_type' => $printer['connection_type'],
        'connection_url'  => $printer['connection_url'],
        'manufacturer'    => $printer['manufacturer'],
        'model'           => $printer['model'],
    ];
}

//Code delle stampanti
$filter = $_GET['filter'] ?? 'all';  // default 'all'
$queue = $printerObj->getQueue($filter);
$jobs = $queue['jobs'] ?? [];

//produttori, modelli e pathdriver
$dataDrivers = $printerObj->getCupsManufacturersAndModels();
// Inizializzo array per i modelli
$brotherModelsArray = [];
// Controllo se il file esiste e lo leggo
if (file_exists($brother_models)) {
    // Leggo tutte le righe in un array
    $lines = file($brother_models, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
 
    foreach ($lines as $line) {
        // Estraggo il codice modello come valore della option (prima parte prima del trattino lungo)
        // Esempio: "HL-L2865DW — Monocromatico, 34 ppm, duplex" => value = HL-L2865DW
        $parts = explode('—', $line, 2);
        
        if (count($parts) == 2) {
            $modelCode = trim($parts[0]);
            $description = trim($line); // tutta la riga come descrizione
            
            $brotherModelsArray[$modelCode] = $description;
        }
    }
} else {
	echo _t("File brother_models.txt non trovato") . '.';
}

?>

<div class="content" style="margin-bottom: 100vh;">

    <img src="img/logo.svg" alt="Logo" class="logo-img spin animate-on-load" />
    
    <h1 class="page-title"><?php echo _t("Amministrazione Stampanti"); ?></h1>

    <?php include 'includes/message.php'; ?>
    
    <div class="title-separator"></div>

    <?php include "cups_view_head.php"; ?>

</div>

<?php include "cups_view_add.php" ?>

<?php include "cups_view_queue.php" ?>

<?php include "cups_view_manage.php" ?>

<button id="toTopBtn"><i data-feather="arrow-up-circle"></i></button>

<?php include "cups_view_modal.php" ?>

<?php include "cups_view_modal_options.php" ?>

<?php include "cups_view_modal_jobs_for_printer.php" ?>

<script>
	const manufacturersModels = <?= json_encode($dataDrivers['manufacturers'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
	const driversPaths = <?= json_encode($dataDrivers['drivers'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
	const csrfToken = <?= json_encode($_SESSION['csrf_token']) ?>;
</script>

<script src="../js/cups_view.js"></script>