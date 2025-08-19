S2MONO_VERSION = 1.0
S2MONO_SITE = $(BR2_EXTERNAL_ext_tree_PATH)/package/s2mono
S2MONO_SITE_METHOD=local
S2MONO_DEPENDENCIES = host-pkgconf alsa-lib


define S2MONO_INSTALL_TARGET_CMDS
	mkdir -p $(TARGET_DIR)/usr/lib/alsa-lib
	$(INSTALL) -D -m 755  $(@D)/*.so $(TARGET_DIR)/usr/lib/alsa-lib
endef

define S2MONO_BUILD_CMDS
	$(MAKE) $(TARGET_CONFIGURE_OPTS) -C $(@D) all
endef


$(eval $(generic-package))
