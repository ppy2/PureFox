#!/bin/sh
echo 512 > /sys/devices/platform/ffae0000.i2s/mclk_multiplier
sed -i 's/^MCLK=.*/MCLK=512/' /etc/i2s.conf
sed -i 's/MODE=ext/MODE=pll/' /etc/i2s.conf

fw_setenv bootcmd 'mmc dev ${devnum}; fatload mmc ${devnum}:5 ${kernel_addr_r} zImage; fatload mmc ${devnum}:5 ${fdt_addr_r} rv1106_pll.dtb; bootz ${kernel_addr_r} - ${fdt_addr_r}'

sync
#reboot -f

