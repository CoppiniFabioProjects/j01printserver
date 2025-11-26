#!/bin/bash

# --- Funzioni di Utilità ---

# Funzione per gestire l'uscita controllata
prompt_to_exit() {
    echo -e "\nPremi ESC per annullare l'installazione, o attendi 5 secondi per riprovare..."
    # Legge un singolo carattere, in modalità silenziosa, con un timeout di 5 secondi
    read -s -n 1 -t 5 key
    # Controlla se il carattere letto è ESC (codice \e)
    if [[ $key == $'\e' ]]; then
        echo -e "\n\n❌ Installazione annullata dall'utente."
        exit 1
    fi
    # Pulisce la riga per il prossimo input
    echo -e "\r                                                                    \r"
}

# Controlla se una specifica porta è in uso
is_port_in_use() {
    local port=$1
    if ss -lntu | grep -q ":${port}\b"; then
        return 0 # La porta è in uso
    else
        return 1 # La porta è libera
    fi
}

# Ottiene una lista formattata di tutte le porte TCP/UDP in ascolto
get_occupied_ports() {
    ss -lntu | awk '{print $5}' | cut -d':' -f2 | grep -E '^[0-9]+$' | sort -un | tr '\n' ',' | sed 's/,$//'
}

# Controlla se un nome di container è già in uso
is_container_name_in_use() {
    local name=$1
    if sudo docker ps -a --format '{{.Names}}' | grep -q "^${name}$"; then
        return 0 # Nome in uso
    else
        return 1 # Nome non in uso
    fi
}

# Ottiene una lista formattata di tutti i nomi dei container esistenti
get_existing_container_names() {
    sudo docker ps -a --format '{{.Names}}' | tr '\n' ',' | sed 's/,$//'
}

