#!/bin/sh

cd buildroot
cat ../ext_tree/board/luckfox/config/uboot-env.txt  > ./board/luckfox-pico/common/uboot-env.txt
make BR2_EXTERNAL=../ext_tree luckfox_pico_max_defconfig
make

