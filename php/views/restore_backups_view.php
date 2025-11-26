<?php


// Gestione anteprima file in nuova finestra
if (isset($_GET['preview'])) {

    $file = $backupDir . '/' . basename($_GET['preview']);
    if (file_exists($file)) {
        header('Content-Type: text/plain');
        readfile($file);
        exit;
    } else {
        echo _t("File non trovato.");
        exit;
    }
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/message.php'; ?>

<div class="content">
    <img src="img/logo.svg" alt="Logo" class="logo-img spin animate-on-load" />
    <h1 class="page-title"><?php echo _t("Backup e Ripristino di J01PrintServer"); ?></h1>
    <div class="title-separator"></div>
    <form method="POST" action="?page=restore">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="table-wrapper">
            <table class="fl-table">
                <thead>
                    <tr>
                        <th><?php echo _t("Data"); ?></th>
                        <th><?php echo _t("Anteprima"); ?></th>
                        <th><?php echo _t("Ripristina"); ?></th>
                    </tr>
                </thead>
                <?php
                $backups = [];
                $backupDir = $backupDir . '/';
                $files = glob($backupDir . '*.bak'); // o l'estensione corretta dei backup
                foreach ($files as $file) {
                    $backups[] = [
                        'name' => basename($file),
                        'mtime' => filemtime($file),
                    ];
                }
                usort($backups, function($a, $b) {
                    return $b['mtime'] <=> $a['mtime'];
                });
                ?>
                <tbody>
                <?php
				$contents = trim(file_get_contents("/etc/timezone", false, null ));
				date_default_timezone_set($contents);
                $visible_limit = 5;
                $total = count($backups);
                foreach ($backups as $index => $b): 
                    $is_hidden = $index >= $visible_limit;
                ?>
                    <tr class="<?= $is_hidden ? 'hidden-backup' : '' ?>">
                        <td><?= date('d/m/Y H:i:s', $b['mtime']) ?></td>
                        <td>
                            <a href="?page=restore&preview=<?= urlencode($b['name']) ?>" target="_blank" rel="noopener noreferrer">
                                <i data-feather="external-link" title="<?php echo _t("Apri backup"); ?>"></i>
                            </a>
                        </td>
                        <td>

                            <button type="button" class="backupRestoreFunction" style="background:none; border:none; cursor:pointer;" data-backup="<?= htmlspecialchars($b['name'], ENT_QUOTES) ?>">
                                <i data-feather="repeat"></i></span>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
    <?php include 'includes/message.php'; ?>
    <?php if ($total > $visible_limit): ?>
        <p><button type="button" id="toggleButton" class=" icon-button tooltip-cell" style="background:none; border:none; cursor:pointer;">
                <span class="tooltip-target"><i data-feather="chevrons-down" style="width: 40px; height:40px;"></i></span>
                <span class="tooltip-bubble"><?php echo _t("Mostra tutti i backup"); ?></span>
        </button></p>
        
    <?php endif; ?>
    <button id="toTopBtn"><i data-feather="arrow-up-circle"></i></button>
</div>