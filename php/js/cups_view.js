// --- Riferimenti agli Elementi del DOM ---
const loadingModal = document.getElementById("loadingModal");
const selectPrinterForm = document.getElementById('selectPrinterForm');
const modal = document.getElementById('printerModal');
const form = document.getElementById('printerForm');
const formActionInput = document.getElementById('formAction');
const modalTitle = document.getElementById('modalTitle');
const closeModalBtn = document.querySelector('.close-modal');
const manufacturerSelect = document.getElementById('manufacturer');
const modelSelect = document.getElementById('modello');
const ppdPathInput = document.getElementById('ppdPath');
const brotherModelsSelect = document.getElementById('brotherModelsSelect');
const brotherModelInput = document.getElementById('brotherModel');

// --- Funzione di Traduzione ---

/**
 * Richiede una traduzione per una data stringa dal server.
 * @param {string} stringa La stringa da tradurre.
 * @param {function(string): void} callback La funzione da eseguire con la stringa tradotta.
 */
function _t(stringa, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'views/traduzioni.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                callback(xhr.responseText); // Esegue la callback con la traduzione
            } else {
                console.error('Errore con la richiesta di traduzione');
                callback(stringa); // In caso di errore, restituisce il testo originale
            }
        }
    };

    var data = 'str_lingua=' + encodeURIComponent(stringa);
    xhr.send(data);
}

// --- Gestori di Eventi (Event Listeners) ---

// Aggiorna il menu a tendina dei modelli quando viene selezionato un produttore
manufacturerSelect.addEventListener('change', function() {
    const selectedManufacturer = this.value;
    console.log('Selected Manufacturer:', selectedManufacturer); // Controlla il valore selezionato
	loadModels(selectedManufacturer);
});

manufacturerSelect.addEventListener('click', function() {
    const selectedManufacturer = this.value;
    console.log('Selected Manufacturer:', selectedManufacturer); // Controlla il valore selezionato
	loadModels(selectedManufacturer);
});

function loadModels(selectedManufacturer) {
	_t("Seleziona modello", function(translatedText) {
        console.log('Translated Text:', translatedText); // Controlla la traduzione
        modelSelect.innerHTML = `<option value="">-- ${translatedText} --</option>`;

        // Ora popoliamo il menu dei modelli
        ppdPathInput.value = '';
        const brotherWrapper = document.getElementById('brotherModelsWrapper');
        brotherWrapper.style.display = 'none';

        if (selectedManufacturer && manufacturersModels[selectedManufacturer]) {
            const uniqueModels = [...new Set(manufacturersModels[selectedManufacturer])];
            console.log('Available Models:', uniqueModels); // Controlla i modelli
            uniqueModels.forEach(model => {
                const option = document.createElement('option');
                option.value = model;
                option.textContent = model;
                modelSelect.appendChild(option);
            });
        }

        if (selectedManufacturer === 'Brother' && modelSelect.value === '') {
            brotherWrapper.style.display = 'block';
        }
	});		
}

// Aggiorna l'input del modello Brother quando un modello viene selezionato dal menu a tendina
brotherModelsSelect.addEventListener('change', function() {
    brotherModelInput.value = this.value;
});
brotherModelsSelect.addEventListener('click', function() {
    brotherModelInput.value = this.value;
});

// Aggiorna il percorso PPD e la visibilità del modello Brother quando viene selezionato un modello
modelSelect.addEventListener('change', function() {
    const selectedManufacturer = manufacturerSelect.value;
    const selectedModel = this.value;
    const brotherWrapper = document.getElementById('brotherModelsWrapper');
    brotherWrapper.style.display = 'none';

    if (selectedManufacturer && selectedModel) {
        const key = selectedManufacturer + ' ' + selectedModel;
        ppdPathInput.value = driversPaths[key] || '';
    } else {
        ppdPathInput.value = '';
    }

    if (selectedManufacturer === 'Brother' && selectedModel === '') {
        brotherWrapper.style.display = 'block';
    }
});
modelSelect.addEventListener('click', function() {
    const selectedManufacturer = manufacturerSelect.value;
    const selectedModel = this.value;
    const brotherWrapper = document.getElementById('brotherModelsWrapper');
    brotherWrapper.style.display = 'none';

    if (selectedManufacturer && selectedModel) {
        const key = selectedManufacturer + ' ' + selectedModel;
        ppdPathInput.value = driversPaths[key] || '';
    } else {
        ppdPathInput.value = '';
    }

    if (selectedManufacturer === 'Brother' && selectedModel === '') {
        brotherWrapper.style.display = 'block';
    }
});
// Apre la modale "Aggiungi Stampante"
document.getElementById('continueBtn').addEventListener('click', function() {
    const scelta = selectPrinterForm.printer.value;

    if (!scelta) {
        _t("Seleziona una stampante", function(translatedText) {
            alert(translatedText);
        });
        return;
    }

    apriModalAggiungiConNome(scelta);
    modal.style.display = 'block';
});

