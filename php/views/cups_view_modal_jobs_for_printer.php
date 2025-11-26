<div id="queueModal" class="modal" style="display:none;">
    <div class="modal-content">
        <button id="deleteAllJobsBtn" class="btn-get-queue-by-printer" style="width: auto;"><?php echo _t("Elimina tutte le code"); ?></button>
        <span id="closeModal" class="close-modal"><button class="RowBtn" style="background:none; border:none; cursor:pointer;"><i data-feather="x-circle"></i></button></span>
        <h2 class="modal-title"><?php echo _t("Code della Stampante"); ?>: <span id="queueModalTitle"></span></h2>
        <table id="modalQueueTable">
            <thead>
                <tr>
                <th><?php echo _t("Rank"); ?></th>
                <th><?php echo _t("Utente"); ?></th>
                <th><?php echo _t("ID"); ?></th>
                <th><?php echo _t("Nome file"); ?></th>
                <th><?php echo _t("Dimensioni"); ?></th>
                </tr>
            </thead>
            <tbody>
                <!-- Job caricati dinamicamente qui -->
            </tbody>
        </table>
    </div>

</div>

