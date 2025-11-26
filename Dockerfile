FROM ubuntu:latest

LABEL maintainer="j01PrintServer by 01Informatica S.R.L. - 2025"

ENV TZ=Europe/Rome

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Aggiorna la lista pacchetti
RUN apt-get update

# Installazione pacchetti necessari
RUN apt-get install -y cups
RUN apt-get install -y cups-bsd
RUN apt-get install -y nginx
RUN apt-get install -y php-fpm
RUN apt-get install -y php-cli
RUN apt-get install -y php-xml
RUN apt-get install -y php-odbc
RUN apt-get install -y unixodbc 
RUN apt-get install -y unixodbc-dev
RUN apt-get install -y zip
RUN apt-get install -y openjdk-17-jdk
RUN apt-get install -y nmap
RUN apt-get install -y snmp
# AGGIUNTO: Esegui un 'update' dedicato prima di un'installazione
# complessa per evitare errori 404 dovuti a cache stantìe.
RUN apt-get update
RUN apt-get install -y docker.io 

# Copia tutti i driver IBM i Access ODBC in /tmp/drivers
COPY drivers /tmp/drivers	

# Script per installazione dinamica del driver ODBC
#script shell che rileva l’architettura con dpkg --print-architecture
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
	
# Copia i file di configurazione ODBC (opzionale: altrimenti monta come volume)
COPY odbc.ini /etc/odbc.ini
COPY odbcinst.ini /etc/odbcinst.ini	
COPY ssl /etc/nginx/sites-available/ssl
COPY cupsd.conf /etc/cups/cupsd.conf										   
				 
# Abilita modulo ODBC per PHP (ci sara da riavviare il servizio PHP-FPM)
RUN phpenmod odbc

# Copia configurazioni personalizzate per Nginx
COPY ./nginx/default /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Copia script di avvio
COPY ./start.sh /start.sh
RUN chmod +x /start.sh

# Abilita permessi per Docker						   
# RUN usermod -aG docker www-data && chmod 666 /var/run/docker.sock serve solo per fare comandi docker

# Utente prt di gestione nel DOCKERFILE per poter accedere all’interfaccia cups di amministrazione
RUN adduser prt \
    && usermod -aG lpadmin prt 

# Cartella per generare il certificato
RUN mkdir -p /etc/nginx/ssl && \
    openssl req -x509 -nodes -days 9125 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx-selfsigned.key \
    -out /etc/nginx/ssl/nginx-selfsigned.crt \
    -subj "/C=IT/ST=Italy/L=*/O=01 Informatica srl/CN=info01.it"
	
EXPOSE 80 443 631

# Serve dare i permessi a www-data per leggere i file .pdd dalla cartella etc/cups/pdd/*.pdd
RUN usermod -aG lp www-data 

RUN ln -s /etc/nginx/sites-available/ssl /etc/nginx/sites-enabled/ssl

CMD ["/start.sh"]
