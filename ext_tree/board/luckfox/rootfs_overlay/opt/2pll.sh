#!/bin/sh

sed -i 's/007c001c/007c003c/' /etc/init.d/S94ioi2s
sed -i 's/MODE=ext/MODE=pll/' /etc/i2s.conf
sed -i 's/MCLK=512/MCLK=1024/' /etc/i2s.conf


flash_erase /dev/mtd3 0 0
mtd_debug write /dev/mtd3 0 4194304 /data/boot/pll_boot.img

sync
#reboot -f
