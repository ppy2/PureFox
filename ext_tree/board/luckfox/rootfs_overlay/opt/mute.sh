#!/bin/bash

######## additional GPIO mute pin for DSC2 ###################
echo 1 > /sys/class/gpio/gpio49/value
#############################################################

MIXER=`amixer 2>/dev/null| awk -F "'" 'NR==1 {print $2; exit}'`
/usr/bin/amixer set "$MIXER" mute

