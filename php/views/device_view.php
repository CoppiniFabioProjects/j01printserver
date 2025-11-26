<?php include 'includes/header.php';
function getTipoDescrizione($tipo) {
    switch ($tipo) {
        case 2: return _t('Stampante');
        case 3: return _t('PEC');
        case 4: return _t('Email');
        default: return _t('Tipo sconosciuto');
    }
}

//ordinamento prima per selezionati ($existingDeviceNames), e poi per tipo, e infine per codice dispositivo, prima di entrare nel foreach della tabella.
function compareDevices($a, $b, $selectedDevices, $aCode = null, $bCode = null) { // <-- 1. CORREZIONE: Aggiunta virgola
    // Usa i codici già trimmati se forniti, altrimenti trimma
    $aCode = $aCode ?? trim($a['MBWS02COD']);
    $bCode = $bCode ?? trim($b['MBWS02COD']);
	
	$aSelected = in_array($aCode, $selectedDevices) ? 0 : 1;
    $bSelected = in_array($bCode, $selectedDevices) ? 0 : 1;

    if ($aSelected !== $bSelected) {
        return $aSelected - $bSelected; // selezionati prima (0), poi non selezionati (1)
    }
    // Se stesso gruppo, ordina per tipo
    if ($a['MBWS02TIP'] !== $b['MBWS02TIP']) {
        return $a['MBWS02TIP'] - $b['MBWS02TIP'];
    }
    // Se stesso tipo, ordina per codice dispositivo
    return strcmp($aCode, $bCode);
}

