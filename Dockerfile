FROM ubuntu:latest

LABEL maintainer="j01PrintServer by 01Informatica S.R.L. - 2025"

ENV TZ=Europe/Rome

# Imposta fuso orario
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Aggiorna pacchetti e installa tutto in un solo layer
# Ho aggiunto 'dos2unix' qui per averlo subito disponibile
RUN apt-get update && \
    apt-get install -y \
        cups cups-bsd \
        nginx \
        php-fpm php-cli php-xml php-odbc \
        unixodbc unixodbc-dev \
        zip \
        openjdk-17-jdk \
        nmap snmp \
        docker.io \
        dos2unix && \
    # Rimuove cups-browsed se presente e pulisce pacchetti non necessari
    apt-get purge -y cups-browsed && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/*

# --- INSTALLAZIONE DRIVER IBM DINAMICA ---
# Copia l'intera cartella driver per avere a disposizione tutte le architetture
COPY drivers /tmp/drivers

# Script per installazione dinamica driver ODBC in base all'architettura
RUN bash -c '\
    ARCH=$(dpkg --print-architecture); \
    echo "Rilevata architettura: $ARCH"; \
    if [ "$ARCH" = "amd64" ]; then \
        # Cerca il driver nella cartella x86_64
        DRIVER=$(ls /tmp/drivers/x86_64/ibm-iaccess-*.deb 2>/dev/null | head -n 1); \
    elif [ "$ARCH" = "ppc64le" ] || [ "$ARCH" = "ppc64el" ]; then \
        # Cerca il driver nella cartella ppc64le
        DRIVER=$(ls /tmp/drivers/ppc64le/ibm-iaccess-*.deb 2>/dev/null | head -n 1); \
    else \
        echo "Architettura non supportata: $ARCH" && exit 1; \
    fi; \
    \
    if [ -z "$DRIVER" ]; then \
        echo "ERRORE: Nessun driver trovato per architettura $ARCH in /tmp/drivers" && exit 1; \
    fi; \
    \
    echo "Installazione driver: $DRIVER"; \
    # Tenta installazione con dpkg, se mancano dipendenze ripara con apt-get -f
    dpkg -i $DRIVER || apt-get install -f -y; \
    rm -rf /tmp/drivers'

# Copia configurazioni ODBC
COPY odbc.ini /etc/odbc.ini
COPY odbcinst.ini /etc/odbcinst.ini

# Copia configurazioni SSL e CUPS
COPY ssl /etc/nginx/sites-available/ssl
COPY cupsd.conf /etc/cups/cupsd.conf

# Abilita modulo ODBC per PHP
RUN phpenmod odbc

# Configurazioni personalizzate Nginx
COPY ./nginx/default /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
RUN ln -s /etc/nginx/sites-available/ssl /etc/nginx/sites-enabled/ssl

# Copia script di avvio
COPY ./start.sh /start.sh
RUN chmod +x /start.sh

# Copia tutti i file nella cartella /codice01/j01printserver
COPY . /codice01/j01printserver

# Conversione dei file line-endings (importante se modifichi file da Windows)
RUN dos2unix /start.sh && \
    dos2unix /codice01/j01printserver/install_printserver.sh && \
    dos2unix /codice01/j01printserver/uninstall_printserver.sh && \
    # Gestione sicura nel caso il file installer non esista
    if [ -f /codice01/j01printserver/utility/linux-brprinter-installer-2.2.4-1 ]; then \
        dos2unix /codice01/j01printserver/utility/linux-brprinter-installer-2.2.4-1 && \
        chmod +x /codice01/j01printserver/utility/linux-brprinter-installer-2.2.4-1; \
    fi

# Utente di gestione CUPS
RUN adduser prt && usermod -aG lpadmin prt

# Cartella per certificati SSL e creazione certificato self-signed
RUN mkdir -p /etc/nginx/ssl && \
    openssl req -x509 -nodes -days 9125 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx-selfsigned.key \
    -out /etc/nginx/ssl/nginx-selfsigned.crt \
    -subj "/C=IT/ST=Italy/L=*/O=01 Informatica srl/CN=info01.it"

# Permessi a www-data per leggere file .pdd e stampare
RUN usermod -aG lp www-data

# Esponi le porte
EXPOSE 80 443 631

# Avvio del container
CMD ["/start.sh"]