// --- Logica Modale e Form ---

const prefixes = {
    "Internet Printing Protocol (ipp)": 'ipp://',
    "Internet Printing Protocol (ipps)": 'ipps://',
    "Internet Printing Protocol (http)": 'http://',
    "Internet Printing Protocol (https)": 'https://',
    "AppSocket/HP JetDirect": 'socket://',
    "LPD/LPR Host": 'lpd://'
};

const urlExamples = {
    "Internet Printing Protocol (ipp)": "ipp://hostname/ipp/ o ipp://hostname/ipp/port1",
    "Internet Printing Protocol (ipps)": "ipps://hostname/ipp/ o ipps://hostname/ipp/port1",
    "Internet Printing Protocol (http)": "http://hostname:631/ipp/ o http://hostname/ipp/port1",
    "Internet Printing Protocol (https)": "https://hostname:631/ipp/ o https://hostname/ipp/port1",
    "AppSocket/HP JetDirect": "socket://hostname o socket://hostname:9100",
    "LPD/LPR Host": "lpd://hostname/queue",
};

/**
 * Apre e configura la modale per aggiungere una nuova stampante.
 * @param {string} tipoConnessione Il tipo di connessione per la nuova stampante.
 */
function apriModalAggiungiConNome(tipoConnessione) {
    form.reset();
    formActionInput.value = 'add-printer';
    _t("Aggiungi Stampante", function(translatedText) {
        modalTitle.textContent = translatedText;
    });

    form.connection_type.value = tipoConnessione;
    form.printer_name.value = '';
	form.printer_name.readOnly = false;

    const prefix = prefixes[tipoConnessione] || '';
    const example = urlExamples[tipoConnessione] || '';
    const urlField = document.getElementById('connectionUrl');
    const urlExampleBox = document.getElementById('urlExampleBox');
    const urlLabel = urlField.previousElementSibling;

    if (tipoConnessione === "CUPS-PDF (Virtual PDF Printer)" || tipoConnessione === "Backend Error Handler") {
        urlField.style.display = 'none';
        urlExampleBox.style.display = 'none';
        urlLabel.style.display = 'none';
        urlField.required = false;
        urlField.value = '';
    } else {
        urlField.style.display = 'block';
        urlExampleBox.style.display = 'block';
        urlLabel.style.display = 'block';
        urlField.required = true;
        urlField.value = prefix;
        urlField.placeholder = example ? "Es: " + example : "";
        urlExampleBox.textContent = example ? "Es: " + example : "";
    }
 
    // Nasconde i campi del produttore/modello corrente per le nuove stampanti
    document.getElementById('labelCurrentManufacturer').style.display = 'none';
    document.getElementById('currentManufacturer').style.display = 'none';
    document.getElementById('currentManufacturerHidden').style.display = 'none';
    document.getElementById('labelCurrentModel').style.display = 'none';
    document.getElementById('currentModel').style.display = 'none';
    document.getElementById('currentModelHidden').style.display = 'none';

    // Pulisce i valori
    document.getElementById('currentManufacturer').value = '';
    document.getElementById('currentManufacturerHidden').value = '';
    document.getElementById('currentModel').value = '';
    document.getElementById('currentModelHidden').value = '';
}

// Chiude la modale della stampante
closeModalBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

