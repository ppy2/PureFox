################################################################################
#
# aprenderer
#
################################################################################

#APRENDERER_SITE = http://albumplayer.ru/linux
#APRENDERER_SOURCE = aprenderer-arm32.tar.gz

define APSCREAM_INSTALL_TARGET_CMDS
#	mkdir -p $(TARGET_DIR)/usr/scream/
#	mkdir -p $(TARGET_DIR)/usr/aprenderer/upnp/
#	cp -r $(@D)/* -t $(TARGET_DIR)/usr/aprenderer/
	cp -r $(BR2_EXTERNAL_ext_tree_PATH)/package/apscream/apscream   $(TARGET_DIR)/usr/
endef

$(eval $(generic-package))

