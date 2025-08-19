#!/bin/sh

rm -f /etc/asound.conf
cp /etc/asound.std /etc/asound.conf
sed -i 's/card 0/card 1/' /etc/asound.conf
echo USB > /etc/output
sync
if ls /etc/init.d/S95* >/dev/null 2>&1; then
    /etc/init.d/S95* restart
else
    echo "Service S95* not found, skipping restart"
fi
