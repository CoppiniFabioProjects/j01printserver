<?php
include("conf.php");
include_once("views/traduzioni.php");
session_start();
require_once 'system_controller.php';
require_once 'config_manager.php';
// Logica per Docker Info
$dockerInfo = null;
$errorMessage = null;
try {
    $dockerInfo = SystemController::getDockerInfo();
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}
// Logica per il Login
if (file_exists($configFile)) {
    $config = simplexml_load_file($configFile);
} else {
    echo '❌ '._t("File di config non trovato");
}
$correctUser = (string)$config->system->username;
$correctPass = (string)$config->system->password;
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST["user"]) ?? '';
    $pass = trim($_POST["pass"]) ?? '';
    if ($user === $correctUser && $pass === $correctPass) {
        session_regenerate_id(true);
        $_SESSION["loggedin"] = true;
        
        $_SESSION['user'] = $user; // salva user in sessione
        $_SESSION['attempts'] = 0; // reset tentativi
		$_SESSION['utonto'] = false; // tipo utente

        header("Location: manager.php?page=home");
        exit();
    } else {
		$configManager = new ConfigManager(configFile: $configFile, backupDir: $backupDir);
		
		if($configManager->checkOdbcConnectionForUsers($user, $pass) === true){
			session_regenerate_id(true);
			$_SESSION["loggedin"] = true;
			
			$_SESSION['user'] = $user; // salva user in sessione
			$_SESSION['attempts'] = 0; // reset tentativi
			$_SESSION['utonto'] = true; // tipo utente
			
			header("Location: manager.php?page=home");
			exit();
		}else{
			if($configManager->checkOdbcConnectionForUsersMBUT02($user, $pass) === true){
				session_regenerate_id(true);
				$_SESSION["loggedin"] = true;
				
				$_SESSION['user'] = $user; // salva user in sessione
				$_SESSION['attempts'] = 0; // reset tentativi
				$_SESSION['utonto'] = true; // tipo utente
				
				header("Location: manager.php?page=home");
				exit();
			}else{
				$_SESSION['attempts']++;
				$error = _t("Credenziali errate!");
			}
		}
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo _t("Accesso - J01PrintServer"); ?></title>
    <link rel="stylesheet" href="css/style.css" />
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
</head>
<body>
    <div class="container">
        <div class="top"></div>
        <div class="bottom"></div>
        <div class="center">
            <h2><?php echo _t("J01PrintServer"); ?></h2>
            
            <hr class="separator" />
            <h2><?php echo _t("Accedi"); ?></h2>

            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="text" name="user" placeholder="<?php echo _t("Utente"); ?>" required />
                <input type="password" name="pass" placeholder="<?php echo _t("Password"); ?>" required />
                <button type="submit" class="login-button"><?php echo _t("Login"); ?></button>
            </form>
        </div>
    </div>

    <!-- Inizio del footer integrato -->
    <?php  
        // Logica PHP originariamente in footer.php
        $descrizione = ''; // Inizializza la variabile per sicurezza
        foreach ($config->datasource->{'set-property'} as $setProperty) {
            $property = (string)$setProperty['property'];  
            $value = (string)$setProperty['value'];
            
            if ($property == "description") {
                $descrizione = $value;
                break;
            }
        }  
    ?>
    <div class="footer-note">
        <div class="footer-content">
            <?php if ($dockerInfo): ?>
                <div class="footer-docker-info">
                    <strong><?php echo _t("Container"); ?>:</strong> <?= ucfirst(htmlspecialchars($dockerInfo['container'])) ?> 🐳 |
                    <strong>HTTP:</strong> <?= htmlspecialchars($dockerInfo['http']) ?> 🌐 |
                    <strong>HTTPS:</strong> <?= htmlspecialchars($dockerInfo['https']) ?> 🔒 |
                    <strong>CUPS:</strong> <?= htmlspecialchars($dockerInfo['cups']) ?> ☕
                </div>
            <?php elseif ($errorMessage): ?>
                <div class="footer-docker-info">
                    <span>⚠️ <?= _t(htmlspecialchars($errorMessage)) ?></span>
                </div>
            <?php endif; ?>
            <div class="footer-copyright">
                1992-2025 &copy; <?php echo _t("Copyright by");?> 01INFORMATICA S.R.L. - v. 1.00 - <?php echo _t(htmlspecialchars($descrizione)); ?>
            </div>
        </div>
    </div>
    <!-- Fine del footer integrato -->
</body>
</html>