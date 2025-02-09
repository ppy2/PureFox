#!/bin/sh


mtd_debug read /dev/mtd0 0 262144 /data/env.img || exit
sleep 1
mtd_debug read /dev/mtd1 0 262144 /data/idblock.img || exit
sleep 1
mtd_debug read /dev/mtd2 0 524288 /data/uboot.img || exit
sleep 1
mtd_debug read /dev/mtd3 0 4194304 /data/boot.img || exit
sleep 1

rsync -axlHWSzv --delete --numeric-ids \
--exclude=/root/.bash_history  \
--exclude=/var/lib/squeezeboxserver \
--exclude=/root/\.ssh/known_hosts \
--exclude=/var/tmp/systemd-private* \
--exclude=/var/log/* \
--exclude=/var/cache/upmpdcli/* \
--exclude=/etc/resolv.conf \
--exclude=/root/.local/share/mc/filepos \
--exclude=/root/.local/share/mc/history \
--exclude=/usr/scream/apscream.log \
--exclude=/data/ethaddr.txt \
--exclude=/root/.ash_history \
--exclude=/root/.cache/* \
--exclude=/root/.local/* \
--exclude=/root/.ssh/ \
--exclude=/etc/init.d/S95* \
/  ppy@luckfox.puredsd.ru::luckfox_upload

rm -f /data/*.img



