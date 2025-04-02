#!/bin/sh

apt-get install -y git rsync build-essential cmake device-tree-compiler bc binutils libncurses-dev clang mtools
git config --global http.version HTTP/1.1
git config --global http.postBuffer 157286400
cd buildroot
make BR2_EXTERNAL=../ext_tree luckfox_pico_max_defconfig
export FORCE_UNSAFE_CONFIGURE=1
export LIBCLANG_PATH=/usr/lib/llvm-14/lib
export CLANG_PATH=/usr/bin/clang
export BINDGEN_EXTRA_CLANG_ARGS="--sysroot=`pwd`/output/host/arm-buildroot-linux-gnueabihf/sysroot"
make




