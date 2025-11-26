<style>
    #cancelAllModal .modal-content {
        background-color: #ffffff;
        margin: 0 auto; /* Centrato orizzontalmente */
        padding: 2.5rem;
        border: none;
        width: 90%;
        max-width: 420px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        text-align: center;
        /* Animazione */
        animation: modal-fade-in 0.3s ease-out;
        transform: translateY(0); /* Resetta per animazione */
    }

    #cancelAllModal {
        display: none; 
        position: fixed; 
        z-index: 1001; 
        left: 0; 
        top: 0; 
        width: 100%; 
        height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.5); /* Sfondo più scuro */
        /* Aggiunto padding per spingere il contenuto in giù */
        padding-top: 10vh; 
    }

    #cancelAllModal h2 {
        font-weight: 600;
        margin-top: 1rem;
        margin-bottom: 0.5rem;
        color: #333;
    }

    #cancelAllModal p {
        margin-bottom: 2rem;
        color: #555;
        line-height: 1.6;
        font-size: 0.95rem;
    }
    
    #cancelAllModal .modal-buttons {
        display: flex;
        justify-content: center;
        gap: 0.75rem; /* Spazio tra i pulsanti */
    }

    #cancelAllModal .modal-btn {
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    #cancelAllModal .btn-danger {
        background-color: #d9534f;
        color: white;
    }
    #cancelAllModal .btn-danger:hover {
        background-color: #c9302c;
        /* Effetto "sollevato" */
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(217, 83, 79, 0.3);
    }

    #cancelAllModal .btn-secondary {
        background-color: #f0f0f0;
        color: #555;
        border: 1px solid #ddd;
    }
    #cancelAllModal .btn-secondary:hover {
        background-color: #e9e9e9;
        border-color: #ccc;
        transform: translateY(-2px);
    }

    /* Animazione di comparsa */
    @keyframes modal-fade-in {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
<!-- Modal di conferma cancellazione coda -->
<div id="cancelAllModal" class="modal-overlay">
    <div class="modal-content">
        <i data-feather="alert-triangle" style="width: 48px; height: 48px; color: #d9534f; margin-bottom: 0.5rem; stroke-width: 1.5;"></i>
        <h2 id="modalTitle"><?php echo _t("Conferma cancellazione"); ?></h2>
        <p id="modalText" data-translation-key="Sei sicuro di voler cancellare tutti i job per la stampante"></p>
        
        <form id="cancelAllForm" method="POST" action="" class="form-no-shadow">
            <input type="hidden" name="form_action" value="queue-printer">
            <input type="hidden" name="queue_action" value="cancelAllJobs">
            <input type="hidden" id="modalPrinterNameInput" name="printer_name" value="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            
            <div class="modal-buttons">
                <button type="button" id="modalCancelButton" class="modal-btn btn-secondary"><?php echo _t("Annulla"); ?></button>
                <button type="submit" class="modal-btn btn-danger"><?php echo _t("Sì, cancella tutti"); ?></button>
            </div>
        </form>
    </div>
</div>

<div id="manage-printer" class="content">
    <h1 class="page-title"><?php echo _t("Pannello Di Gestione"); ?></h1>
    <div class="title-separator"></div>
    <div class="div-ricerca">
        <input type="text" id="printerSearch" placeholder="<?php echo _t("Cerca stampante per nome"); ?>...">
        <button class="RowBtn" style="background:none; border:none; cursor:pointer;" onclick="document.getElementById('printerSearch').value=''; document.getElementById('printerSearch').dispatchEvent(new Event('input'));"><i data-feather="delete"></i></button>
    </div>
  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="form_action" value="manage-printer">
    <input type="hidden" name="delete_printer_name" id="delete_printer_name">
    <input type="hidden" name="options_printer_name" id="options_printer_name">
    <table id="managePrinterTable">
      <thead>
        <tr>
          <th style="cursor:pointer;" onclick="sortTable(0, 'managePrinterTable')"><?php echo _t("Abilita/Disabilita"); ?> &#x25B2;&#x25BC;</th>
          <th><?php echo _t("Elimina"); ?></th>
          <th><?php echo _t("Modifica"); ?></th>
          <th><?php echo _t("Imposta Opzioni"); ?></th>
          <th style="cursor:pointer;" onclick="sortTable(4, 'managePrinterTable')"><?php echo _t("Stampante"); ?> &#x25B2;&#x25BC;</th>
          <th><?php echo _t("Stato"); ?></th>
          <th style="cursor:pointer;" onclick="sortTable(6, 'managePrinterTable')"><?php echo _t("Data"); ?> &#x25B2;&#x25BC;</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $visibleLimit = 5;
        $index = 0;
        foreach ($data['printers'] as $printer): 
          $printerName = $printer['name'];
          $status = $printer['status'];
          $enabledSince = $printer['enabled_since'];
          $isHidden = $index >= $visibleLimit;
        ?>
          <tr class="<?= $isHidden ? 'hidden-printer' : '' ?>">
            <td>
              <button type="button"
                      class="RowBtn toggle-status-btn"
                      data-printer-name="<?= htmlspecialchars($printerName) ?>"
                      data-status="<?= ($status === 'enabled' ? 'enabled' : 'disabled') ?>"
                      data-index="<?php echo $index ?>"
                      style="background:none; border:none; cursor:pointer;">
                <span class="tooltip-target">
                  <i name="icon_<?php echo $index ?>" data-feather="<?= $status === 'enabled' ? 'pause-circle' : 'play-circle' ?>"></i>
                </span>
                <span class="tooltip-bubble"><?= $status === 'enabled' ? _t('Disabilita') : _t('Abilita') ?></span>
              </button>
            </td>
            <td>
                <button type="button" class="RowBtn delete-printer-btn" data-printer-name="<?= htmlspecialchars($printerName) ?>" style="background:none; border:none; cursor:pointer;" title="<?php echo _t('Cancella') ?>">
                    <i data-feather="trash-2"></i>
                </button>
            </td>
            <td>
                <button type="button" class="RowBtn edit-printer-btn" data-printer-name="<?= htmlspecialchars($printerName) ?>" style="background:none; border:none; cursor:pointer;" title="<?php echo _t('Modifica') ?>">
                    <i data-feather="edit"></i>
                </button>
            </td>
            <td>
                <button type="button" class="RowBtn options-printer-btn" data-printer-name="<?= htmlspecialchars($printerName) ?>" style="background:none; border:none; cursor:pointer;" title="<?php echo _t('Opzioni') ?>">
                    <i data-feather="sliders"></i>
                </button>
            </td>
            <!-- nome stampante è il trigger per il modal -->
			<td class="cancel-all-trigger" 
				data-printer-name="<?php echo htmlspecialchars($printerName) ?>" 
				style="cursor: pointer; text-decoration: underline; color: black;"
				title="<?php echo _t('Clicca per cancellare tutti i job di '); ?><?php echo htmlspecialchars($printerName) ?>"><?php echo htmlspecialchars($printerName) ?>
			</td>
            <td><div class="status_<?php echo $index ?>"><?php echo _t(htmlspecialchars($status)) ?></div></td>
            <td><?php echo htmlspecialchars($enabledSince) ?></td>
          </tr>
        <?php $index++; endforeach; ?>
      </tbody>
    </table>
  </form>

  <?php if (count($data['printers']) > $visibleLimit): ?>
    
      <button type="button" id="togglePrinterButton" class="icon-button tooltip-cell" style="background:none; border:none; cursor:pointer;">
        <span class="tooltip-target">
          <i data-feather="chevrons-down" style="width: 40px; height:40px;"></i>
        </span>
        <span class="tooltip-bubble"><?php echo _t("Mostra tutte le stampanti"); ?></span>
      </button>
    
    <?php endif; ?>

</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('cancelAllModal');
    const modalText = document.getElementById('modalText');
    const hiddenInput = document.getElementById('modalPrinterNameInput');
    const cancelButton = document.getElementById('modalCancelButton');
    const triggers = document.querySelectorAll('.cancel-all-trigger');

    triggers.forEach(trigger => {
        trigger.addEventListener('click', function(event) {
            const printerName = event.currentTarget.dataset.printerName;
            
            // 1. Prendi la stringa chiave (non tradotta) dal data-attribute
            const translationKey = modalText.dataset.translationKey || "Sei sicuro di voler cancellare tutti i job per la stampante";

            // 2. Chiama la funzione di traduzione asincrona
            _t(translationKey, function(translatedString) {
                
                // 3. Questo codice viene eseguito DOPO che la traduzione è tornata
                // Costruisci la stringa finale
                const fullText = translatedString + " '" + printerName + "'?";
                modalText.textContent = fullText;
                
                // 4. Imposta i valori nascosti e mostra il modale
                hiddenInput.value = printerName;
                modal.style.display = 'block';

                // 5. Assicura che la nuova icona venga renderizzata da Feather
                if (typeof feather !== 'undefined') {
                    feather.replace({ width: '48px', height: '48px', 'stroke-width': 1.5 });
                }
            });
        });
    });

    // Chiudi modal cliccando "Annulla"
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    // Chiudi modal cliccando fuori (sull'overlay)
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
});
</script>