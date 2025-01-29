#!/bin/sh

git clone --depth 1 https://github.com/ologn-tech/buildroot-rv1106
cd buildroot-rv1106
cat ../ext_tree/board/luckfox/config/uboot-env.txt  > ./board/luckfox-pico/common/uboot-env.txt
make BR2_EXTERNAL=../ext_tree luckfox_pico_max_defconfig
make
