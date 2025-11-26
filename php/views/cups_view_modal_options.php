<div id="optionsPrinterModal" class="modal">
    <div class="modal-content"> 
        <span class="close-modal"><button class="RowBtn" id="closeOptionsModal" style="background:none; border:none; cursor:pointer;"><i data-feather="x-circle"></i></button></span>
        <div class="modal-header">
            <h2 class="modal-title"></h2>
        </div>
        <form name="setting-options-printer" method="POST" action="" autocomplete="off">
            <input type="hidden" name="options_printer_name" />
            <input type="hidden" name="form_action" value="setting-options-printer" />
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <!-- opzioni generate in cups_view_js -->
            <div id="optionsContainer"></div>
            
            <button type="submit" id="submitBtn" class="confirm-submit icon-button tooltip-cell" style="background:none; border:none; cursor:pointer; padding: 1rem;">
                <span class="tooltip-target"><i data-feather="save"></i></span>
                <span class="tooltip-bubble"><?php echo _t("Salva modifiche"); ?></span>
            </button>

        </form>


    </div>

</div>