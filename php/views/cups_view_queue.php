


 <div id="queue-printer" class="content">
    <h1 class="page-title"><?php echo _t("Coda delle Stampanti"); ?></h1>
	<div class="title-separator" style="display:block !important;"></div>
		
		<div class="filter-actions">
		<form method="GET" action="manager.php#resultsTable" id="filterForm">
			<input type="hidden" name="page" value="cups" />
			<select name="filter" id="filterSelect" onchange="document.getElementById('filterForm').submit()">
				<option value="all" <?= ($filter ?? 'all') === 'all' ? 'selected' : '' ?>><?php echo _t("Tutti"); ?></option>
				<option value="not-completed" <?= ($filter ?? '') === 'not-completed' ? 'selected' : '' ?>><?php echo _t("In corso"); ?></option>
				<option value="completed" <?= ($filter ?? '') === 'completed' ? 'selected' : '' ?>><?php echo _t("Completati"); ?></option>
			</select>
		</form>

		<form method="POST" action="" id="clearQueueForm">
			<input type="hidden" name="form_action" value="queue-printer">
			<input type="hidden" name="queue_action" value="clearAllJobs">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token) ?>">

			<button type="submit" class="btn-red"
				title="<?php echo _t('Cancella tutte le code'); ?>"
				onclick="return confirm('<?php echo _t('Sei sicuro di voler cancellare TUTTI i job?'); ?>')">
				<i data-feather="trash"></i> <?php echo _t("Cancella tutte"); ?>
			</button>
		</form>
	</div>

    <table style="margin-top: 1rem;" id="resultsTable">
        <thead>
            <tr>
                <th><?php echo _t("Riprendi"); ?></th>
                <th><?php echo _t("Cancella"); ?></th>
                <th><?php echo _t("Sposta"); ?></th>
                <!-- Colonne ordinabili: indici ripristinati come in origine -->
                <th onclick="sortTable(3, 'resultsTable')" style="cursor: pointer;"><?php echo _t("ID coda"); ?> &#x25B2;&#x25BC;</th>
                <th onclick="sortTable(4, 'resultsTable')" style="cursor: pointer;"><?php echo _t("Stato"); ?> &#x25B2;&#x25BC;</th>
                <th onclick="sortTable(5, 'resultsTable')" style="cursor: pointer;"><?php echo _t("Alert"); ?> &#x25B2;&#x25BC;</th>
                <th onclick="sortTable(6, 'resultsTable')" style="cursor: pointer;"><?php echo _t("Utente"); ?> &#x25B2;&#x25BC;</th>
                <th onclick="sortTable(7, 'resultsTable')" style="cursor: pointer;"><?php echo _t("Data / Ora"); ?> &#x25B2;&#x25BC;</th>
                <th onclick="sortTable(8, 'resultsTable')" style="cursor: pointer;"><?php echo _t("Grandezza"); ?> &#x25B2;&#x25BC;</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($jobs) && is_array($jobs)): ?>
                <?php $visible_limit = 5;
                      $total_jobs = count($jobs);
                      foreach ($jobs as $index => $job):
                          $is_hidden = $index >= $visible_limit; 
                          $csrf_token = $csrf_token ?? ''; 
                          $data['printers'] = $data['printers'] ?? [];
                    ?>
                    <tr class="<?php echo $is_hidden ? 'hidden-queue' : '' ?>">
                            <td>
                                <form method="POST" action="" class="form-no-shadow">
                                    <input type="hidden" name="form_action" value="queue-printer">
                                    <input type="hidden" name="queue_action" value="resumeJob">
                                    <input type="hidden" name="resume_job_id" value="<?php echo htmlspecialchars($job['job_id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token) ?>">

                                    <button type="button" class="RowBtn resume-job-btn" data-job-id="<?php echo htmlspecialchars($job['job_id']) ?>" style="background:none; border:none; cursor:pointer;" title="<?php echo _t('Riprendi job'); ?>">
                                        <i data-feather="play"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="" class="form-no-shadow">
                                    <input type="hidden" name="form_action" value="queue-printer">
                                    <input type="hidden" name="queue_action" value="cancelJob">
                                    <input type="hidden" name="delete_job_id" value="<?php echo htmlspecialchars($job['job_id']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token) ?>">

                                    <button type="button" class="RowBtn delete-job-btn" data-job-id="<?php echo htmlspecialchars($job['job_id']) ?>" style="background:none; border:none; cursor:pointer;" title="<?php echo _t('Cancella job'); ?>">
                                        <i data-feather="trash-2"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="" class="form-no-shadow move-job-form"> 
                                    <input type="hidden" name="form_action" value="queue-printer">
                                    <input type="hidden" name="queue_action" value="moveJob">
                                    <input type="hidden" name="move_job_id" value="<?php echo htmlspecialchars($job['job_id']) ?>">
                                    <input type="hidden" name="printer_name" value="<?php echo htmlspecialchars($job['printer_name']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token) ?>">

                                    <select name="destination_printer" class="destination-printer-select" style="display:none;" required>
                                        <option value=""><?php echo _t("Seleziona Stampante"); ?></option>
                                        <?php foreach ($data['printers'] as $printer): ?>
                                            <?php if ($printer['name'] !== $job['printer_name']): ?>
                                                <option value="<?= htmlspecialchars($printer['name']) ?>">
                                                    <?php echo htmlspecialchars($printer['name']) ?> (<?php echo $printer['status'] ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="button" class="RowBtn move-job-btn" data-job-id="<?php echo htmlspecialchars($job['job_id']) ?>" style="background:none; border:none; cursor:pointer;" title="<?php echo _t('Sposta job'); ?>">
                                        <i data-feather="move"></i>
                                    </button>

                                    <button type="submit" class="confirm-move-btn" style="display:none;">
                                        <?php echo _t("Conferma"); ?>
                                    </button>
                                </form>
                            </td>
                            <td> <?php echo htmlspecialchars($job['job_id']) ?>
                            </td>
                            <td>
                                <?php if (!empty($job['status'])): ?>
                                    <span style="color: <?= $job['status'] === 'completed' ? 'green' : 'orange'; ?>;">
                                        <?php echo htmlspecialchars($job['status']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: grey;">--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($job['alert'])): ?>
                                    <span style="color: red;"><?php echo _t(htmlspecialchars($job['alert'])); ?></span>
                                <?php else: ?>
                                    <span style="color: grey;"><?php echo _t("Nessun avviso"); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($job['user']); ?></td>
                            <td><?php echo htmlspecialchars($job['datetime']); ?></td>
                            <td><?php echo htmlspecialchars($job['size']); ?></td>
                        </tr>
                    <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <!-- Aggiorna colspan per corrispondere al nuovo numero di colonne (9) -->
                    <td colspan="9" style="text-align: center; padding: 1rem;">
                        <?php echo _t("Nessun lavoro nella coda corrispondente ai filtri selezionati."); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (($total_jobs ?? 0) > $visible_limit): ?>
        <p>
            <button type="button" id="toggleQueueButton" class="icon-button tooltip-cell" style="background:none; border:none; cursor:pointer;">
                <span class="tooltip-target"><i data-feather="chevrons-down" style="width: 40px; height:40px;"></i></span>
                <span class="tooltip-bubble"><?php echo _t("Mostra tutte le code"); ?></span>
            </button>
        </p>
    <?php endif; ?>

</div>