// Valida il form della stampante all'invio
form.addEventListener('submit', (e) => {
    loadingModal.style.display = 'block';
    loadingModal.style.opacity = '1';
    loadingModal.style.visibility = 'visible';

    const name = form.printer_name.value;
    const invalidChars = /[\/#\s]/;
    if (invalidChars.test(name)) {
        e.preventDefault();
        loadingModal.style.display = 'none';
        
        _t("Il nome stampante non può contenere /, # o spazi. Aggiunta la stampante senza spazi.", function(translatedText) {
            alert(translatedText);
            form.printer_name.focus();
            location.reload();
        });
        return false;
    }

    const connectionUrl = form.connection_url.value.trim();
    const connectionType = form.connection_type.value;
    const expectedPrefix = prefixes[connectionType] || '';

    if (expectedPrefix && !connectionUrl.startsWith(expectedPrefix)) {
        e.preventDefault();
        loadingModal.style.display = 'none';
        
        _t("L'URL deve iniziare con %s per il tipo di connessione selezionato.", function(translatedText) {
             alert(translatedText.replace('%s', expectedPrefix));
             form.connection_url.focus();
             location.reload();
        });
        return;
    }
});

// Installa il driver della stampante Brother
document.getElementById('installBrotherModelBtn').addEventListener('click', function() {
    const selectedModel = brotherModelsSelect.value;

    if (!selectedModel) {
        _t("Seleziona un modello Brother da installare.", function(translatedText) {
            alert(translatedText);
        });
        return;
    }

    const sanitizedModel = selectedModel.trim().replace(/\s+/g, '-');
    brotherModelInput.value = sanitizedModel;

    loadingModal.style.display = 'block';
    const MIN_LOADING_TIME = 10000;
    const startTime = Date.now();

    fetch('../actions/installBrotherModel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `model=${encodeURIComponent(sanitizedModel)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => response.text())
        .then(text => {
            const timeElapsed = Date.now() - startTime;
            const delay = Math.max(0, MIN_LOADING_TIME - timeElapsed);

            setTimeout(() => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        _t("Installazione completata con successo!", t => alert(t));
                    } else {
                        _t("Errore durante l'installazione:\n", function(errorMsg) {
                            _t("Errore sconosciuto.", function(unknownError) {
                                alert(errorMsg + (data.error || unknownError));
                            });
                        });
                    }
                } catch (e) {
                    _t("Risposta non valida JSON", t => alert(t + '\n' + text));
                } finally {
                    location.reload();
                }
            }, delay);
        })
        .catch(error => {
            setTimeout(() => {
                alert('Errore di rete o server: ' + error);
                location.reload();
            }, MIN_LOADING_TIME);
        });
});

// --- Azioni Stampante (Elimina, Modifica, Opzioni) ---

// Gestisce l'eliminazione della stampante
document.querySelectorAll('.delete-printer-btn').forEach(button => {
    button.addEventListener('click', function() {
        const printerName = this.getAttribute('data-printer-name');

        if (!printerName) {
            _t("Nome stampante mancante.", t => alert(t));
            return;
        }

        _t("Eliminare la stampante %s?", function(translatedText) {
            if (confirm(translatedText.replace('%s', printerName))) {
                const form = button.closest('form');
                form.querySelector('input[name="delete_printer_name"]').value = printerName;
                form.querySelector('input[name="form_action"]').value = 'delete-printer';

                loadingModal.style.display = "block";
                setTimeout(() => form.submit(), 100);
            }
        });
    });
});

