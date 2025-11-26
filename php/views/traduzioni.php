<?php
// Percorso al file delle traduzioni
if (!defined('TRANSLATION_FILE')) {
    define('TRANSLATION_FILE', __DIR__ . '/lang_array.php');
}

// Lingue supportate
$supportedLanguages = ['it', 'en', 'fr', 'de', 'es'];

// Funzione per ottenere la lingua dal browser
if (!function_exists('getBrowserLanguage')) {
    function getBrowserLanguage() {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            return substr($langs[0], 0, 2);
        }
        return 'en';
    }
}

// Funzione di traduzione principale
if (!function_exists('_t')) {
    function _t(string $label): string {
        global $supportedLanguages;
        
        $lang = getBrowserLanguage();
        $lang = in_array($lang, $supportedLanguages) ? $lang : 'en';


        // Se il file non esiste, crealo con array vuoti per tutte le lingue
        if (!file_exists(TRANSLATION_FILE)) {
            $translations = [];
            foreach ($supportedLanguages as $l) {
                $translations[$l] = [];
            }
            $content = "<?php\n\n";
            foreach ($translations as $k => $v) {
                $content .= "\$translations['$k'] = " . var_export($v, true) . ";\n\n";
            }
            // Aggiungiamo un controllo per evitare errori di permessi se il file non è scrivibile
            @file_put_contents(TRANSLATION_FILE, $content);
        }

        // Includi il file, se esiste
        if (file_exists(TRANSLATION_FILE)) {
            include TRANSLATION_FILE;
        } else {
            $translations = []; // Fallback se il file non può essere creato/letto
        }


        // Se la traduzione esiste, la ritorniamo
        if (isset($translations[$lang][$label])) {
            return $translations[$lang][$label];
        }

        // Altrimenti, aggiungila a tutte le lingue
        foreach ($supportedLanguages as $l) {
            if (!isset($translations[$l][$label])) {
                if ($l === 'it') {
                    $translations[$l][$label] = $label;
                } else {
                    $translations[$l][$label] = "TO_TRANSLATE: $label";
                }
            }
        }

        // Riscrivi il file aggiornato
        $content = "<?php\n\n";
        foreach ($translations as $langKey => $langArray) {
            $content .= "\$translations['$langKey'] = " . var_export($langArray, true) . ";\n\n";
        }
        // Aggiungiamo un controllo per evitare errori di permessi se il file non è scrivibile
        @file_put_contents(TRANSLATION_FILE, $content);

        // Ritorna la traduzione creata o segnaposto
        return $translations[$lang][$label] ?? $label;
    }
}


if (isset($_POST["str_lingua"])) {
    $stringa = $_POST["str_lingua"];
    echo _t($stringa);
}

