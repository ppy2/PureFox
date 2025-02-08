################################################################################
#
# Raspotify
#
################################################################################

RASPOTIFY_VERSION = 0.46.1
RASPOTIFY_SOURCE = raspotify_$(RASPOTIFY_VERSION).librespot.v0.6.0-383a6f6_armhf.deb
RASPOTIFY_SITE = https://github.com/dtcooper/raspotify/releases/download/$(RASPOTIFY_VERSION)
RASPOTIFY_SITE_METHOD = wget

RASPOTIFY_DEPENDENCIES = alsa-lib

define RASPOTIFY_EXTRACT_CMDS
    mkdir -p $(@D)/extract
    cp $(DL_DIR)/raspotify/$(RASPOTIFY_SOURCE) $(@D)/
    cd $(@D)/extract && ar x $(@D)/$(RASPOTIFY_SOURCE)
    tar -xvf $(@D)/extract/data.tar.* -C $(@D)
    rm -f $(@D)/$(RASPOTIFY_SOURCE)
    rm -f -r $(@D)/extract
endef

define RASPOTIFY_INSTALL_TARGET_CMDS
    cp -r $(@D)/etc/raspotify  $(TARGET_DIR)/etc/
    cp -r $(@D)/usr/bin  $(TARGET_DIR)/usr/
endef

$(eval $(generic-package))