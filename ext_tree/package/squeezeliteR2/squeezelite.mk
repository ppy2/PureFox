################################################################################
#
# squeezeliteR2
#
################################################################################

define SQUEEZELITER2_INSTALL_TARGET_CMDS
        cp $(BR2_EXTERNAL_ext_tree_PATH)/package/squeezeliteR2/squeezelite $(TARGET_DIR)/usr/bin/
endef

$(eval $(generic-package))
