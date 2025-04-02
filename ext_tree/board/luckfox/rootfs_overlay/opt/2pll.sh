#!/bin/sh

sed -i 's/007c001c/007c003c/' /etc/init.d/S94ioi2s
sed -i 's/MODE=ext/MODE=pll/' /etc/i2s.conf
sed -i 's/MCLK=512/MCLK=1024/' /etc/i2s.conf

fw_setenv bootcmd 'mmc dev ${devnum}; fatload mmc ${devnum}:5 ${kernel_addr_r} zImage; fatload mmc ${devnum}:5 ${fdt_addr_r} rv1106_pll.dtb; bootz ${kernel_addr_r} - ${fdt_addr_r}'

