################################################################################
#
# alsa-wrapper
#
################################################################################

ALSA_WRAPPER_VERSION = 1.0
ALSA_WRAPPER_SITE_METHOD = local
ALSA_WRAPPER_SITE = $(TOPDIR)/../ext_tree/package/alsa-wrapper/src
ALSA_WRAPPER_LICENSE = GPL-2.0+
ALSA_WRAPPER_LICENSE_FILES = LICENSE
ALSA_WRAPPER_DEPENDENCIES = alsa-lib

define ALSA_WRAPPER_BUILD_CMDS
    $(TARGET_CC) $(TARGET_CFLAGS) $(TARGET_LDFLAGS) \
	-fPIC -Wall -O2 -shared \
	-march=armv7-a -mfpu=neon-vfpv4 -mfloat-abi=hard \
	-pthread \
	-o $(@D)/libfake_alsa.so \
	$(@D)/alsa_wrapper.c \
	-lasound -lpthread -lm -ldl
endef

define ALSA_WRAPPER_INSTALL_TARGET_CMDS
    $(INSTALL) -d $(TARGET_DIR)/usr/lib
    $(INSTALL) -m 755 $(@D)/libfake_alsa.so $(TARGET_DIR)/usr/lib/
endef

$(eval $(generic-package))