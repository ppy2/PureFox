################################################################################
#
# Buildroot package: librespot
#
################################################################################

#LIBRESPOT_VERSION = 0.6.0
#LIBRESPOT_SOURCE = v$(LIBRESPOT_VERSION).tar.gz
#LIBRESPOT_SITE = https://github.com/librespot-org/librespot/archive/refs/tags

LIBRESPOT_SITE = https://github.com/librespot-org/librespot.git
LIBRESPOT_SITE_METHOD = git
LIBRESPOT_VERSION = dev


LIBRESPOT_DEPENDENCIES = alsa-lib  host-rust-bindgen host-pkgconf

$(eval $(cargo-package))
