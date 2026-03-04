#!/bin/sh
# dlna_disable.sh — disable ALSA loopback bridge to DLNA

/etc/init.d/S96dlna_bridge stop 2>/dev/null || true
rm -f /etc/init.d/S96dlna_bridge

# Revert ALSA to standard config based on current SUBMODE
SUBMODE=$(grep '^SUBMODE=' /etc/i2s.conf | cut -d= -f2 | tr -d '[:space:]')
CONF="std"
[ "$SUBMODE" = "lr" ]  && CONF="lr"
[ "$SUBMODE" = "plr" ] && CONF="plr"
[ "$SUBMODE" = "8ch" ] && CONF="8ch"

rm -f /etc/asound.conf
ln -sf /etc/asound.$CONF /etc/asound.conf

echo "disabled" > /etc/dlna_bridge.state
echo "DLNA bridge disabled"