# Funzione per installare e configurare logrotate
setup_logrotate() {
    # Questa funzione usa le variabili globali DEFAULT_DIR e CONTAINER_NAME
    # definite nello script principale, che devono essere già impostate.

    echo "----------------------------------------------------"
    echo "⚙️  Configurazione della rotazione dei log (logrotate)..."

    # 1. Definisci il percorso REALE del log sull'HOST
    # Questo è il percorso che logrotate deve monitorare
    local HOST_LOG_FILE_PATH="$DEFAULT_DIR/$CONTAINER_NAME/j01printserver/info01/wrapper.log"
    
    # Usiamo un nome file di config univoco per questo container
    local LOGROTATE_CONF_FILE="/etc/logrotate.d/j01printserver_${CONTAINER_NAME}"
    
	# Definiamo e creiamo una cartella di backup per i log archiviati
    # Sarà dentro la cartella del container, così verrà eliminata automaticamente
    # con lo script di uninstall.
    local HOST_BACKUP_DIR="$DEFAULT_DIR/$CONTAINER_NAME/j01printserver/info01/log_backups"
    echo "Creo la cartella di backup dei log a lungo termine: $HOST_BACKUP_DIR"
    # Creiamo la cartella con sudo, 'root' (che esegue logrotate) ne sarà il proprietario.
    sudo mkdir -p "$HOST_BACKUP_DIR"											
	
    # 2. Assicurati che logrotate sia installato
    if ! command -v logrotate &> /dev/null; then
        echo "logrotate non trovato. Tentativo di installazione..."
        # Lo script usa 'sudo' per docker, quindi assumiamo di poter usare sudo qui
        if command -v apt-get &> /dev/null; then
            # AGGIUNTO: Controllo dell'errore su apt-get update
            sudo apt-get -qq update
            if [ $? -ne 0 ]; then
                echo "⚠️  ERRORE: 'apt-get update' sull'HOST è fallito." >&2
                echo "    Controlla la configurazione di APT (es. /etc/apt/sources.list)." >&2
                echo "    Impossibile continuare con l'installazione di logrotate." >&2
                return 1
            fi
            
            sudo apt-get -qq install -y logrotate
            # AGGIUNTO: Controllo dell'errore sull'installazione
            if [ $? -ne 0 ]; then
                echo "⚠️  ERRORE: Impossibile installare 'logrotate' sull'HOST." >&2
                echo "    Controlla la configurazione di APT (E: Failed to fetch...)." >&2
                return 1
            fi
            echo "logrotate installato."
        elif command -v yum &> /dev/null; then # <-- CORREZIONE 1: Aggiunto 'then'
            # AGGIUNTO: Blocco logica per yum
            sudo yum install -y logrotate
            if [ $? -ne 0 ]; then
                echo "⚠️  ERRORE: Impossibile installare 'logrotate' sull'HOST." >&2
                return 1
            fi
            echo "logrotate installato."
        else
            # blocco 'else' per gestore pacchetti
            echo "⚠️ Impossibile installare logrotate. Gestore pacchetti non riconosciuto." >&2
            echo "    Dovrai installare 'logrotate' manualmente." >&2
            return 1
        fi # fi per chiudere if/elif/else
    else
        echo "logrotate è già installato."
    fi # <-- fi per chiudere if ! command -v

    # ORA il 'cat' può essere eseguito, dato che è fuori dai blocchi condizionali
    # Le virgolette attorno a "$HOST_LOG_FILE_PATH" gestiscono eventuali spazi nel percorso.
    cat <<EOF | sudo tee "$LOGROTATE_CONF_FILE" > /dev/null
# Configurazione di Logrotate per il container $CONTAINER_NAME
# Questo file è stato generato automaticamente da install_printserver.sh
# Percorso del log sull'HOST (mappato da /codice01/... nel container):

"$HOST_LOG_FILE_PATH" {
    # Ruota i log ogni giorno.
    daily

    # Archiviamo i log per 180 giorni.
    maxage 180
    
    # Sposta i log vecchi in questa cartella di archivio
    # Invece di lasciarli nella stessa directory.
    olddir "$HOST_BACKUP_DIR"												

    # Comprimi (con gzip) i file di log ruotati.
    compress
    delaycompress

    # Non andare in errore se il file di log non viene trovato.
    missingok

    # Non ruotare il log se è vuoto.
    notifempty

    # Ruota se > 100MB
    size 100M

    # Metodo di rotazione: copia e tronca.
    # Sicuro per i servizi che tengono il file log aperto.
    copytruncate
}
EOF

    echo "✅ Configurazione logrotate creata."
    echo "   Monitorerà il file: $HOST_LOG_FILE_PATH"
    echo "----------------------------------------------------"
}


# --- Script Principale ---

# Rileva architettura host
ARCH=$(uname -m)
echo "🔍 Architettura macchina: $ARCH"

# Mappa architettura host -> directory driver
if [ "$ARCH" = "x86_64" ]; then
    DRIVER_DIR="x86_64"
# CORREZIONE: Aggiunto '4' a ppc64le e aggiunto il controllo per ppc64el
elif [ "$ARCH" = "ppc64le" ] || [ "$ARCH" = "ppc64el" ]; then
    DRIVER_DIR="ppc64le"
else
    echo "❌ Architettura non supportata: $ARCH"
    exit 1
fi

echo "📦 Verranno usati i driver da: drivers/$DRIVER_DIR"

# --- Input guidato e validato per le porte ---
PORT0=8080
HOST_HTTP_PORT=""

