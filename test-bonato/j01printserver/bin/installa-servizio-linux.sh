#!/bin/bash

SERVICE_NAME="j01printserver"
SERVICE_FILE="/etc/systemd/system/$SERVICE_NAME.service"

# Create the systemd service file
sudo bash -c "cat > $SERVICE_FILE" <<EOF
[Unit]
Description=j01printserver
After=network.target

[Service]
ExecStart=/usr/bin/sh /codice01/j01printserver/bin/startup.sh
Restart=always

[Install]
WantedBy=multi-user.target
EOF

sudo chmod 644 $SERVICE_FILE
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
sudo systemctl enable $SERVICE_NAME
sudo systemctl start $SERVICE_NAME
sudo systemctl status $SERVICE_NAME --no-pager
