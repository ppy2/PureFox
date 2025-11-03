#!/bin/sh

SCRIPT_PATH="/opt/update.sh"
SCRIPT_NEW="/tmp/update.sh.new"
UPDATE_SERVER="luckfox@luckfox.puredsd.ru::luckfox_ultra/opt/update.sh"

# Check if script update is needed (only on first run, not on restart)
if [ "$UPDATE_SELF_DONE" != "1" ]; then
    echo "Checking for update.sh updates..."

    # Download new version of update.sh to temp location
    sshpass -p 'luckfox' rsync -av "$UPDATE_SERVER" "$SCRIPT_NEW" 2>/dev/null

    if [ $? -eq 0 ] && [ -f "$SCRIPT_NEW" ]; then
        # Compare checksums
        OLD_MD5=$(md5sum "$SCRIPT_PATH" | awk '{print $1}')
        NEW_MD5=$(md5sum "$SCRIPT_NEW" | awk '{print $1}')

        if [ "$OLD_MD5" != "$NEW_MD5" ]; then
            echo "New version of update.sh found, updating..."
            chmod +x "$SCRIPT_NEW"
            cp "$SCRIPT_NEW" "$SCRIPT_PATH"
            rm -f "$SCRIPT_NEW"

            echo "Restarting with new update.sh..."
            export UPDATE_SELF_DONE=1
            exec "$SCRIPT_PATH" "$@"
        else
            echo "update.sh is up to date"
            rm -f "$SCRIPT_NEW"
        fi
    else
        echo "Could not check for updates, continuing..."
    fi
fi

killall -9 status_monitor
/etc/init.d/S95* stop

sshpass -p 'luckfox' rsync -acv --delete-before --one-file-system \
--exclude=.git \
--exclude=/dev \
--exclude=/proc \
--exclude=/sys \
--exclude=/mnt \
--exclude=/root \
--exclude=/tmp \
--exclude=/etc/asound.conf \
--filter='protect /usr/aprenderer/*.dat' \
--filter='protect /usr/aplayer/*.dat' \
--filter='protect /data/ethaddr.txt' \
--filter='protect /etc/resolv.conf' \
--filter='protect /etc/init.d/S95*' \
luckfox@luckfox.puredsd.ru::luckfox_ultra / || exit 1
sleep 1
sync

dd if=/data/mmcblk0p1 of=/dev/mmcblk0p1 bs=1M
dd if=/data/mmcblk0p2 of=/dev/mmcblk0p2 bs=1M
dd if=/data/mmcblk0p3 of=/dev/mmcblk0p3 bs=1M
sync

rm -f /data/*.img
sync