while true; do
    read -p "Inserisci la porta HOST per HTTP (default: $PORT0): " input_port
    HOST_HTTP_PORT=${input_port:-$PORT0}

    # 1. Validazione: Deve essere un numero intero
    if ! [[ "$HOST_HTTP_PORT" =~ ^[0-9]+$ ]]; then
        echo "❌ Errore: Devi inserire un valore numerico."
        prompt_to_exit
        continue
    fi

    # 2. Calcolo e controllo di tutte le porte necessarie
    HOST_HTTPS_PORT=$((HOST_HTTP_PORT + 1))
    HOST_CUPS_PORT=$((HOST_HTTP_PORT + 2))
    HOST_SSH_PORT=$((HOST_HTTP_PORT + 3))
    
    ports_to_check=($HOST_HTTP_PORT $HOST_HTTPS_PORT $HOST_CUPS_PORT $HOST_SSH_PORT)
    all_ports_free=true

    for port in "${ports_to_check[@]}"; do
        if is_port_in_use "$port"; then
            echo "⚠️ Attenzione: La porta $port è già occupata."
            all_ports_free=false
        fi
    done

    # 3. Decisione finale
    if [ "$all_ports_free" = true ]; then
        echo "✅ Porte disponibili. Si procede con: HTTP=${HOST_HTTP_PORT}, HTTPS=${HOST_HTTPS_PORT}, CUPS=${HOST_CUPS_PORT}, SSH=${HOST_SSH_PORT}"
        break # Esce dal loop, le porte sono valide e libere
    else
        echo " Riprova con un'altra porta di base."
        occupied_ports=$(get_occupied_ports)
        echo "ℹ️ Porte attualmente occupate sul sistema: $occupied_ports"
        prompt_to_exit
    fi
done


# Nome immagine (sempre lowercase)
IMAGE_NAME="j01printserver"
IMAGE_NAME=$(echo "$IMAGE_NAME" | tr '[:upper:]' '[:lower:]')

# --- Input guidato e validato per il nome del container ---
DEFAULT_NAME="office01"
CONTAINER_NAME=""

while true; do
    read -p "Inserisci il nome del container (default: $DEFAULT_NAME): " input_name
    CONTAINER_NAME=${input_name:-$DEFAULT_NAME}
    CONTAINER_NAME=$(echo "$CONTAINER_NAME" | tr '[:upper:]' '[:lower:]')

    # 1. Validazione: Deve essere un nome valido per un container
    if ! [[ "$CONTAINER_NAME" =~ ^[a-zA-Z0-9][a-zA-Z0-9_.-]+$ ]]; then
        echo "❌ Errore: Il nome del container può contenere solo lettere, numeri, '.', '_' o '-'."
        echo "         Deve iniziare con una lettera o un numero."
        prompt_to_exit
        continue
    fi

    # 2. Validazione: Il nome non deve essere già in uso
    if is_container_name_in_use "$CONTAINER_NAME"; then
        echo "⚠️ Attenzione: Un container con il nome \"$CONTAINER_NAME\" esiste già."
        existing_names=$(get_existing_container_names)
        echo "ℹ️ Nomi dei container esistenti: $existing_names"
        echo "   Scegli un nome differente."
        prompt_to_exit
        continue
    fi
    
    echo "✅ Nome container \"$CONTAINER_NAME\" valido e disponibile."
    break
done


# Cartella progetto
DEFAULT_DIR=$(pwd)

# Controllo se la directory del container esiste già
if [ -d "$DEFAULT_DIR/$CONTAINER_NAME" ]; then
    echo "⚠️ La cartella \"$DEFAULT_DIR/$CONTAINER_NAME\" esiste già. NON si effettua la sovrascrittura."
    exit 0
fi

# Creo la cartella del progetto
echo "📁 Creazione della directory del progetto: $DEFAULT_DIR/$CONTAINER_NAME"
mkdir "$DEFAULT_DIR/$CONTAINER_NAME"
cp -r "$DEFAULT_DIR/codice01/j01printserver/" "$DEFAULT_DIR/$CONTAINER_NAME"
cp -r "$DEFAULT_DIR/codice01/fopconf/" "$DEFAULT_DIR/$CONTAINER_NAME"

