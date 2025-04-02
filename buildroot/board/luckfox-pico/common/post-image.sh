#!/bin/sh

BOARD_DIR=$(dirname "$0")

mv -f $BINARIES_DIR/rootfs.ext2 $BINARIES_DIR/rootfs.img 2>/dev/null
#mv -f $BINARIES_DIR/uboot-env.bin $BINARIES_DIR/env.img 2>/dev/null
rm -f $BINARIES_DIR/*.dtb
rm -f $BINARIES_DIR/rootfs.ext4

#cp -f $BOARD_DIR/uboot-env.txt $BINARIES_DIR/uboot.env
#mkenvimage -s 32768 -o $BINARIES_DIR/env.img $BINARIES_DIR/uboot.env
#rm -f $BINARIES_DIR/uboot.env
