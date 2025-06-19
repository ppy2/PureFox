#!/bin/sh

/opt/2pll.sh
sleep 1
dd if=/dev/mmcblk0p1 of=/data/mmcblk0p1 bs=1M
dd if=/dev/mmcblk0p2 of=/data/mmcblk0p2 bs=1M
dd if=/dev/mmcblk0p3 of=/data/mmcblk0p3 bs=1M

rsync -alHWSzcv --delete --numeric-ids \
--exclude=/dev \
--exclude=/proc \
--exclude=/tmp \
--exclude=/run \
--exclude=/sys \
--exclude=/root/.bash_history  \
--exclude=/root/\.ssh/* \
--exclude=/var/tmp/systemd-private* \
--exclude=/var/log/* \
--exclude=/var/cache/upmpdcli/* \
--exclude=/etc/resolv.conf \
--exclude=/data/ethaddr.txt \
--exclude=/root/* \
--exclude=/etc/init.d/S95* \
--exclude=/usr/aplayer/*.dat \
--exclude=/usr/aprenderer/*.dat \
/  ppy@luckfox.puredsd.ru::luckfox_upload_ultra

rm -f /data/mmc*



