#\!/bin/sh

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
        # Restart only the currently active audio service
        for service in /etc/rc.pure/S95*; do
            if [ -x "$service" ]; then
                echo "Restarting active audio service: $(basename $service)" | logger -t usb-audio
                "$service" restart &
            fi
        done
        ;;
    remove)
        # USB audio device disconnected  
        echo "USB audio device disconnected: $DEVICE" | logger -t usb-audio
        ;;
esac
