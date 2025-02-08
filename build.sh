#!/bin/sh

git config --global http.version HTTP/1.1
git config --global http.postBuffer 157286400

cd buildroot
make BR2_EXTERNAL=../ext_tree luckfox_pico_max_defconfig
export FORCE_UNSAFE_CONFIGURE=1
apt-get install -y rsync build-essential cmake device-tree-compiler bc binutils

time make



