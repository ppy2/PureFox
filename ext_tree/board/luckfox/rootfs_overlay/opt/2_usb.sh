#!/bin/sh

rm -f /etc/asound.conf
ln -s /etc/asound.usb /etc/asound.conf
echo USB > /etc/output
/etc/init.d/S01statusmonitor restart
sync
sh -c '/etc/init.d/S95* restart' 2>/dev/null || true