// Gestisce l'apertura della modale di modifica stampante
document.querySelectorAll('.edit-printer-btn').forEach(button => {
    button.addEventListener('click', function() {
        const printerforName = this.getAttribute('data-printer-name');
        if (!printerforName) {
            _t("Nome stampante mancante.", t => alert(t));
            return;
        }

        _t("Modifica Stampante", function(translatedText) {
            modalTitle.textContent = translatedText;
        });
        formActionInput.value = 'edit-printer';

        fetch(`../actions/get_printer.php?printer_name=${encodeURIComponent(printerforName)}`)
            .then(response => {
                if (!response.ok) {
                    return new Promise((_, reject) => {
                         _t("Errore di rete", t => reject(new Error(t + ": " + response.statusText)));
                    });
                }
                return response.json();
            })
            .then(data => {
                form.reset();
                if (data.error) {
                    _t("Errore", t => alert(t + " : " + data.error));
                    return;
                }

                form.printer_name.value = printerforName;
                form.printer_name.readOnly = true;
                form.connection_type.value = data.connection_type || '';
                form.connection_url.value = data.connection_url || '';
                form.description.value = data.description || '';
                form.location.value = data.location || '';
                document.getElementById('currentManufacturer').value = data.manufacturer || '';
                document.getElementById('currentManufacturerHidden').value = data.manufacturer || '';
                document.getElementById('currentModel').value = data.model || '';
                document.getElementById('currentModelHidden').value = data.model || '';
				var options= form.manufacturer.options;
				var cnf = data.manufacturer;
				if (data.manufacturer=="Printer") cnf = "Ipp";
				for (var i= 0, n= options.length; i < n ; i++) {
					if (options[i].value==cnf) {
						form.manufacturer.selectedIndex = i;
						break;
					}
				}
				loadModels(cnf);
				//	var modello = document.getElementById('modello');
				//	var options= modello.options;
				//	for (var i= 0, n= options.length; i < n ; i++) {
				//		if (options[i].value!= '' && data.model.indexOf(options[i].value)!=-1) {
				//			form.modello.selectedIndex = i;
				//			modello.selectedIndex = i;
				//			break;
				//		}
				//	}
                const urlExampleBox = document.getElementById('urlExampleBox');
                if (urlExampleBox) {
                    urlExampleBox.textContent = data.connection_url || '';
                }

                modal.style.display = 'block';
            })
            .catch(error => {
                _t("Errore nella richiesta", t => alert(t + ": " + error.message));
            });
    });
});

const checkInterval = setInterval(function() {
    const modello = document.getElementById('modello');

    // 3. Controlla se il menu ha più di un'opzione (quella di default + i modelli)
    if (modello.options.length > 1) {
        console.log("Trovati modelli nel menu. Eseguo la selezione.");
		currentModel=document.getElementById('currentModel').value;
        // 4. Ferma l'intervallo per non continuare a controllare inutilmente
        clearInterval(checkInterval);

        // 5. Esegui il tuo codice per selezionare il modello
        const options = modello.options;
        for (let i = 0; i < options.length; i++) {
            if (options[i].value !== '' && currentModel.indexOf(options[i].value) !== -1) {
                modello.selectedIndex = i;
                break;
            }
        }
    }
}, 100);
// Chiude la modale generica della stampante
document.querySelector('#printerModal .close-modal button').addEventListener('click', () => {
    document.getElementById('printerModal').style.display = 'none';
});


// Gestisce la modale delle opzioni della stampante
const optionsPrinterModal = document.getElementById('optionsPrinterModal');
const optionsmodalTitle = optionsPrinterModal.querySelector('.modal-title');
const optionsform = optionsPrinterModal.querySelector('form');

/**
 * Genera i campi del form per le opzioni della stampante.
 * @param {object} options L'oggetto con le opzioni della stampante.
 */
function generateOptionsForm(options) {
    const container = document.getElementById('optionsContainer');
    container.innerHTML = '';

    for (const [label, values] of Object.entries(options)) {
        const groupDiv = document.createElement('div');
        groupDiv.classList.add('group-div');
        groupDiv.style.marginBottom = '15px';

        const groupLabel = document.createElement('strong');
        groupLabel.textContent = label + ':';
        groupLabel.style.display = 'block';
        groupDiv.appendChild(groupLabel);

        const select = document.createElement('select');
        select.name = label;
        select.required = true;
        select.classList.add('option-select');

        _t("Seleziona un'opzione", function(translatedText) {
            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = '-- ' + translatedText + ' --';
            placeholderOption.disabled = true;
            placeholderOption.selected = true;
            select.appendChild(placeholderOption);

            values.forEach(({ value, selected }) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                if (selected) {
                    option.selected = true;
                    placeholderOption.selected = false;
                }
                select.appendChild(option);
            });
        });

        groupDiv.appendChild(select);
        container.appendChild(groupDiv);
    }
}


