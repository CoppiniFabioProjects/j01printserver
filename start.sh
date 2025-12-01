#!/bin/bash

set -e

echo "🔧 Configurazione permessi odbc.ini..."
# Assicura che il file esista
touch /etc/odbc.ini
# Cambia il proprietario in www-data (l'utente che esegue PHP)
chown www-data:www-data /etc/odbc.ini
# Permette lettura/scrittura al proprietario e al gruppo
chmod 664 /etc/odbc.ini
echo "✅ Permessi odbc.ini aggiornati."

# Prende la versione PHP installata
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "🔧 Versione PHP rilevata: $PHP_VERSION"

# Avvia PHP-FPM
echo "🚀 Avvio di PHP-FPM..."
if service php${PHP_VERSION}-fpm start; then
  echo "✅ php${PHP_VERSION}-fpm avviato con successo."
else
  echo "❌ Errore nell'avvio di php${PHP_VERSION}-fpm"
fi

# Avvia Nginx
echo "🚀 Avvio di NGINX..."
if service nginx start; then
  echo "✅ Nginx avviato con successo."
else
  echo "❌ Errore nell'avvio di nginx"
fi

# Avvia CUPS
echo "🚀 Avvio di CUPS..."
if service cups start; then
  echo "✅ CUPS avviato con successo."
else
  echo "❌ Errore nell'avvio di CUPS"
fi

# Avvia Dbus
echo "🚀 Avvio di Dbus..."
if service dbus start; then
  echo "✅ Dbus avviato con successo."
else
  echo "❌ Errore nell'avvio di Dbus"
fi

# aggiunge l’utente www-data al gruppo lp per permessi di cancellazione stampa
usermod -aG lp www-data

#nome utente e password per CUPS administration
echo "prt:prt2025" | chpasswd 

chown www-data:www-data /codice01
chown -R www-data:www-data /codice01/j01printserver/info01/backup
chown www-data:www-data /codice01/j01printserver/info01/j01printserver-config.xml

# Avvia Printer Watcher in background
echo "🚀 Avvio di printer_watcher.sh..."
/utility/printer_watcher.sh &

# Avvia j01printserver.jar in background
echo "🚀 Avvio di j01printserver.jar..."
cd /codice01/j01printserver/info01/
if java -jar /codice01/j01printserver/info01/j01printserver.jar &>> wrapper.log & then
  echo "✅ j01printserver.jar avviato con successo."
else
  echo "❌ Errore nell'avvio di j01printserver.jar"
fi

# Tieni il container attivo
echo "📦 Container in esecuzione. Premere CTRL+C per interrompere."



tail -f /dev/null
