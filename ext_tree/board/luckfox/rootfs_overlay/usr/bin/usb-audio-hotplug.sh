#!/bin/sh

ACTION="$1"
DEVICE="$2"

# Debug logging
echo "mdev hotplug: ACTION=$ACTION DEVICE=$DEVICE MDEV=$MDEV" | logger -t usb-audio

# Skip internal I2S card
[ "$DEVICE" = "card0" ] && exit 0

case "$ACTION" in
    add)
        # USB audio device connected
        echo "USB audio device connected: $DEVICE" | logger -t usb-audio
        sleep 1
        # Restart audio services
        for service in /etc/init.d/S95*; do
            [ -x "$service" ] && "$service" restart &
        done
        ;;
    remove)
        # USB audio device disconnected  
        echo "USB audio device disconnected: $DEVICE" | logger -t usb-audio
        ;;
esac