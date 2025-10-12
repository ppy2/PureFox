include $(sort $(wildcard $(BR2_EXTERNAL_ext_tree_PATH)/package/*/*.mk))

# Create boot.img (zImage + DTB at offset) after kernel build for Ultra
define LINUX_CREATE_BOOT_IMG
	$(Q)cd $(LINUX_DIR)/arch/arm/boot && \
	dd if=/dev/zero of=boot.img bs=1 count=0 seek=4194304 && \
	dd if=zImage of=boot.img conv=notrunc && \
	dd if=dts/rv1106_pll.dtb of=boot.img bs=1 seek=3932160 conv=notrunc && \
	echo "Created boot.img for Ultra"
endef
LINUX_POST_BUILD_HOOKS += LINUX_CREATE_BOOT_IMG