document.querySelectorAll('.options-printer-btn').forEach(button => {
    button.addEventListener('click', () => {
        const printerName = button.getAttribute('data-printer-name');
        if (!printerName) {
            _t("Nome stampante mancante", t => alert(t));
            return;
        }

        optionsform.reset();
        _t("Opzioni Stampante: ", t => {
            optionsmodalTitle.textContent = t + printerName;
        });

        optionsform.querySelector('input[name="options_printer_name"]').value = printerName;
        optionsform.querySelector('input[name="form_action"]').value = 'setting-options-printer';

        fetch(`../actions/get_options_printer.php?printer_name=${encodeURIComponent(printerName)}`)
            .then(response => {
                if (!response.ok) {
                    return new Promise((_, reject) => {
                        _t("Errore di rete", t => reject(new Error(t + ": " + response.statusText)));
                    });
                }
                return response.json();
            })
            .then(options => {
                generateOptionsForm(options);
                optionsPrinterModal.style.display = 'block';
            })
            .catch(error => {
                _t("Errore nella richiesta:", t => alert(t + " " + error.message));
            });
    });
});

optionsform.addEventListener('submit', function(event) {
    if (loadingModal) loadingModal.style.display = 'block';
    const submitBtn = optionsform.querySelector('[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    setTimeout(() => {
        if (loadingModal) loadingModal.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
    }, 5000);
});

optionsPrinterModal.querySelector('#closeOptionsModal').addEventListener('click', () => {
    optionsPrinterModal.style.display = 'none';
});

// --- Gestione Coda di Stampa ---

// Elimina un lavoro specifico
document.querySelectorAll('.delete-job-btn').forEach(button => {
    button.addEventListener('click', function() {
        const jobId = this.getAttribute('data-job-id');
        if (!jobId) {
            _t("ID lavoro mancante", t => alert(t));
            return;
        }
        _t("Sei sicuro di voler eliminare il lavoro %s?", function(translatedText) {
            if (confirm(translatedText.replace('%s', jobId))) {
                const form = button.closest('form');
                form.submit();
                loadingModal.style.display = "block";
            }
        });
    });
});

// Riprende un lavoro specifico
document.querySelectorAll('.resume-job-btn').forEach(button => {
    button.addEventListener('click', function() {
        const jobId = this.getAttribute('data-job-id');
        if (!jobId) {
            _t("ID lavoro mancante", t => alert(t));
            return;
        }
        _t("Riprendere il lavoro %s?", function(translatedText) {
            if (confirm(translatedText.replace('%s', jobId))) {
                button.closest('form').submit();
                loadingModal.style.display = "block";
            }
        });
    });
});

// Mette in pausa un lavoro specifico
document.querySelectorAll('.pause-job-btn').forEach(button => {
    button.addEventListener('click', function() {
        const jobId = this.getAttribute('data-job-id');
        if (!jobId) {
            _t("ID lavoro mancante", t => alert(t));
            return;
        }
        _t("Mettere in pausa il lavoro %s?", function(translatedText) {
            if (confirm(translatedText.replace('%s', jobId))) {
                button.closest('form').submit();
                loadingModal.style.display = "block";
            }
        });
    });
});

// Sposta un lavoro su un'altra stampante
document.querySelectorAll('.move-job-form').forEach(form => {
    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Blocca sempre l'invio di default prima
        const select = this.querySelector('.destination-printer-select');

        if (!select.value) {
            _t("Seleziona una stampante di destinazione", t => alert(t));
            return;
        }

        _t("Spostare il job sulla stampante %s?", function(translatedText) {
            if (confirm(translatedText.replace('%s', select.value))) {
                if (loadingModal) loadingModal.style.display = "block";
                form.submit();
            }
        });
    });
});


