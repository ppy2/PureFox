################################################################################
#
# Buildroot package: librespot
#
################################################################################

LIBRESPOT_VERSION = $(call qstrip,$(or $(BR2_PACKAGE_LIBRESPOT_VERSION),ba3d501b08345aadf207d09b3a0713853228ba64))
LIBRESPOT_SITE = https://github.com/librespot-org/librespot.git
LIBRESPOT_SITE_METHOD = git

LIBRESPOT_LICENSE = MIT
LIBRESPOT_CARGO_BUILD_OPTS = $(call qstrip,$(BR2_PACKAGE_LIBRESPOT_BUILD_OPTS))
LIBRESPOT_DEPENDENCIES += host-rust-bindgen

LIBRESPOT_DEPENDENCIES = alsa-lib  host-rust-bindgen host-pkgconf

$(eval $(cargo-package))
