################################################################################
#
# snd-screamalsa — virtual ALSA sound card streaming audio via Scream protocol
#
################################################################################

SND_SCREAMALSA_VERSION = 1.0.0
SND_SCREAMALSA_SITE = $(TOPDIR)/../ext_tree/package/snd-screamalsa
SND_SCREAMALSA_SITE_METHOD = local
SND_SCREAMALSA_LICENSE = GPL-2.0

SND_SCREAMALSA_MODULE_SUBDIRS = .

define SND_SCREAMALSA_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/S97screamalsa \
		$(TARGET_DIR)/etc/rc.pure/S97screamalsa
	$(INSTALL) -D -m 0644 $(@D)/scream.conf \
		$(TARGET_DIR)/etc/scream.conf
	$(INSTALL) -D -m 0644 $(@D)/snd-screamalsa.conf \
		$(TARGET_DIR)/etc/modprobe.d/snd-screamalsa.conf
endef

$(eval $(kernel-module))
$(eval $(generic-package))
