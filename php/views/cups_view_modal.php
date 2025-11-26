<div id="printerModal" class="modal">
  <div class="modal-content">
    <span class="close-modal"><button class="RowBtn" style="background:none; border:none; cursor:pointer;"><i data-feather="x-circle"></i></button></span>
    <h2 id="modalTitle" class="modal-title"><?php echo _t("Aggiungi Stampante"); ?></h2>
    <form id="printerForm" method="POST" enctype="multipart/form-data" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="form_action" id="formAction" value="add-printer">
        <input type="hidden" id="ppdPath" name="ppd_path" value="">

        <label for="printerName"><?php echo _t("Nome stampante"); ?>:</label>
        <input type="text" id="printerName" name="printer_name" required>

        <label for="connectionType"><?php echo _t("Tipo di connessione"); ?>:</label>
        <input type="text" id="connectionType" name="connection_type" required readonly>

        <label for="connectionUrl"><?php echo _t("URL di connessione"); ?>:</label>
        <input type="url" id="connectionUrl" name="connection_url">
        <small id="urlExampleBox" class="url-example-box"></small>

        <label for="description"><?php echo _t("Descrizione"); ?>:</label>
        <input type="text" id="description" name="description">

        <label for="location"><?php echo _t("Location"); ?>:</label>
        <input type="text" id="location" name="location">




        <!-- Produttore Attuale -->
        <label id="labelCurrentManufacturer" for="currentManufacturer"><?php echo _t("Produttore attuale"); ?>:</label>
        <input type="text" id="currentManufacturer" name="current_manufacturer_display" disabled>
        <input type="hidden" id="currentManufacturerHidden" name="current_manufacturer">

        <!-- Modello Attuale -->
        <label id="labelCurrentModel" for="currentModel"><?php echo _t("Modello attuale"); ?>:</label>
        <input type="text" id="currentModel" name="current_model_display" disabled>
        <input type="hidden" id="currentModelHidden" name="current_model">




        <label for="manufacturer"><?php echo _t("Produttore"); ?>:</label>
        <select id="manufacturer" name="manufacturer" required>
            <option value="">-- <?php echo _t("Seleziona produttore"); ?> --</option>
            <?php
            foreach ($dataDrivers['manufacturers'] as $manufacturer => $models) {
                // Usa il nome del produttore come value e testo dell'opzione
                echo '<option value="' . htmlspecialchars($manufacturer) . '">' . htmlspecialchars($manufacturer) . '</option>';
            }
            ?>
        </select>

        <label for="modello"><?php echo _t("Modello"); ?>:</label>
        <select id="modello" name="modello" required>
        <option value="">-- <?php echo _t("Seleziona modello"); ?> --</option>
        <!-- Popolato dinamicamente in base al produttore -->
        </select>


        <div id="brotherModelsWrapper" style="display: none;">
		<?php if (php_uname('m')=="x86") { ?>
            
            
            <label for="brotherModelsSelect"><?php echo _t("Installa un modello Brother (Lista aggiornata al 2025)"); ?>:</label>
            <select id="brotherModelsSelect" name="brotherModelsSelect">
                <option value="">-- <?php echo _t("Seleziona un modello da installare"); ?> --</option>
                <?php foreach ($brotherModelsArray as $value => $label): ?>
                    <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="brotherModel" name="brother_model" placeholder="<?php echo _t("Incolla qui il modello (es: HL-L2390DW)"); ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                <button id="installBrotherModelBtn" type="button" class="general-button"><?php echo _t("Installa modello Brother"); ?></button>

		<?php } else {
			echo '<span id="brotherModelsSelect"><input type="hidden" id="brotherModelInput" name="brotherModelInput"><input type="hidden" id="installBrotherModelBtn" name="installBrotherModelBtn"></span>';
		} ?>

        </div>
        
        
        <button type="submit" id="submitBtn" class="confirm-submit icon-button tooltip-cell" style="background:none; border:none; cursor:pointer; padding: 1rem;">
            <span class="tooltip-target"><i data-feather="save"></i></span>
            <span class="tooltip-bubble"><?php echo _t("Salva modifiche"); ?></span>
        </button>

    </form>
  </div>
</div>