################################################################################
#
# Buildroot package: librespot
#
################################################################################

#LIBRESPOT_VERSION = 0.6.0
#LIBRESPOT_SOURCE = v$(LIBRESPOT_VERSION).tar.gz
#LIBRESPOT_SITE = https://github.com/librespot-org/librespot/archive/refs/tags

#LIBRESPOT_SITE = https://github.com/librespot-org/librespot.git
#LIBRESPOT_SITE_METHOD = git
#LIBRESPOT_VERSION = dev

LIBRESPOT_LICENSE = MIT
LIBRESPOT_VERSION = $(call qstrip,$(or $(BR2_PACKAGE_LIBRESPOT_VERSION),ba3d501b08345aadf207d09b3a0713853228ba64))
LIBRESPOT_SITE = $(call github,librespot-org,librespot,$(LIBRESPOT_VERSION))
LIBRESPOT_CARGO_BUILD_OPTS = $(call qstrip,$(BR2_PACKAGE_LIBRESPOT_BUILD_OPTS))
LIBRESPOT_DEPENDENCIES += host-rust-bindgen

LIBRESPOT_DEPENDENCIES = alsa-lib  host-rust-bindgen host-pkgconf

$(eval $(cargo-package))
