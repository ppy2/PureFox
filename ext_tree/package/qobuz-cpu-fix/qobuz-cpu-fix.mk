################################################################################
#
# qobuz-cpu-fix
#
################################################################################

QOBUZ_CPU_FIX_VERSION = 1.0
QOBUZ_CPU_FIX_SITE_METHOD = local
QOBUZ_CPU_FIX_SITE = $(TOPDIR)/../ext_tree/package/qobuz-cpu-fix/src
QOBUZ_CPU_FIX_LICENSE = GPL-2.0+
QOBUZ_CPU_FIX_LICENSE_FILES = LICENSE

define QOBUZ_CPU_FIX_BUILD_CMDS
    $(TARGET_CC) $(TARGET_CFLAGS) $(TARGET_LDFLAGS) \
	-fPIC -Wall -O2 -shared \
	-march=armv7-a -mfpu=neon-vfpv4 -mfloat-abi=hard \
	-pthread \
	-o $(@D)/libqobuz_cpu_fix.so \
	$(@D)/qobuz_cpu_fix.c \
	-ldl
endef

define QOBUZ_CPU_FIX_INSTALL_TARGET_CMDS
    $(INSTALL) -d $(TARGET_DIR)/opt/qobuz-connect
    $(INSTALL) -m 755 $(@D)/libqobuz_cpu_fix.so $(TARGET_DIR)/opt/qobuz-connect/
endef

$(eval $(generic-package))