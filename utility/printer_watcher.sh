#!/bin/bash

# === CONFIG ===
WATCH_DIR="/codice01"
FILENAME="printer.txt"
RESULT_FILE="/codice01/printer_log.txt"

# Crea la cartella se non esiste
mkdir -p "$WATCH_DIR"

echo "🖨️  Monitoraggio avviato. In attesa di $FILENAME nella cartella $WATCH_DIR..."

while true; do
    FILEPATH="$WATCH_DIR/$FILENAME"

    if [ -f "$FILEPATH" ]; then
        # echo "📄 Trovato $FILENAME, elaborazione..."

        # Pulisce risultato precedente
        rm -f "$RESULT_FILE"
		
        # Leggi il contenuto del file
        COMMAND=$(cat "$FILEPATH")
		
		rm -f "$FILEPATH"
		
        if [ -z "$COMMAND" ]; then
            echo "COMANDO: - $COMMAND -" >> "$RESULT_FILE"
            #echo "⚠️  Il file è vuoto. Ignorato."
            echo "ERRORE: file vuoto." > "$RESULT_FILE"
        else
            echo "🔧 Avvio script con parametro: $COMMAND"
			
            OUTPUT=$($COMMAND 2>&1)
            EXIT_CODE=$?
            echo "Errore: $OUTPUT" > "$RESULT_FILE"
            if [ $EXIT_CODE -eq 0 ]; then
                #echo "✅ Comando completato con successo."
                echo "Successo: $COMMAND." >> "$RESULT_FILE"
            else
                echo "COMANDO: - $COMMAND -" >> "$RESULT_FILE"
                echo "EXIT_CODE: - $EXIT_CODE -" >> "$RESULT_FILE"
                #echo "❌ Errore durante l'esecuzione (exit code: $EXIT_CODE)"  
                # Sanifica output per evitare problemi in PHP
                SANITIZED_OUTPUT=$(echo "$OUTPUT" | sed 's/["`$]/ /g' | head -n 10)
                echo "ERRORE: $SANITIZED_OUTPUT" >> "$RESULT_FILE"
            fi
        fi
        chown www-data:www-data "$RESULT_FILE"
        # Rimuove il file di trigger
        rm -f "$FILEPATH"
        #echo "🧹 File $FILENAME rimosso."
    fi

    sleep 0.1
done