?><div class="content">
    <img src="img/logo.svg" alt="Logo" class="logo-img spin animate-on-load" />
    <h1 class="page-title"><?php echo _t("Modifica Device List"); ?></h1>
    <div class="title-separator"></div>
    <div class="filtri-box">
        <label for="filterTipo"><?php echo _t("Filtri per tipo"); ?>:</label>
        <select id="filterTipo">
            <option value="all"><?php echo _t("Tutti"); ?></option>
            <option value="2"><?php echo _t("Stampanti (2)"); ?></option>
            <option value="3"><?php echo _t("PEC (3)"); ?></option>
            <option value="4"><?php echo _t("E-mail (4)"); ?></option>
        </select>
    </div>
    <?php include 'includes/message.php'; ?>
    <div class="alert-info" style="margin: 1rem 0 2rem; padding: 10px; background-color: #eef7ff; border-left: 4px solid #2461a3;">
        <i data-feather="info" style="vertical-align: middle; margin-right: 5px;"></i>
        <strong><?php echo _t("Nota"); ?>:</strong><br>
        • <?php echo _t("Per i dispositivi"); ?> <strong><?php echo _t("PEC"); ?></strong> <?php echo _t("ed"); ?> <strong>Email</strong>, <?php echo _t("è sufficiente"); ?> <strong><?php echo _t("impostare la spunta"); ?></strong> <?php echo _t("per aggiungerli"); ?>.<br>
        • <?php echo _t("Per le"); ?> <strong><?php echo _t("Stampanti"); ?></strong>, <?php echo _t("è necessario sia"); ?> <strong><?php echo _t("impostare la spunta"); ?></strong> <?php echo _t("che"); ?> <strong><?php echo _t("selezionare la stampante"); ?></strong> <?php echo _t("per aggiungerla"); ?>.<br>
        • <?php echo _t("Per la"); ?> <strong><?php echo _t("rimozione delle Stampanti"); ?></strong>, <?php echo _t("è sufficiente"); ?> <strong><?php echo _t("togliere la spunta"); ?></strong>.
    </div>

    <div class="db-results">
        <form id="deviceForm" method="POST" action="?page=device">

            <div class="form-row">
                <button type="submit" class="confirm-submit icon-button tooltip-cell" style="background:none; border:none; cursor:pointer;">
                    <span class="tooltip-target"><i data-feather="save"></i></span>
                    <span class="tooltip-bubble"><?php echo _t("Salva modifiche"); ?></span>
                </button>
            </div>

            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />

            <?php if ($dbErrors): ?>
                <p style="color: red; font-weight: bold;">
                    <?php echo _t("ATTENZIONE: Errore ODBC"); ?> - <?= htmlspecialchars($dbErrors); ?>
                </p>

            <?php elseif (!empty($dbData)): ?>
                <table style="box-shadow:none;">
                    <colgroup>
                        <col style="width: 5%;"> <!-- Checkbox -->
                        <col style="width: 5%;"> <!-- ID -->
                        <col style="width: 20%;"> <!-- Descrizione -->
                        <col style="width: 5%;">  <!-- Tipo -->
                        <col style="width: 50%;" class="col-azioni">  <!-- Azioni -->
                        <col style="width: 15%;" class="col-azienda"> <!-- Colonna Azienda -->
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th><?php echo _t("ID"); ?></th>
                            <th><?php echo _t("Descrizione"); ?></th>
                            <th><?php echo _t("Tipo"); ?></th>
                            <th class="col-azioni"><?php echo _t("Azioni"); ?></th>
                            <th class="col-azienda"><?php echo _t("Azienda"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php usort($dbData, function($a, $b) use ($existingDeviceNames) {
							$aCode = trim($a['MBWS02COD']);
                            $bCode = trim($b['MBWS02COD']);
                            return compareDevices($a, $b, $existingDeviceNames, $aCode, $bCode); // <-- 2. CORREZIONE: Passa $aCode e $bCode
                        }); ?>
                        <?php foreach ($dbData as $row):
                            $deviceCode = trim($row['MBWS02COD']);
                            $isChecked = in_array($deviceCode, $existingDeviceNames) ? 'checked' : '';
                            $savedPrinter = $existingDeviceMappings[$deviceCode] ?? null;
                        ?>
                            <tr style="text-align: center;"
                                data-tipo="<?= htmlspecialchars($row['MBWS02TIP']) ?>"
                                data-azienda="<?= htmlspecialchars(trim($row['MBWS02AZI'])) ?>">
                                <td>
                                    <input type="checkbox" name="selectedDevices[]" value="<?= htmlspecialchars($deviceCode) ?>" <?= $isChecked ?>>
                                </td>
                                <td><?= htmlspecialchars($deviceCode) ?></td>
                                <td><?= htmlspecialchars($row['MBWS02DES']) ?></td>
                                <td><?= getTipoDescrizione($row['MBWS02TIP']) ?></td>

                                <?php if ($row['MBWS02TIP'] == 2): ?>
                                    <td class="col-azioni">
                                        <select class="printer-select" name="printer_<?= htmlspecialchars($deviceCode) ?>">
                                            <option value="" <?= empty($savedPrinter) ? 'selected' : '' ?>><?php echo _t("🖨️ Seleziona stampante"); ?></option>

                                            <?php foreach ($cupsPrinters as $printer):
                                                $printerName = $printer['name'];
                                            ?>
                                                <option value="<?= htmlspecialchars($printerName) ?>" <?= $printerName === $savedPrinter ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($printerName) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                <?php else: ?>
                                    <td class="col-azioni"></td>
                                <?php endif; ?>

                                <td class="col-azienda"><?= htmlspecialchars(trim($row['MBWS02AZI'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="form-row">
                    <button type="submit" class="confirm-submit icon-button tooltip-cell" style="background:none; border:none; cursor:pointer;">
                        <span class="tooltip-target"><i data-feather="save"></i></span>
                        <span class="tooltip-bubble"><?php echo _t("Salva modifiche"); ?></span>
                    </button>
                </div>
            <?php else: ?>
                <p style="padding: 1rem;"><?php echo _t("Nessun dispositivo trovato. Prova a cambiare il nome dell'azienda in Datasource"); ?></p>
            <?php endif; ?>
        </form>
    </div>
    <button id="toTopBtn"><i data-feather="arrow-up-circle"></i></button>
</div>