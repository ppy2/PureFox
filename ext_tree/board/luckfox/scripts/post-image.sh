#!/bin/sh

BOARD_DIR=$(dirname "$0")
LINUX_DIR=$PWD/output/build/linux-`grep BR2_LINUX_KERNEL_VERSION $BR2_CONFIG |cut  -d= -f2 |tr -d \"`

cp -f $BR2_EXTERNAL_ext_tree_PATH/board/luckfox/config/uboot-env.txt $BINARIES_DIR/uboot.env
mkenvimage -s 32768 -o $BINARIES_DIR/env.img $BINARIES_DIR/uboot.env

############### rootfs.img ###########################
mkdir -p $BINARIES_DIR/rootfs && tar -xf $BINARIES_DIR/rootfs.tar -C $BINARIES_DIR/rootfs
echo y | mkfs.ext4 -d $BINARIES_DIR/rootfs -r 1 -N 0 -m 0 -L "" -O ^64bit,^huge_file $BINARIES_DIR/rootfs.img "6144M"
resize2fs -M $BINARIES_DIR/rootfs.img
e2fsck -fy $BINARIES_DIR/rootfs.img
tune2fs -m 0 $BINARIES_DIR/rootfs.img
resize2fs -M $BINARIES_DIR/rootfs.img
rm -fr $BINARIES_DIR/rootfs

############## userdata.img ##########################
echo y | mkfs.ext4 -r 1 -N 0 -m 0 -L "" -O ^64bit,^huge_file $BINARIES_DIR/userdata.img "256M"
resize2fs -M $BINARIES_DIR/userdata.img
e2fsck -fy $BINARIES_DIR/userdata.img
tune2fs -m 0 $BINARIES_DIR/userdata.img
resize2fs -M $BINARIES_DIR/userdata.img

############## oem.img ###############################
dd if=/dev/zero of=$BINARIES_DIR/oem.img bs=1M count=10
mkfs.fat $BINARIES_DIR/oem.img
mcopy -i $BINARIES_DIR/oem.img $LINUX_DIR/arch/arm/boot/dts/rv1106_usb.dtb ::/
mcopy -i $BINARIES_DIR/oem.img $LINUX_DIR/arch/arm/boot/dts/rv1106_pll.dtb ::/
mcopy -i $BINARIES_DIR/oem.img $LINUX_DIR/arch/arm/boot/dts/rv1106_ext.dtb ::/
mcopy -i $BINARIES_DIR/oem.img $LINUX_DIR/arch/arm/boot/dts/rv1106_512_ext.dtb ::/
mcopy -i $BINARIES_DIR/oem.img $LINUX_DIR/arch/arm/boot/zImage ::/

rm -f $BINARIES_DIR/uboot-env.bin
rm -f $BINARIES_DIR/uboot.env
rm -f $BINARIES_DIR/*.dtb
rm -f $BINARIES_DIR/rootfs.tar


