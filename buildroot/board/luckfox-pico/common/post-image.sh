#!/bin/sh

# Copy tidal.sqfs to images directory for flashing
if [ -f "/tmp/tidal.sqfs" ]; then
    cp /tmp/tidal.sqfs $BINARIES_DIR/
fi

mv -f $BINARIES_DIR/rootfs.ubi $BINARIES_DIR/rootfs.img 2>/dev/null
mv -f $BINARIES_DIR/uboot-env.bin $BINARIES_DIR/env.img 2>/dev/null
rm -f $BINARIES_DIR/*.dtb
rm -f $BINARIES_DIR/rootfs.ubifs
