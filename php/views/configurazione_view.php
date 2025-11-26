
<?php include 'includes/header.php'; 

$configContent = file_get_contents($configFile);

$attributes = ['listenPort', 'listenBackLog', 'forcedPollingMillis', 'rinnovaWebConfigMillis', 'serverVersion', 'disabilitaAcrobat'];

$values = [];

foreach ($attributes as $attr) {
    if ($attr === 'disabilitaAcrobat') {
        $pattern = '/<!ATTLIST\s+j01printserver-config\s+' . preg_quote($attr, '/') . '\s+\(([^)]+)\)\s*"([^"]*)"\s*>/';
        // ad esempio: (true | false) "false"
    } else {
        $pattern = '/<!ATTLIST\s+j01printserver-config\s+' . preg_quote($attr, '/') . '\s+CDATA\s+"([^"]*)"\s*>/';
    }

    if (preg_match($pattern, $configContent, $matches)) {
        // per disabilitaAcrobat: $matches[2] è il valore
        // per gli altri: $matches[1] è il valore
        $values[$attr] = ($attr === 'disabilitaAcrobat') ? $matches[2] : $matches[1];
    } else {
        $values[$attr] = null; // non trovato
    }
}

?>
<div class="content">   
    <img src="img/logo.svg" alt="Logo" class="logo-img spin animate-on-load" />
    <h1 class="page-title"><?php echo _t("Modifica Configurazione"); ?></h1>
    <div class="title-separator"></div>        

    <form method="POST" id="source-conf-device-form" action="?page=configurazione">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
        <table>
            <thead>
            <tr><th><?php echo _t("Proprietà"); ?></th><th><?php echo _t("Valore"); ?></th></tr>
        </thead>
            <tbody>
                <tr>
                    <td>
                        <label for="listenPort">
                            <?php echo _t("Listen Port"); ?>:<br>
                            <span class="label-note">(default 8887)</span>
                        </label>
                    </td>
                    <td>
                        <input type="number" id="listenPort" name="listenPort" 
                            value="<?php echo htmlspecialchars($values['listenPort'] ?? ''); ?>" 
                            min="1" max="32765" step="1" />
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="listenBackLog">
                            <?php echo _t("Listen BackLog"); ?>:<br>
                            <span class="label-note">(default 500)</span>
                        </label>
                    </td>
                    <td>
                        <input type="number" id="listenBackLog" name="listenBackLog" 
                            value="<?php echo htmlspecialchars($values['listenBackLog'] ?? ''); ?>" 
                            min="0" max="50000" />
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="forcedPollingMillis">
                            <?php echo _t("Forced Polling Millis"); ?>:<br>
                            <span class="label-note">(default 30000)</span>
                        </label>
                    </td>
                    <td>
                        <input type="number" id="forcedPollingMillis" name="forcedPollingMillis" 
                            value="<?php echo htmlspecialchars($values['forcedPollingMillis'] ?? ''); ?>" 
                            min="0" max="300000" />
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="rinnovaWebConfigMillis">
                            <?php echo _t("Web Config Millis"); ?>:<br>
                            <span class="label-note">(default 30000)</span>
                        </label>
                    </td>
                    <td>
                        <input type="number" id="rinnovaWebConfigMillis" name="rinnovaWebConfigMillis" 
                            value="<?php echo htmlspecialchars($values['rinnovaWebConfigMillis'] ?? ''); ?>" 
                            min="0" max="300000" />
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="serverVersion"><?php echo _t("Server Version"); ?>:</label>
                    </td>
                    <td>
                        <select id="serverVersion" name="serverVersion">
                            <option value="1" <?php if (($values['serverVersion'] ?? '') == "1") echo "selected"; ?>>
                                <?php echo _t("1 - versione classica di PrintServer"); ?>
                            </option>
                            <option value="2" <?php if (($values['serverVersion'] ?? '') == "2") echo "selected"; ?>>
                                <?php echo _t("2 - versione con code di stampa e gestori"); ?> (Java 1.6)
                            </option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td>
                        <label for="disabilitaAcrobat"><?php echo _t("Disabilita Acrobat"); ?>:</label>
                    </td>
                    <td>
                        <select id="disabilitaAcrobat" name="disabilitaAcrobat">
                            <option value="false" <?php if (($values['disabilitaAcrobat'] ?? '') == "false") echo "selected"; ?>>
                                false
                            </option>
                            <option value="true" <?php if (($values['disabilitaAcrobat'] ?? '') == "true") echo "selected"; ?>>
                                true
                            </option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" style="text-align: center;">
                        <button type="submit" class="confirm-submit icon-button tooltip-cell" style="background:none; border:none; cursor:pointer;">
                            <span class="tooltip-target"><i data-feather="save"></i></span>
                            <span class="tooltip-bubble"><?php echo _t("Salva modifiche"); ?></span>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
    <?php include 'includes/message.php'; ?>
    <button id="toTopBtn"><i data-feather="arrow-up-circle"></i></button>
</div>