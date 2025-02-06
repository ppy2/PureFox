#!/bin/sh

cd buildroot
make BR2_EXTERNAL=../ext_tree luckfox_pico_max_defconfig
export FORCE_UNSAFE_CONFIGURE=1
apt-get install -y build-essential cmake clang libclang-dev libc6-dev-armhf-cross device-tree-compiler
make



