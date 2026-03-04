#!/bin/sh

# USBtoI2S mode: I2S audio output + USB gadget (UAC2)
MODE_FILE="/etc/usb_to_i2s.state"
MODULES_DIR="/lib/modules"

# Stop all running players and remove symlinks
sh -c '/etc/init.d/S95* stop' 2>/dev/null || true
rm -f /etc/init.d/S95*
ln -sf  /etc/rc.pure/S98uac2 /etc/init.d/S98uac2
ln -sf  /etc/rc.pure/S95uac2_router /etc/init.d/S99uac2_router

# Set mode file
echo "enabled" > "$MODE_FILE"

# 1. Switch ALSA to I2S (8-channel TDM mode for USBtoI2S)
rm -f /etc/asound.conf
ln -sf /etc/asound.8ch /etc/asound.conf
sed -i 's/^SUBMODE=.*$/SUBMODE=8ch/' /etc/i2s.conf
echo I2S > /etc/output

# 2. Switch USB to gadget mode
echo "Switching USB to gadget mode..."

rmmod dwc3 2>/dev/null || true
sleep 0.2

# Load dwc3_gadget.ko from custom location
insmod $MODULES_DIR/dwc3_gadget.ko 2>/dev/null || true
sleep 1.0

# Start UAC2 gadget and router SYNCHRONOUSLY (critical services)
/etc/init.d/S98uac2 restart
/etc/init.d/S99uac2_router start

# 3. Restart status monitor in BACKGROUND (can wait for services to stabilize)
/etc/init.d/S01statusmonitor restart >/dev/null 2>&1 &
sync

echo "USBtoI2S mode enabled"
exit 0
