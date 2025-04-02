#!/bin/sh

sed -i 's/007c003c/007c001c/' /etc/init.d/S94ioi2s
sed -i 's/MODE=pll/MODE=ext/' /etc/i2s.conf
sed -i 's/MCLK=1024/MCLK=512/' /etc/i2s.conf

fw_setenv bootcmd 'mmc dev ${devnum}; fatload mmc ${devnum}:5 ${kernel_addr_r} zImage; fatload mmc ${devnum}:5 ${fdt_addr_r} rv1106_512_ext.dtb; bootz ${kernel_addr_r} - ${fdt_addr_r}'
