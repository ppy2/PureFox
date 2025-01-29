################################################################################
#
# roonready
#
################################################################################

define ROONREADY_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(BR2_EXTERNAL_ext_tree_PATH)/package/roonready/raat_app  $(TARGET_DIR)/opt/RoonReady/raat_app
	$(INSTALL) -D -m 0755 $(BR2_EXTERNAL_ext_tree_PATH)/package/roonready/raatool  $(TARGET_DIR)/opt/RoonReady/raatool
endef

$(eval $(generic-package))
