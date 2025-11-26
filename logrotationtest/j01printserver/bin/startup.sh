#! /bin/sh
export SERVER_DIR=$(pwd)

export LOG_DIR=$SERVER_DIR/log
export LOG_FILE=$LOG_DIR/print_server.log
export JAVA_HOME=$(readlink -f $(which java))
export JAVA=$JAVA_HOME

mkdir -p $LOG_DIR
ORARIO=$(date "+%Y-%m-%d %H:%M:%S")

# rotazione del file di log
if [ -e $LOG_FILE ]; then
  mv $LOG_FILE $LOG_FILE.$(date "+%Y-%m-%d_%H%M%S")
  rm -R $LOG_DIR/print_server*
fi

echo "*******************************************" | tee -a $LOG_FILE
echo "     Avvio del server J01PrintServer       " | tee -a $LOG_FILE
echo "          $ORARIO                          " | tee -a $LOG_FILE

$JAVA -jar $SERVER_DIR/../info01/j01printserver.jar $SERVER_DIR/../info01/ >> $LOG_FILE 2>&1 &

echo $! > $SERVER_DIR/pid

echo "     servizio avviato - PID: $!"             | tee -a $LOG_FILE
echo "*******************************************" | tee -a $LOG_FILE

# Facendo cosi, quando lanciato come servizio, il file sh non esce appena lanciato il comando jar ma aspetta che finsica (ovvero mai), questo
# fatto cosi per evitare di cambiare il codice pre esistente e mettere i thread come non Daemon 
JAVA_PID=$!
wait $JAVA_PID
