#!/bin/sh

rm -f /etc/asound.conf
ln -s /etc/asound.std /etc/asound.conf
sed -i 's/^SUBMODE=.*$/SUBMODE=std/' /etc/i2s.conf
echo I2S > /etc/output
/etc/init.d/S01statusmonitor restart
sync
sh -c '/etc/init.d/S95* restart' 2>/dev/null || true
