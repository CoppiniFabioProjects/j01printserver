<?php include 'includes/header.php'; ?>
<div class="content">
    <img src="img/logo.svg" alt="Logo" class="logo-img spin animate-on-load" />
    <h1 class="page-title"><?php echo _t("Notifiche App Server"); ?></h1>
    <div class="title-separator"></div>

    <form method="POST" action="?page=notifica" id="notificaForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />

        <table class="fl-table" id="notificaTable" style="box-shadow:none;">
            <thead>
                <tr>
                    <th><?php echo _t("Elimina"); ?></th>
                    <th><?php echo _t("URL Applicazione Server Notifica"); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Assumiamo che $config->{'applicationserver-notifica'} possa essere multiplo o singolo
                $notifiche = $config->{'applicationserver-notifica'};
                if ($notifiche instanceof SimpleXMLElement || is_array($notifiche)) {
                    foreach ($notifiche as $notifica) {
                        $url = (string)$notifica;
                        echo '<tr>';
                        // Colonna Azioni prima
                        echo '<td><button type="button" class="removeRowBtn" title="' . _t("Rimuovi notifica") . '" style="background:none; border:none; cursor:pointer;"><i data-feather="trash-2"></i></button></td>';
                        // Colonna URL dopo
                        echo '<td><input type="url" name="applicationserver_notifica[]" required placeholder="http://ip:port/j01smb_p/" value="' . htmlspecialchars($url) . '" style="width: 100%;" /></td>';
                        echo '</tr>';
                    }
                } else {
                    // singolo valore (stringa)
                    $url = (string)$notifiche;
                    if ($url !== '') {
                        echo '<tr>';
                        echo '<td><input type="url" name="applicationserver_notifica[]" required placeholder="http://ip:port/j01smb_p/" value="' . htmlspecialchars($url) . '" style="width: 100%;" /></td>';
                        $str = _t("Sei sicuro di voler rimuovere questa notifica?");
						echo '<td><button type="button" class="removeRowBtn" onclick="return confirm(\''. $str . '\');"></button></td>';
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
        <div style="background-color: #ffff; display: flex; justify-content: center; gap: 20px; padding: 20px 0;">

            <button type ="button" id="addRowBtn" title="<?php echo _t("Aggiungi notifica"); ?>" style="background:none; border:none; cursor:pointer;">
                <i data-feather="plus-circle"></i>
            </button>
            <button type ="button" id="refreshPageBtn" title="<?php echo _t("Aggiorna pagina"); ?>" style="background:none; border:none; cursor:pointer;">
                <i data-feather="refresh-cw"></i>
            </button>

            <button type="submit" class="confirm-submit icon-button tooltip-cell" style="background:none; border:none; cursor:pointer;">
                <span class="tooltip-target"><i data-feather="save"></i></span>
                <span class="tooltip-bubble"><?php echo _t("Salva modifiche"); ?></span>
            </button>


        </div>
    </form>

    <?php include 'includes/message.php'; ?>
    <button id="toTopBtn"><i data-feather="arrow-up-circle"></i></button>
</div>