// Mostra la coda di stampa per una stampante specifica
document.querySelectorAll('.btn-get-queue-by-printer').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const printerName = this.getAttribute('data-printer-name');

        fetch('../actions/get_jobs_for_printer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ printer_name: printerName })
            })
            .then(response => {
                if (!response.ok) {
                    return new Promise((_, reject) => {
                        _t("Errore di rete:", t => reject(new Error(t + " " + response.statusText)));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    alert(data.error); // Si presume che gli errori qui non siano tradotti
                    return;
                }
                document.getElementById('queueModalTitle').textContent = data.printer_name;
                const modalTableBody = document.querySelector('#modalQueueTable tbody');
                
                const showModal = () => {
                    document.getElementById('queueModal').style.display = 'block';
                };

                if (!data.jobs || data.jobs.length === 0) {
                    _t("Nessun lavoro in coda", function(translatedText) {
                        modalTableBody.innerHTML = `<tr><td colspan="5">${translatedText}.</td></tr>`;
                        showModal();
                    });
                } else {
                    let jobsHtml = data.jobs.map(job => `
                        <tr>
                            <td>${job.rank || ''}</td>
                            <td>${job.user || ''}</td>
                            <td>${job.job_id || ''}</td>
                            <td>${job.file || ''}</td>
                            <td>${job.size || ''}</td>
                        </tr>`).join('');
                    modalTableBody.innerHTML = jobsHtml;
                    showModal();
                }
            })
            .catch(err => {
                _t("Errore nella richiesta:", t => alert(t + " " + err.message));
            });
    });
});

// Chiude la modale della coda
document.getElementById('closeModal').addEventListener('click', () => {
    document.getElementById('queueModal').style.display = 'none';
});

// Elimina tutti i lavori per una stampante dalla modale della coda
document.getElementById('deleteAllJobsBtn').addEventListener('click', () => {
    const printerName = document.getElementById('queueModalTitle')?.textContent?.trim();

    if (!printerName) {
        _t("Nome stampante non trovato", t => alert(t));
        return;
    }

    _t("Cancellare tutte le code per la stampante %s?", function(translatedText) {
        if (!confirm(translatedText.replace('%s', printerName))) {
            return;
        }

        (async () => {
            try {
                const response = await fetch(`../actions/delete_all_jobs.php?printer_name=${encodeURIComponent(printerName)}`);
                const data = await response.json();

                if (data.success) {
                    _t("Tutte le code sono state cancellate con successo", t => alert(t));
                    const tbody = document.querySelector('#modalQueueTable tbody');
                    if (tbody) tbody.innerHTML = '';
                } else {
                    _t("Errore", t => alert(t + ": " + data.error));
                }
            } catch (error) {
                _t("Si è verificato un errore nella richiesta", t => alert(t));
                console.error(error);
            }
        })();
    });
});

// --- Interruttori UI e Ricerca ---

document.addEventListener('DOMContentLoaded', () => {
    // Attiva/disattiva la visibilità di tutte le code di stampa
    const toggleQueueBtn = document.getElementById('toggleQueueButton');
    if (toggleQueueBtn) {
        let queueVisible = false;
        toggleQueueBtn.addEventListener('click', () => {
            document.querySelectorAll('.hidden-queue').forEach(row => {
                row.style.display = queueVisible ? 'none' : 'table-row';
            });
            queueVisible = !queueVisible;

            _t("Nascondi code", function(stringa1) {
                _t("Mostra tutte le code", function(stringa2) {
                    const tooltipText = queueVisible ? stringa1 : stringa2;
                    toggleQueueBtn.innerHTML = `
                        <span class="tooltip-target">
                            <i data-feather="${queueVisible ? 'chevrons-up' : 'chevrons-down'}" style="width: 40px; height:40px;"></i>
                        </span>
                        <span class="tooltip-bubble">${tooltipText}</span>`;
                    feather.replace();
                });
            });
        });
    }

    // Attiva/disattiva la visibilità di tutte le stampanti
    const togglePrinterBtn = document.getElementById('togglePrinterButton');
    if (togglePrinterBtn) {
        let printersExpanded = false;
        togglePrinterBtn.addEventListener('click', () => {
            document.querySelectorAll('.hidden-printer').forEach(row => {
                row.style.display = printersExpanded ? 'none' : 'table-row';
            });
            printersExpanded = !printersExpanded;

            _t("Nascondi stampanti", function(stringa1) {
                _t("Mostra tutte le stampanti", function(stringa2) {
                    const tooltipText = printersExpanded ? stringa1 : stringa2;
                    togglePrinterBtn.innerHTML = `
                        <span class="tooltip-target">
                            <i data-feather="${printersExpanded ? 'chevrons-up' : 'chevrons-down'}" style="width: 40px; height:40px;"></i>
                        </span>
                        <span class="tooltip-bubble">${tooltipText}</span>`;
                    feather.replace();
                });
            });
        });
    }
});

