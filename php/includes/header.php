<?php include 'loader.php';?>
<?php include_once 'views/traduzioni.php';?>

<!-- header.php -->
<script src="../js/navbar.js"></script>
<script src="../js/functions.js"></script>
<script src="../js/icons.js"></script>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo _t("Gestione"); ?> J01PrintServer</title>
    <link rel="icon" href="img/logo.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="img/logo.png">

    <link rel="stylesheet" href="../css/navbar.css" />
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/cups.css" />
</head>
<body>
    <button class="navbar__toggle" aria-label="Apri menu">
        <i data-feather="menu"></i>
    </button>
    <nav class="navbar">
		<ul class="navbar__menu">
			<li class="navbar__item">
				<a href="?page=home" class="navbar__link <?= $page == 'home' ? 'active' : '' ?>">
					<i data-feather="home"></i><span><?php echo _t("Home"); ?></span>
				</a>
			</li>

			<?php if (!($_SESSION['utonto'] ?? false)): ?>
				<li class="navbar__item">
					<a href="?page=system" class="navbar__link <?= $page == 'system' ? 'active' : '' ?>">
						<i data-feather="monitor"></i><span><?php echo _t("System"); ?></span>
					</a>
				</li>
				<li class="navbar__item">
					<a href="?page=datasource" class="navbar__link <?= $page == 'datasource' ? 'active' : '' ?>">
						<i data-feather="code"></i><span><?php echo _t("Datasource"); ?></span>
					</a>
				</li>
			<?php endif; ?>

			<li class="navbar__item">
				<a href="?page=cups" class="navbar__link <?= $page == 'cups' ? 'active' : '' ?>">
					<i data-feather="coffee"></i><span><?php echo _t("Amministrazione Stampanti"); ?></span>
				</a>
			</li>
			<li class="navbar__item">
				<a href="?page=device" class="navbar__link <?= $page == 'device' ? 'active' : '' ?>">
					<i data-feather="printer"></i><span><?php echo _t("Stampanti Codice01"); ?></span>
				</a>
			</li>

			<?php if (!($_SESSION['utonto'] ?? false)): ?>
				<li class="navbar__item">
					<a href="?page=notifica" class="navbar__link <?= $page == 'notifica' ? 'active' : '' ?>">
						<i data-feather="bell"></i><span><?php echo _t("Notifica"); ?></span>
					</a>
				</li>
				<li class="navbar__item">
					<a href="?page=configurazione" class="navbar__link <?= $page == 'configurazione' ? 'active' : '' ?>">
						<i data-feather="settings"></i><span><?php echo _t("Configurazione"); ?></span>
					</a>
				</li>
			<?php endif; ?>

			<li class="navbar__item">
				<a href="?logout=1" class="navbar__link">
					<i data-feather="log-out"></i><span><?php echo _t("Logout"); ?></span>
				</a>
			</li>
		</ul>
		<div class="navbar__logo">
			<img src="img/Logo_01_silver.gif" alt="Logo J01" />
		</div>
	</nav>