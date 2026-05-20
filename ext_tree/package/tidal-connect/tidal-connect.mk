################################################################################
#
# tidal-connect
#
################################################################################

TIDAL_CONNECT_VERSION = 1.0.2
TIDAL_CONNECT_SITE = https://github.com/ppy2/tidal-connect-private
TIDAL_CONNECT_SITE_METHOD = git
TIDAL_CONNECT_DEPENDENCIES = host-squashfs

define TIDAL_CONNECT_BUILD_CMDS
	rm -rf $(@D)/sqfs_staging
	mkdir -p $(@D)/sqfs_staging
	cp -a $(@D)/lib/*.so* $(@D)/sqfs_staging/ 2>/dev/null || true
	$(TARGET_STRIP) $(@D)/sqfs_staging/*.so* 2>/dev/null || true
	cp $(@D)/bin/tidal_connect_application $(@D)/sqfs_staging/
	cp $(@D)/cert/T1.dat $(@D)/sqfs_staging/
	cp $(@D)/cert/T2.dat $(@D)/sqfs_staging/
	$(HOST_DIR)/bin/mksquashfs $(@D)/sqfs_staging $(@D)/tidal.sqfs -comp xz -b 1M -noappend
endef

define TIDAL_CONNECT_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0644 $(@D)/tidal.sqfs $(TARGET_DIR)/opt/tidal.sqfs
	$(INSTALL) -D -m 0755 $(@D)/bin/tc_volume $(TARGET_DIR)/usr/sbin/tc_volume
	$(INSTALL) -D -m 0755 $(@D)/S95tidal $(TARGET_DIR)/etc/rc.pure/S95tidal
endef

$(eval $(generic-package))
