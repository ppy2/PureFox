################################################################################
#
# librespot
#
################################################################################

LIBRESPOT_VERSION = 0.6.0
LIBRESPOT_SOURCE = v$(LIBRESPOT_VERSION).tar.gz
LIBRESPOT_SITE = https://github.com/librespot-org/librespot/archive/refs/tags
#LIBRESPOT_CARGO_BUILD_OPTS =  --no-default-features --features "alsa-backend"
#LIBRESPOT_CARGO_BUILD_OPTS =  --no-default-features --features "alsa-backend"

LIBRESPOT_DEPENDENCIES =  alsa-lib


$(eval $(cargo-package))
