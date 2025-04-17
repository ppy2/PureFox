#!/bin/sh

fw_setenv bootcmd 'mmc dev ${devnum}; fatload mmc ${devnum}:5 ${kernel_addr_r} zImage; fatload mmc ${devnum}:5 ${fdt_addr_r} rv1106_usb.dtb; bootz ${kernel_addr_r} - ${fdt_addr_r}'