// Attiva/disattiva lo stato abilitato/disabilitato della stampante
document.querySelectorAll('.toggle-status-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const printerName = btn.getAttribute('data-printer-name');
        const currentStatus = btn.getAttribute('data-status');
        const index = btn.getAttribute('data-index');
        const newAction = currentStatus === 'enabled' ? 'disablePrinter' : 'enablePrinter';

        (async () => {
            try {
                const res = await fetch(`../actions/toggle_printer_status.php?action=${newAction}&printer_name=${encodeURIComponent(printerName)}`);
                const data = await res.json();
                if (data.success) {
                    const iconContainer = btn.querySelector('.tooltip-target');
                    const newIconName = newAction === 'disablePrinter' ? 'play-circle' : 'pause-circle';

                    if (iconContainer) {
                        // Ricrea l'elemento <i> ogni volta.
                        // Questo gestisce sia il click iniziale (sostituendo <i>) sia i click successivi (sostituendo <svg>)
                        iconContainer.innerHTML = `<i data-feather="${newIconName}"></i>`;
                    } else {
                        // Questo non dovrebbe accadere se l'HTML è corretto
                        console.warn(`Contenitore icona non trovato per l'indice: ${index}`);
                    }
                    btn.setAttribute('data-status', newAction === 'disablePrinter' ? 'disabled' : 'enabled');
                    _t(newAction === 'disablePrinter' ? "Disabilitata" : "Abilitata", function(translatedStatus) {
                        // Il selettore dello stato è corretto e dovrebbe funzionare
                        const status = document.querySelector(`.status_${index}`);
                        if (status) {
                            status.innerText = translatedStatus;
                        } else {
                            console.warn(`Elemento stato non trovato per l'indice: ${index}`);
                        }
                    });
                    feather.replace(); // Esegui feather.replace() DOPO aver aggiunto il new <i>
                } else {
                    // Sostituito alert con console.error
                    _t("Errore", t => console.error(t + ": " + data.message));
                }
            } catch (e) {
                console.error(e);
                // Sostituito alert con console.error
                _t("Errore nella richiesta", t => console.error(t));
            }
        })();
    });
});

// Cerca/filtra una stampante per nome
document.getElementById('printerSearch').addEventListener('input', (e) => {
    const filter = e.target.value.toLowerCase();
    const tbody = document.querySelector('#managePrinterTable tbody');

    Array.from(tbody.rows).forEach(row => {
        const cell = row.cells[4];
        const text = cell.textContent;
        cell.innerHTML = text; // Resetta l'evidenziazione

        if (text.toLowerCase().includes(filter)) {
            row.style.display = '';
            if (filter) {
                const regex = new RegExp(`(${filter})`, 'gi');
                cell.innerHTML = text.replace(regex, '<mark>$1</mark>');
            }
        } else {
            row.style.display = 'none';
        }
    });
});
// Oggetto globale per memorizzare le direzioni di ordinamento per tabella
let sortDirections = {};

/**
 * Ordina una tabella HTML in base all'indice di colonna e all'ID della tabella.
 * @param {number} columnIndex L'indice della colonna su cui ordinare.
 * @param {string} tableId L'ID della tabella da ordinare (es. 'managePrinterTable' or 'resultsTable').
 */
