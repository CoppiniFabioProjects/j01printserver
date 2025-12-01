FROM ubuntu:latest

LABEL maintainer="j01PrintServer by 01Informatica S.R.L. - 2025"

ENV TZ=Europe/Rome

# Imposta fuso orario
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Aggiorna pacchetti e installa tutto in un solo layer
RUN apt-get update && \
    apt-get install -y \
        cups cups-bsd \
        nginx \
        php-fpm php-cli php-xml php-odbc \
        unixodbc unixodbc-dev \
        zip \
        openjdk-17-jdk \
        nmap snmp \
        docker.io && \
    # Rimuove cups-browsed se presente e pulisce pacchetti non necessari
    apt-get purge -y cups-browsed && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/*

# Copia driver IBM i Access ODBC
COPY drivers /tmp/drivers

# Script per installazione dinamica driver ODBC
RUN bash -c '\
    ARCH=$(dpkg --print-architecture); \
    echo "Rilevata architettura: $ARCH"; \
    if [ "$ARCH" = "amd64" ]; then \
        DRIVER="/tmp/drivers/x86_64/ibm-iaccess-*.deb"; \
    elif [ "$ARCH" = "ppc64le" ] || [ "$ARCH" = "ppc64el" ]; then \
        DRIVER="/tmp/drivers/ppc64le/ibm-iaccess-*.deb"; \
    else \
        echo "Architettura non supportata: $ARCH" && exit 1; \
    fi; \
    dpkg -i $DRIVER || apt-get install -f -y; \
    rm -f $DRIVER'

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

COPY ./start.sh /start.sh
RUN chmod +x /start.sh

# Copia tutti i file nella cartella /codice01/j01printserver
COPY . /codice01/j01printserver

# Installa dos2unix e converti i file
RUN apt-get update && apt-get install -y dos2unix && rm -rf /var/lib/apt/lists/* && \
    dos2unix /codice01/j01printserver/start.sh && \
    dos2unix /codice01/j01printserver/install_printserver.sh && \
    dos2unix /codice01/j01printserver/uninstall_printserver.sh && \
    dos2unix /codice01/j01printserver/utility/linux-brprinter-installer-2.2.4-1 && \
    chmod +x /codice01/j01printserver/utility/linux-brprinter-installer-2.2.4-1

# Utente di gestione CUPS
RUN adduser prt && usermod -aG lpadmin prt

# Cartella per certificati SSL e creazione certificato self-signed
RUN mkdir -p /etc/nginx/ssl && \
    openssl req -x509 -nodes -days 9125 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx-selfsigned.key \
    -out /etc/nginx/ssl/nginx-selfsigned.crt \
    -subj "/C=IT/ST=Italy/L=*/O=01 Informatica srl/CN=info01.it"

# Permessi a www-data per leggere file .pdd
RUN usermod -aG lp www-data

# Esponi le porte
EXPOSE 80 443 631

# Avvio del container
CMD ["/start.sh"]
