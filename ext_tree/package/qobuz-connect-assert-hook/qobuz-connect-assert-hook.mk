################################################################################
#
# qobuz-connect-assert-hook
#
################################################################################

QOBUZ_CONNECT_ASSERT_HOOK_VERSION = 1.0
QOBUZ_CONNECT_ASSERT_HOOK_SITE = $(BR2_EXTERNAL_ext_tree_PATH)/package/qobuz-connect-assert-hook
QOBUZ_CONNECT_ASSERT_HOOK_SITE_METHOD = local
QOBUZ_CONNECT_ASSERT_HOOK_LICENSE = MIT

define QOBUZ_CONNECT_ASSERT_HOOK_BUILD_CMDS
	$(TARGET_CC) -shared -fPIC -o $(@D)/libassert_hook.so \
		$(@D)/assert_hook.c \
		$(TARGET_CFLAGS) -Wno-unused-result -ldl
endef

define QOBUZ_CONNECT_ASSERT_HOOK_INSTALL_TARGET_CMDS
	$(INSTALL) -d $(TARGET_DIR)/usr/lib
	$(INSTALL) -m 755 $(@D)/libassert_hook.so $(TARGET_DIR)/usr/lib/
endef

$(eval $(generic-package))
