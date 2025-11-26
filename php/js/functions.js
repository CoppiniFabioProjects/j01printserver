document.addEventListener('DOMContentLoaded', () => {
    feather.replace();


	const logo = document.querySelector('.logo-img');
	if (logo) {
	  logo.classList.add('animate-on-load');

	  // Rimuove animazione dopo la fine così :hover torna funzionante
	  logo.addEventListener('animationend', () => {
		logo.classList.remove('animate-on-load');
	  });
	}

	// device view filtra per tipo email, stampante o fax e Se filtro è "all", mostro solo righe con azienda valorizzata e mostro la colonna azienda. Se filtro è tipo specifico, mostro solo righe di quel tipo e nascondo la colonna azienda.
	const filterTipo = document.getElementById('filterTipo');
	if (filterTipo) {
		filterTipo.addEventListener('change', function() {
			const selected = this.value; // valore selezionato nel filtro (es: 'all', '2', '3', '4')
			const rows = document.querySelectorAll('table tbody tr'); // tutte le righe della tabella
			const aziendaCols = document.querySelectorAll('.col-azienda'); // tutte le celle della colonna azienda
			const aziendaHeader = document.querySelector('th.col-azienda'); // intestazione della colonna azienda
			const azioniCols = document.querySelectorAll('.col-azioni, td.printer-icon-cell'); // tutte le celle della colonna azioni e celle icona stampante
			const azioniHeader = document.querySelector('th.col-azioni'); // intestazione della colonna azioni

			if (selected === 'all') {
				// Caso filtro "Tutti"
				let anyAzienda = false; // Flag per determinare se ci sono righe con azienda non vuota
				let showAzioniCol = false; // Flag per determinare se mostrare la colonna Azioni

				// Ciclo tutte le righe per vedere se c'è almeno una azienda non vuota e se ci sono stampanti
				rows.forEach(row => {
					const azienda = row.getAttribute('data-azienda').trim(); // ottengo il valore azienda della riga
					const tipo = row.getAttribute('data-tipo'); // ottengo il tipo dispositivo della riga
					
					if (azienda !== '') {
						anyAzienda = true; // Se c'è almeno una riga con azienda non vuota, setto il flag
					}

					if (tipo === '2') { // Se il tipo è "Stampante" (2), mostriamo la colonna Azioni
						showAzioniCol = true;
					}
				});

				// Mostra/Nascondi la colonna azienda
				if (anyAzienda) {
					aziendaCols.forEach(td => td.style.display = ''); // mostra tutte le celle azienda
					if (aziendaHeader) aziendaHeader.style.display = ''; // mostra l'intestazione azienda
				} else {
					aziendaCols.forEach(td => td.style.display = 'none'); // nascondi tutte le celle azienda
					if (aziendaHeader) aziendaHeader.style.display = 'none'; // nascondi l'intestazione azienda
				}

				// Mostra/Nascondi la colonna azioni
				if (showAzioniCol) {
					azioniCols.forEach(td => td.style.display = ''); // mostra tutte le celle azioni
					if (azioniHeader) azioniHeader.style.display = ''; // mostra l'intestazione azioni
				} else {
					azioniCols.forEach(td => td.style.display = 'none'); // nascondi tutte le celle azioni
					if (azioniHeader) azioniHeader.style.display = 'none'; // nascondi l'intestazione azioni
				}

				// Ciclo tutte le righe e mostro tutte
				rows.forEach(row => {
					row.style.display = ''; // Mostra tutte le righe
				});

			} else {
				// Caso filtro tipo specifico (es: 2, 3, 4)

				let showAzioniCol = false; // Flag per determinare se mostrare la colonna Azioni

				// Nascondi la colonna azienda (header e celle)
				aziendaCols.forEach(td => td.style.display = 'none');
				if (aziendaHeader) aziendaHeader.style.display = 'none';

				// Ciclo tutte le righe e mostro solo quelle che corrispondono al tipo selezionato
				rows.forEach(row => {
					const tipo = row.getAttribute('data-tipo'); // prendo valore tipo dalla riga
					
					if (tipo === selected) {
						row.style.display = ''; // Mostro la riga solo se il tipo corrisponde

						if (tipo === '2') { // Se il tipo è "Stampante" (2), mostriamo la colonna Azioni
							showAzioniCol = true;
						}
					} else {
						row.style.display = 'none'; // Nascondo la riga se il tipo non corrisponde
					}
				});

				// Mostra/Nascondi la colonna azioni
				if (showAzioniCol) {
					azioniCols.forEach(td => td.style.display = ''); // mostra tutte le celle azioni
					if (azioniHeader) azioniHeader.style.display = ''; // mostra l'intestazione azioni
				} else {
					azioniCols.forEach(td => td.style.display = 'none'); // nascondi tutte le celle azioni
					if (azioniHeader) azioniHeader.style.display = 'none'; // nascondi l'intestazione azioni
				}
			}
		});
	}

	// Toggle backup rows
	const toggleBtn = document.getElementById('toggleButton');
	const hiddenRows = document.querySelectorAll('.hidden-backup');
	let expanded = false;

	if (toggleBtn) {
		toggleBtn.addEventListener('click', () => {
			expanded = !expanded;

			// Mostra o nascondi le righe
			hiddenRows.forEach(row => {
				row.style.display = expanded ? 'table-row' : 'none';
			});

			// Sostituisci completamente l'interno del pulsante
			if (expanded) {
				toggleBtn.innerHTML = `
					<span class="tooltip-target">
						<i data-feather="chevrons-up" style="width: 40px; height:40px;"></i>
					</span>
					<span class="tooltip-bubble" id="1"></span>
				`;
				
				feather.replace();
				
				// traduzione: Chiama la traduzione subito dopo aver aggiornato il DOM
				_t("Nascondi backup vecchi", function(traduzione) {
					const el = document.getElementById("1");
					if(el) {
					  el.textContent = traduzione;
					}
				}); 
			} else {
				toggleBtn.innerHTML = `
					<span class="tooltip-target">
						<i data-feather="chevrons-down" style="width: 40px; height:40px;"></i>
					</span>
					<span class="tooltip-bubble" id="2"></span>
				`;
				feather.replace();
				
				// traduzione: Chiama la traduzione subito dopo aver aggiornato il DOM
				_t("Mostra tutti i backup", function(traduzione) {
					const el = document.getElementById("2");
					if(el) {
					  el.textContent = traduzione;
					}
				});
			}
			
			
		});
	}

	// Nascondi inizialmente
	hiddenRows.forEach(row => row.style.display = 'none');

	// Trova tutte le icone con classe backupRestoreFunction
	const icons = document.querySelectorAll('.backupRestoreFunction');

	icons.forEach(function (icon) {
		icon.addEventListener('click', function () {
			const backupName = this.dataset.backup;
			
			_t("Confermare ripristino del backup?", function(traduzione) {		
				if (confirm(traduzione)) {
					const form = document.createElement('form');
					form.method = 'POST';
					form.action = '?page=restore';  // o URL esatto

					// backup name
					const input = document.createElement('input');
					input.type = 'hidden';
					input.name = 'restore_backup';
					input.value = backupName;
					form.appendChild(input);

					// csrf token
					const csrfToken = document.querySelector('input[name="csrf_token"]').value;
					const tokenInput = document.createElement('input');
					tokenInput.type = 'hidden';
					tokenInput.name = 'csrf_token';
					tokenInput.value = csrfToken;
					form.appendChild(tokenInput);

					document.body.appendChild(form);
					form.submit();
								
					_t("Backup ripristinato con successo!", function(traduzione) {	
						alert(traduzione);
					}); 
				}
			}); 
		});
	});

	// Add / Remove Application Server Notifica rows in table 
	// URL control 
	const notificaForm = document.getElementById('notificaForm');
	if (notificaForm) {
		notificaForm.addEventListener('submit', function(e) {
			const inputs = document.querySelectorAll('input[name="applicationserver_notifica[]"]');
			for (let input of inputs) {
				const value = input.value.trim();
				if (value === '' || !/^https?:\/\/.+/.test(value)) {
				 _t("Inserisci un URL valido (es. http://ip:port/j01smb_p/)", function(traduzione) {
					 alert(traduzione);
					 }); 
					
					e.preventDefault(); // blocca l'invio del form
					e.stopImmediatePropagation(); // Blocca anche altri listener dello stesso evento
					return false;
				}
			}
		});
	}

	const tableBody = document.querySelector('#notificaTable tbody');
	if (tableBody) {
		const addRowBtn = document.getElementById('addRowBtn');
		if (addRowBtn) {
			addRowBtn.addEventListener('click', () => {
				const newRow = document.createElement('tr');
				newRow.innerHTML = `
					<td><i data-feather="trash-2" class="removeRowBtn" title="Rimuovi notifica" style="cursor: pointer;"></i></td>
					<td><input type="url" name="applicationserver_notifica[]" required placeholder="http://ip:port/j01smb_p/" style="width: 100%;" /></td>
				`;
				tableBody.appendChild(newRow);
				feather.replace();
			});
		}

		tableBody.addEventListener('click', (e) => {
			const btn = e.target.closest('.removeRowBtn');
			if (btn) {
				_t("Sei sicuro di voler rimuovere questa notifica?", function(traduzione) {	
					if (confirm(traduzione)) {
						btn.closest('tr').remove();
					}
				}); 
			}
		});
	}

	// Refresh button handler
	const refreshBtn = document.getElementById('refreshPageBtn');
	if (refreshBtn) {
		refreshBtn.addEventListener('click', () => {
			window.location.href = '?page=notifica&refresh=1'; // forzo il GET, non POST così non effettuo il backup nell'aggiorna pagina
		});
	}

	// Pulsante "Torna su"
	const toTopBtn = document.getElementById("toTopBtn");

	if (toTopBtn) {
		window.addEventListener('scroll', function() {
		if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
			toTopBtn.style.display = "block";
		} else {
			toTopBtn.style.display = "none";
		}
		});

		toTopBtn.addEventListener("click", function() {
			window.scrollTo({
			top: 0,
			behavior: "smooth"
			});
		});
	}

	const formDevice = document.getElementById('deviceForm');
	const selectAllCheckbox = document.getElementById('selectAll');

	// Gestione Select All checkbox
	if(selectAllCheckbox) {
		selectAllCheckbox.addEventListener('change', function() {
			const checkboxes = formDevice.querySelectorAll('input[type="checkbox"][name="selectedDevices[]"]');
			checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
		});
	}


	// GESTIONE SELECT MOVE JOB CODA DI STAMPA
	document.querySelectorAll('.move-job-btn').forEach(function (button) {
		button.addEventListener('click', function () {
			const form = button.closest('form');
			const select = form.querySelector('.destination-printer-select');
			const confirmBtn = form.querySelector('.confirm-move-btn');

			// Mostra select e conferma
			select.style.display = 'inline-block';
			confirmBtn.style.display = 'inline-block';

			// Nascondi pulsante Move
			button.style.display = 'none';

			// Event listener solo per questo select
			select.addEventListener('change', function () {
				if (select.value) {
					confirmBtn.disabled = false;
				} else {
					confirmBtn.disabled = true;
				}
			});

			// Disabilita pulsante finché non viene selezionato qualcosa
			confirmBtn.disabled = true;
		});
	});

    document.addEventListener('submit', (e) => {
		if(loadingModal.style.opacity == '50'){
			loadingModal.style.opacity = '0';
		}else{
			loadingModal.style.display = 'block';
			loadingModal.style.opacity = '1';
			loadingModal.style.visibility = 'visible';
		}
	});
	
	function _t(stringa, callback) {
	  var xhr = new XMLHttpRequest();
	  xhr.open('POST', 'views/traduzioni.php', true);
	  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	  xhr.onreadystatechange = function() {
		if (xhr.readyState === 4) {
		  if (xhr.status === 200) {
			//console.log(callback(xhr));
			callback(xhr.responseText);
		  } else {
			console.error('Error with request');
			callback(stringa);
		  }
		}
	  };

	  var data = 'str_lingua=' + encodeURIComponent(stringa);
	  xhr.send(data);
	}

});