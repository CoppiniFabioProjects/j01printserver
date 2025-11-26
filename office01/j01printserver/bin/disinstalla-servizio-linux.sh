#!/bin/bash

SERVICE_NAME="j01printserver"
SERVICE_FILE="/etc/systemd/system/$SERVICE_NAME.service"

sudo rm $SERVICE_FILE
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
