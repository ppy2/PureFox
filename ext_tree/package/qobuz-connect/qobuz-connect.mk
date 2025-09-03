################################################################################
#
# qobuz-connect
#
################################################################################

QOBUZ_CONNECT_VERSION = 1.0.0
QOBUZ_CONNECT_SITE_METHOD = local
QOBUZ_CONNECT_SITE = /opt/qobuz-connect-sdk-purefox-armhf-v$(QOBUZ_CONNECT_VERSION)
QOBUZ_CONNECT_LICENSE = Proprietary
QOBUZ_CONNECT_DEPENDENCIES = alsa-lib avahi civetweb openssl


define QOBUZ_CONNECT_BUILD_CMDS
	mkdir -p $(@D)/build
	cd $(@D)/build && \
	PKG_CONFIG_PATH=$(STAGING_DIR)/usr/lib/pkgconfig:$(STAGING_DIR)/usr/share/pkgconfig \
	/usr/bin/cmake \
		-DCMAKE_TOOLCHAIN_FILE=$(HOST_DIR)/share/buildroot/toolchainfile.cmake \
		-DCMAKE_INSTALL_PREFIX=/usr \
		-DCMAKE_BUILD_TYPE=Release \
		-DCMAKE_C_FLAGS="-O2 -DDISABLE_LOGGING" \
		-DCMAKE_PREFIX_PATH=$(STAGING_DIR)/usr \
		../sample_console_app && \
	$(MAKE)
endef

define QOBUZ_CONNECT_INSTALL_TARGET_CMDS
	$(INSTALL) -d $(TARGET_DIR)/opt/qobuz-connect
	$(INSTALL) -m 755 $(@D)/build/qobuz_connect_sample_app $(TARGET_DIR)/opt/qobuz-connect/qobuz-connect
	$(INSTALL) -d $(TARGET_DIR)/usr/lib
	$(INSTALL) -m 755 $(QOBUZ_CONNECT_SITE)/sdk/lib/libqobuz_connect.so.1.0.0 $(TARGET_DIR)/usr/lib/
	ln -sf libqobuz_connect.so.1.0.0 $(TARGET_DIR)/usr/lib/libqobuz_connect.so
	$(INSTALL) -m 755 $(QOBUZ_CONNECT_SITE)/third_party/libcjson/lib/libcjson.so.1.7.13 $(TARGET_DIR)/usr/lib/
	ln -sf libcjson.so.1.7.13 $(TARGET_DIR)/usr/lib/libcjson.so.1
	ln -sf libcjson.so.1 $(TARGET_DIR)/usr/lib/libcjson.so
	$(INSTALL) -m 755 $(QOBUZ_CONNECT_SITE)/third_party/libuv/lib/libuv.so.1.0.0 $(TARGET_DIR)/usr/lib/
	ln -sf libuv.so.1.0.0 $(TARGET_DIR)/usr/lib/libuv.so.1
	ln -sf libuv.so.1 $(TARGET_DIR)/usr/lib/libuv.so
endef

$(eval $(generic-package))