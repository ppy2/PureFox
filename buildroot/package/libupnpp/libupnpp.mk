################################################################################
#
# libupnpp
#
################################################################################

LIBUPNPP_VERSION = 1.0.2
LIBUPNPP_SOURCE = libupnpp-$(LIBUPNPP_VERSION).tar.gz
LIBUPNPP_SITE = https://www.lesbonscomptes.com/upmpdcli/downloads


LIBUPNPP_LICENSE = LGPL-2.1+
LIBUPNPP_LICENSE_FILES = COPYING
LIBUPNPP_INSTALL_STAGING = YES
LIBUPNPP_DEPENDENCIES = host-pkgconf expat libcurl libnpupnp

$(eval $(meson-package))
#$(eval $(autotools-package))

