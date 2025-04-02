#!/bin/sh

sshpass -p 'luckfox' rsync -av --delete --size-only \
--exclude=.git \
--exclude=/dev \
--exclude=/proc \
--exclude=/sys \
--exclude=/mnt \
--exclude=/root \
--exclude=/tmp \
--exclude=/etc/asound.conf \
--filter='protect /usr/aprenderer/*.dat' \
--filter='protect /usr/aplayer/*.dat' \
--filter='protect /data/ethaddr.txt' \
--filter='protect /etc/resolv.conf' \
--filter='protect /etc/init.d/S95*' \
luckfox@luckfox.puredsd.ru::luckfox_ultra / || exit 1
sleep 1
sync

dd if=/data/mmcblk0p1 of=/dev/mmcblk0p1 bs=1M
dd if=/data/mmcblk0p2 of=/dev/mmcblk0p2 bs=1M
dd if=/data/mmcblk0p3 of=/dev/mmcblk0p3 bs=1M
sync

rm -f /data/*.img
sync








