#!/bin/bash

# Script per rimuovere un container j01printserver e i suoi file associati

# --- Definizioni Colori ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Script di Rimozione j01printserver${NC}"
echo "----------------------------------------------------"

# --- 1. Controllo Esecuzione come Root ---
# È necessario 'sudo' per i comandi Docker e per rimuovere il file logrotate
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ Errore: Questo script deve essere eseguito come root o con sudo.${NC}"
    echo "Riprova con: sudo ./uninstall_printserver.sh"
    exit 1
fi

# --- 2. Richiesta Nome Container ---
# Se viene passato un argomento, usalo come nome, altrimenti chiedi
if [ -n "$1" ]; then
    CONTAINER_NAME=$1
    echo -e "Container da rimuovere specificato: ${YELLOW}$CONTAINER_NAME${NC}"
else
    read -p "Inserisci il nome esatto del container da rimuovere: " CONTAINER_NAME
fi

if [ -z "$CONTAINER_NAME" ]; then
    echo -e "${RED}❌ Annullato. Nessun nome inserito.${NC}"
    exit 1
fi

# Converti in lowercase (l'install script lo fa)
CONTAINER_NAME=$(echo "$CONTAINER_NAME" | tr '[:upper:]' '[:lower:]')

# --- 3. Verifica Esistenza Container ---
# Controlliamo se il container esiste
DOES_CONTAINER_EXIST=true
if ! docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
    echo -e "${YELLOW}⚠️  Attenzione: Un container con il nome \"$CONTAINER_NAME\" non è stato trovato.${NC}"
    echo "   (Potrebbe essere già stato rimosso)."
    DOES_CONTAINER_EXIST=false
    # Non usciamo, potremmo voler rimuovere solo la cartella
fi

if [ "$DOES_CONTAINER_EXIST" = true ]; then
    echo -e "✅ Container \"${GREEN}$CONTAINER_NAME${NC}\" trovato."
fi

# --- 4. Trova la Cartella di Progetto (Host) ---
# CORREZIONE: Invece di 'docker inspect' (che fallisce su container vecchi/manuali),
# assumiamo che la cartella del progetto sia nella directory corrente
# e abbia lo stesso nome del container (come fa lo script di installazione).
echo "🔍 Ricerca della cartella di progetto..."
PROJECT_DIR="$(pwd)/$CONTAINER_NAME"

if [ ! -d "$PROJECT_DIR" ]; then
    echo -e "${YELLOW}⚠️  Attenzione: La cartella di progetto non è stata trovata nel percorso atteso:${NC}"
    echo "   $PROJECT_DIR"
    PROJECT_DIR="" # Resetta per non tentare la rimozione
else
    echo -e "✅ Cartella di progetto trovata: ${GREEN}$PROJECT_DIR${NC}"
fi


# --- 5. Trova File Logrotate ---
LOGROTATE_CONF_FILE="/etc/logrotate.d/j01printserver_${CONTAINER_NAME}"

if [ ! -f "$LOGROTATE_CONF_FILE" ]; then
    echo -e "${YELLOW}ℹ️  File logrotate non trovato (potrebbe essere già stato rimosso):${NC}"
    echo "   $LOGROTATE_CONF_FILE"
else
    echo -e "✅ File Logrotate trovato: ${GREEN}$LOGROTATE_CONF_FILE${NC}"
fi

# --- 6. Conferma Finale ---
echo "----------------------------------------------------"
echo -e "${RED}ATTENZIONE: Stai per rimuovere permanentemente:${NC}"
if [ "$DOES_CONTAINER_EXIST" = true ]; then
    echo -e "  1. Container Docker: ${YELLOW}$CONTAINER_NAME${NC}"
fi
if [ -n "$PROJECT_DIR" ]; then
    echo -e "  2. Cartella Progetto:  ${YELLOW}$PROJECT_DIR${NC}"
fi
echo -e "  3. Config Logrotate: ${YELLOW}$LOGROTATE_CONF_FILE${NC} (se esiste)"
echo "----------------------------------------------------"

# Se non c'è nulla da fare, esci
if [ "$DOES_CONTAINER_EXIST" = false ] && [ -z "$PROJECT_DIR" ] && [ ! -f "$LOGROTATE_CONF_FILE" ]; then
    echo -e "${GREEN}Nulla da fare. Il container e i file associati sono già stati rimossi.${NC}"
    exit 0
fi

read -p "Sei assolutamente sicuro? Questa operazione NON può essere annullata. (s/N): " confirm
echo "" # Aggiungi una linea vuota

if [[ ! "$confirm" =~ ^[sS]$ ]]; then
    echo -e "${GREEN}👍 Rimozione annullata dall'utente.${NC}"
    exit 0
fi

# --- 7. Esecuzione Rimozione ---

# A. Stop e Rimozione Container
if [ "$DOES_CONTAINER_EXIST" = true ]; then
    echo "⚙️  Fermo il container (stop)..."
    docker stop "$CONTAINER_NAME" > /dev/null
    
    echo "⚙️  Rimuovo il container (rm)..."
    docker rm "$CONTAINER_NAME"
    echo -e "✅ Container ${GREEN}$CONTAINER_NAME${NC} rimosso."
fi

# B. Rimozione File Logrotate
if [ -f "$LOGROTATE_CONF_FILE" ]; then
    echo "⚙️  Rimuovo il file logrotate..."
    rm -f "$LOGROTATE_CONF_FILE"
    echo -e "✅ File ${GREEN}$LOGROTATE_CONF_FILE${NC} rimosso."
fi

# C. Rimozione Cartella Progetto
if [ -n "$PROJECT_DIR" ] && [ -d "$PROJECT_DIR" ]; then
    echo "⚙️  Rimuovo la cartella di progetto..."
    # Usiamo sudo perché è stato richiesto all'avvio
    rm -rf "$PROJECT_DIR"
    echo -e "✅ Cartella ${GREEN}$PROJECT_DIR${NC} rimossa."
fi

echo -e "\n${GREEN}🎉 Rimozione completata!${NC}"