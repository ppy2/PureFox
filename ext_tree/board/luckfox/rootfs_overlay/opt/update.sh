#!/bin/sh

sshpass -p 'luckfox' rsync -av --delete --size-only \
--exclude=.git \
--exclude=/dev \
--exclude=/proc \
--exclude=/sys \
--exclude=/mnt \
--exclude=/root \
--exclude=/etc/asound.conf \
--filter='protect /usr/aprenderer/*.dat' \
--filter='protect /usr/aplayer/*.dat' \
--filter='protect /data/ethaddr.txt' \
--filter='protect /etc/resolv.conf' \
--filter='protect /etc/init.d/S95*' \
luckfox@luckfox.puredsd.ru::luckfox / || exit 1


flash_erase /dev/mtd0 0 0 || exit 1
mtd_debug write /dev/mtd0 0 262144 /data/env.img || exit 1
sleep 2
flash_erase /dev/mtd1 0 0 || exit 1
mtd_debug write /dev/mtd1 0 262144 /data/idblock.img || exit 1
sleep 2
flash_erase /dev/mtd2 0 0 || exit 1
mtd_debug write /dev/mtd2 0 524288 /data/uboot.img || exit 1
sleep 2
flash_erase /dev/mtd3 0 0 || exit 1
mtd_debug write /dev/mtd3 0 4194304 /data/boot.img || exit 1
sleep 2


rm -f /data/*.img
sync








