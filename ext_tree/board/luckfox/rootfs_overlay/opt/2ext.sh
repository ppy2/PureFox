#!/bin/sh

sed -i 's/007c003c/007с001с/' /etc/init.d/S94ioi2s
sed -i 's/MODE=pll/MODE=ext/' /etc/i2s.conf

flash_erase /dev/mtd3 0 0
mtd_debug write /dev/mtd3 0 4194304 /data/boot/ext_boot.img

sync
#reboot -f



