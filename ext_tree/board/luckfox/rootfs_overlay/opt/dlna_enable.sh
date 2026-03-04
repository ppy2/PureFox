#!/bin/sh
# dlna_enable.sh — enable ALSA loopback bridge to DLNA

# Force SUBMODE=std (bridge only supports stereo)
sed -i 's/^SUBMODE=.*$/SUBMODE=std/' /etc/i2s.conf

# Switch ALSA config to bridge mode
rm -f /etc/asound.conf
ln -sf /etc/asound.bridge /etc/asound.conf

# Load loopback module
modprobe snd-aloop pcm_substreams=2 2>/dev/null || true

# Start bridge daemon
ln -sf /etc/rc.pure/S96dlna_bridge /etc/init.d/S96dlna_bridge
/etc/init.d/S96dlna_bridge start

echo "enabled" > /etc/dlna_bridge.state
echo "DLNA bridge enabled"
