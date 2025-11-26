#! /bin/sh
. $SERVER_DIR/bin/setenv.sh

ORARIO=$(date "+%Y-%m-%d %H:%M:%S")
echo "###########################################" | tee -a $LOG_FILE
echo "     Fermo del server J01PrintServer       " | tee -a $LOG_FILE
echo "          $ORARIO                          " | tee -a $LOG_FILE

if [ -e $SERVER_DIR/bin/pid ]; then 
  kill -9 $(cat $SERVER_DIR/bin/pid)
  rm $SERVER_DIR/bin/pid
else
  RED='\033[93;41m'
  NC='\033[0m'
  printf "${RED}  ATTENZIONE  ${NC}\n"
  echo "  ATTENZIONE"                                   >> $LOG_FILE
  echo "    PID file non trovato ($SERVER_DIR/bin/pid)" | tee -a $LOG_FILE
  echo "    non posso fermare il servizio"              | tee -a $LOG_FILE
fi
echo "###########################################" | tee -a $LOG_FILE

