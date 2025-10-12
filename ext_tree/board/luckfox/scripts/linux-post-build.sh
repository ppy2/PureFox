#!/bin/sh
# Post-build hook for Linux kernel
# Creates boot.img from zImage for eMMC boot partition

KERNEL_DIR=$1

# Create boot.img as a copy of zImage
cp -f ${KERNEL_DIR}/arch/arm/boot/zImage ${KERNEL_DIR}/arch/arm/boot/boot.img

echo "Created boot.img from zImage"
