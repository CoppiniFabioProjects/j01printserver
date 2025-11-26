<?php
include 'includes/header.php';
require_once 'system_controller.php';

$dockerInfo = null;
$errorMessage = null;
$messaggioSessione = null;

if (isset($_SESSION['message'])) {
	$messaggioSessione = $_SESSION['message'];
} else {
	$messaggioSessione = "";
}				
try {
    $dockerInfo = SystemController::getDockerInfo();
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}
?>

<style>
    /* Stili per centrare i bottoni */
    .home-buttons {
        display: flex;
        justify-content: center; /* Centra i bottoni orizzontalmente */
        align-items: center;     /* Allinea i bottoni verticalmente */
        flex-wrap: wrap;         /* Permette ai bottoni di andare a capo su schermi piccoli */
        gap: 1rem;               /* Aggiunge spazio tra i bottoni */
        margin-top: 2rem;        /* Aggiunge un po' di margine sopra */
    }
</style>

<div class="content">
    <div class="docker-container">
        <img src="img/logo.svg" alt="Logo" class="logo-img spin animate-on-load" />
        <?php if ($dockerInfo): ?>
        <div class="docker-info">
            <p><strong><?= ucfirst(htmlspecialchars($dockerInfo['container'])) ?> container 🐳</strong></p>
            <p><strong><?php echo _t("Porta HTTP"); ?>: <?= htmlspecialchars($dockerInfo['http']) ?> 🌐</strong></p>
            <p><strong><?php echo _t("Porta HTTPS"); ?>: <?= htmlspecialchars($dockerInfo['https']) ?> 🔒</strong></p>
			<p><strong><?php echo _t("Porta CUPS"); ?>: <?= htmlspecialchars($dockerInfo['cups']) ?> ☕</strong></p>
        </div>
        <?php elseif ($errorMessage): ?>
            <div class="alert-message">⚠️ <?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
    </div>
    <h1 class="page-title"><?php echo _t("Homepage di"); ?> J01PrintServer</h1>
    <div class="title-separator"></div>
    <?php if (!empty($message)): ?>
        <div class="alert-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
	
	<?php if (!empty($messaggioSessione)): ?>
        <div class="alert-message"><?= htmlspecialchars($messaggioSessione) ?></div>
    <?php endif; ?>
	

	<?php    
	$jarDir = '/codice01/j01printserver/info01/';
	$currentJar = $jarDir . 'j01printserver.jar';
	
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
	$timestamp = filemtime($currentJar);
    $data_modifica = date('d/m/Y H:i:s', $timestamp);
	?>
    <div class="home-buttons">
        <!-- Pulsante Riavvia Docker -->
		<form method="POST" action="actions/printserver.php" onsubmit="return checkMsgRiavviaDocker('<?php echo _t("Confermi il riavvio Docker?"); ?>');">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
			<button type="submit" name="action" value="docker-restart">
				<i data-feather="power"></i><?php echo _t("Riavvia Docker"); ?>
			</button>
		</form>
		
		<!-- Pulsante per aprire log errori -->
		<button id="logButton" onclick="openLog()" title="<?php echo _t("Apri Log Errori"); ?>"><i data-feather="file-text"></i><?php echo _t("Log Errori"); ?></button>
        
		<?php if (!($_SESSION['utonto'] ?? false)): ?>
			<!-- Pulsante per andare alla pagina restore -->
			<form method="POST" action="manager.php">
				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"/>
				<input type="hidden" name="page" value="restore" />
				<button type="submit"><i data-feather="archive"></i><?php echo _t("Ripristina Versioni"); ?></button>
			</form>
		<?php endif; ?>
	
		<!-- Pulsante aggiorna JAR -->
		<form method="POST" action="actions/update_jar.php" onsubmit="return checkMsgAggiornaPrintserver('<?php echo _t("Sei sicuro di voler aggiornare il PrintServer?"); ?>');">
			<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
			<button type="submit"><i data-feather="download-cloud"></i><?php echo _t("Aggiorna PrintServer"); ?>
			<p><?php echo _t("Data installazione ") .$data_modifica; ?>
			</button>
		</form>

    </div>
</div>
<button id="toTopBtn"><i data-feather="arrow-up-circle"></i></button>

<script>
	function checkMsgRiavviaDocker(msg) {
		if (confirm(msg))  {
			return true;
		} else {
			const loadingModal = document.getElementById("loadingModal");
			loadingModal.style.display = 'none';
			loadingModal.style.visibility = 'hidden';
			loadingModal.style.opacity = '50'; //con l'opacity a 50 il loading non viene mostrato per permettere di fare annulla
			return false;
		}
	}
	function checkMsgAggiornaPrintserver(msg) {
		if (confirm(msg))  {
			return true;
		} else {
			const loadingModal = document.getElementById("loadingModal");
			loadingModal.style.display = 'none';
			loadingModal.style.visibility = 'hidden';
			loadingModal.style.opacity = '50'; //con l'opacity a 50 il loading non viene mostrato per permettere di fare annulla
			return false;
		}
	}
	function openLog() {
		const url = "manager.php?page=log";
		window.open(url, '_blank', 'noopener,noreferrer');
	}
	
  document.addEventListener('DOMContentLoaded', () => {
    const secretPhrase = "sono un duro";
    let inputBuffer = "";

    document.addEventListener('keydown', (e) => {
      // Prendi solo lettere, spazi, numeri (escludo tasti come shift, ctrl, ecc)
      const key = e.key.toLowerCase();

      // Accetta solo lettere, numeri e spazio
      if (/^[a-z0-9 ]$/.test(key)) {
        inputBuffer += key;

        // Mantieni buffer con lunghezza massima uguale a secretPhrase
        if (inputBuffer.length > secretPhrase.length) {
          inputBuffer = inputBuffer.slice(inputBuffer.length - secretPhrase.length);
        }

        // Controlla se buffer coincide con la frase segreta
        if (inputBuffer === secretPhrase) {
          window.location.href = "manager.php?page=easter-egg";
        }
      }
    });
  });
</script>