window.sortTable = function(columnIndex, tableId) {
    // Inizializza l'oggetto delle direzioni per questa tabella se non esiste
    if (!sortDirections[tableId]) {
        sortDirections[tableId] = {};
    }

    const table = document.querySelector(`#${tableId} tbody`);
    if (!table) {
        console.error(`Tabella con ID #${tableId} non trovata.`);
        return;
    }
    const rows = Array.from(table.rows);

    // Inverti la direzione per questa colonna specifica in questa tabella specifica
    // true = ascendente, false = discendente
    // Se undefined, !undefined diventa true (ascendente di default)
    sortDirections[tableId][columnIndex] = !sortDirections[tableId][columnIndex];
    const direction = sortDirections[tableId][columnIndex];

    rows.sort((a, b) => {
        // Controlla se le celle esistono prima di accedere a textContent
        const cellA = a.cells[columnIndex];
        const cellB = b.cells[columnIndex];

        let valA = cellA ? cellA.textContent.trim() : '';
        let valB = cellB ? cellB.textContent.trim() : '';

        let compare = 0;

        // --- Logica di ordinamento specifica per tabella ---
        if (tableId === 'managePrinterTable') {
            const valALower = valA.toLowerCase();
            const valBLower = valB.toLowerCase();
            
            if (columnIndex === 0) { // Colonna Stato
                const order = { 'enabled': 0, 'disabilita': 1, 'disabled': 1, 'abilitata': 0, 'disabilitata': 1 };
                let orderA = order[valALower] !== undefined ? order[valALower] : 99;
                let orderB = order[valBLower] !== undefined ? order[valBLower] : 99;
                compare = orderA - orderB;
            } else if (columnIndex === 6) { // Colonna Data
                let dateA = Date.parse(valA) || 0; // Usa il testo originale per il parsing
                let dateB = Date.parse(valB) || 0;
                compare = dateA - dateB;
            } else {
                compare = valALower.localeCompare(valBLower); // Confronto stringa standard
            }

        } else if (tableId === 'resultsTable') {
            if (columnIndex === 3) { // Colonna ID coda (numerico)
                let numA = parseInt(valA, 10) || 0;
                let numB = parseInt(valB, 10) || 0;
                compare = numA - numB;
            } else if (columnIndex === 7) { // Colonna Data / Ora
                let dateA = Date.parse(valA) || 0; // Usa il testo originale per il parsing
                let dateB = Date.parse(valB) || 0;
                compare = dateA - dateB;
            } else if (columnIndex === 8) { // Colonna Grandezza (numerico)
                let numA = parseInt(valA, 10) || 0; // '123k' diventerà 123
                let numB = parseInt(valB, 10) || 0;
                compare = numA - numB;
            } else {
                compare = valA.toLowerCase().localeCompare(valB.toLowerCase()); // Confronto stringa standard
            }
        
        } else {
            // Fallback per qualsiasi altra tabella
            compare = valA.toLowerCase().localeCompare(valB.toLowerCase());
        }

        // Applica la direzione
        // Se direction è true (ascendente), ritorna 'compare'
        // Se direction è false (discendente), ritorna '-compare'
        return direction ? compare : -compare;
    });

    // --- NUOVA LOGICA POST-ORDINAMENTO ---
    
    // Determina se l'utente sta visualizzando tutte le righe.
    // IPOTESI: Assumo che il tuo script che gestisce 'toggleQueueButton'
    // aggiunga una classe 'showing-all' al 'tbody' (l'elemento 'table' qui) 
    // quando l'utente clicca per mostrare tutto.
    const isShowingAll = table.classList.contains('showing-all');
    
    // Questo limite deve corrispondere a quello nel tuo PHP
    const visible_limit = 5; 

    // Itera sulle righe *ordinate* e ri-applica le classi di visibilità
    rows.forEach((row, index) => {
        if (isShowingAll) {
            // Se 'mostra tutti' è attivo, assicurati che nessuna riga sia nascosta
            row.classList.remove('hidden-queue');
        } else {
            // Altrimenti, applica la logica standard: nascondi dopo il limite
            if (index < visible_limit) {
                row.classList.remove('hidden-queue');
            } else {
                row.classList.add('hidden-queue');
            }
        }
    });
    // --- FINE NUOVA LOGICA ---


    // Ri-aggiungi le righe ordinate e con le classi corrette al tbody
    // 'appendChild' sposta gli elementi, non li duplica, quindi l'ordine nel DOM
    // corrisponderà all'ordine nell'array 'rows'.
    rows.forEach(row => table.appendChild(row));
};