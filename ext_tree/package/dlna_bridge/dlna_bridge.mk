################################################################################
#
# dlna_bridge
#
################################################################################

DLNA_BRIDGE_VERSION = 1.0
DLNA_BRIDGE_SITE_METHOD = local
DLNA_BRIDGE_SITE = $(TOPDIR)/../ext_tree/package/dlna_bridge
DLNA_BRIDGE_DEPENDENCIES = alsa-lib

define DLNA_BRIDGE_BUILD_CMDS
	$(TARGET_CC) $(TARGET_CFLAGS) $(TARGET_LDFLAGS) \
		-o $(@D)/dlna_bridge $(@D)/dlna_bridge.c \
		-lasound -lpthread
endef

define DLNA_BRIDGE_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/dlna_bridge $(TARGET_DIR)/opt/dlna_bridge
	$(INSTALL) -D -m 0755 $(@D)/S96dlna_bridge $(TARGET_DIR)/etc/rc.pure/S96dlna_bridge
	$(INSTALL) -D -m 0644 $(@D)/dlna_bridge.conf $(TARGET_DIR)/etc/dlna_bridge.conf
endef

$(eval $(generic-package))