echo "Container name: $CONTAINER_NAME" > "$DEFAULT_DIR/$CONTAINER_NAME/README.txt"
echo "Http port:$HOST_HTTP_PORT" >> "$DEFAULT_DIR/$CONTAINER_NAME/README.txt"
echo "Https port:$HOST_HTTPS_PORT" >> "$DEFAULT_DIR/$CONTAINER_NAME/README.txt"
echo "Cups port:$HOST_CUPS_PORT" >> "$DEFAULT_DIR/$CONTAINER_NAME/README.txt"

echo "🔧 Imposto permessi per la cartella /codice01 ..."
# CORREZIONE: Applica chown al percorso HOST ($DEFAULT_DIR/$CONTAINER_NAME),
# non al percorso "assoluto" /codice01.
# Ho aggiunto -R per applicarlo ricorsivamente a tutti i file copiati.
sudo chown "$(whoami)":"$(whoami)" "$DEFAULT_DIR/$CONTAINER_NAME" -R

echo "🚀 Build dell'immagine Docker \"$IMAGE_NAME\" dalla cartella \"$DEFAULT_DIR\"..."
sudo docker buildx build -t "$IMAGE_NAME" -f "$DEFAULT_DIR/Dockerfile" "$DEFAULT_DIR"

# AGGIUNTO: Controllo dell'errore sulla build di Docker
if [ $? -ne 0 ]; then
    echo "❌ ERRORE CRITICO: La build dell'immagine Docker è fallita."
    echo "Controlla l'output qui sopra per i dettagli (es. Errore 17/32)."
    exit 1
fi

echo "🚀 Avvio del container \"$CONTAINER_NAME\" in background con le porte scelte..."
sudo docker run -d --name "$CONTAINER_NAME" \
    -p $HOST_HTTP_PORT:80 \
    -p $HOST_HTTPS_PORT:443 \
    -p $HOST_CUPS_PORT:631 \
    -p $HOST_SSH_PORT:22 \
    -v "$DEFAULT_DIR/php":/php \
    -v "$DEFAULT_DIR/utility":/utility \
    -v "$DEFAULT_DIR/$CONTAINER_NAME":/codice01 \
    -v "$DEFAULT_DIR/$CONTAINER_NAME/README.txt":/README.txt \
    -v "$DEFAULT_DIR/utility/brother_models.txt":/brother_models.txt \
    -v /var/run/docker.sock:/var/run/docker.sock \
    -v cups_config:/etc/cups \
    "$IMAGE_NAME"

echo "⏳ Attendo qualche secondo che il container si avvii..."
sudo docker start $CONTAINER_NAME;

sleep 5

echo "🔍 Controllo se php-fpm è in esecuzione dentro il container..."
PHPFPM_STATUS=$(sudo docker exec "$CONTAINER_NAME" pgrep php-fpm || true)

if [ -z "$PHPFPM_STATUS" ]; then
    echo "⚙️  php-fpm NON è in esecuzione. Lo avvio..."
    PHP_VERSION=$(sudo docker exec "$CONTAINER_NAME" php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    sudo docker exec "$CONTAINER_NAME" service php${PHP_VERSION}-fpm start
else
    echo "✅ php-fpm è già in esecuzione."
fi

# --- CHIAMATA ALLA FUNZIONE LOGROTATE ---
# Ora che DEFAULT_DIR e CONTAINER_NAME sono impostati e il container è avviato,
# configuriamo la rotazione dei log sull'host.
setup_logrotate

echo "🌐 Puoi visitare ora:"
echo "  🔸 HTTP → http://localhost:$HOST_HTTP_PORT/login.php"
echo "  🔸 HTTPS → https://localhost:$HOST_HTTPS_PORT/login.php"
echo "  🔸 Info PHP (HTTP) → http://localhost:$HOST_HTTP_PORT/info.php"
echo "  🔸 CUPS → http://localhost:$HOST_CUPS_PORT"

echo ""
echo "📁 Volume del progetto montato nel container:"
echo "  Host      → $DEFAULT_DIR/$CONTAINER_NAME"
echo "  Container → /codice01"
echo ""
echo "🎉 Installazione completata!"