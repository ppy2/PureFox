# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PureFox 2.0 is a custom Buildroot-based Linux distribution for LuckFox Pico MAX (RV1106/RK1106 SoC), implementing a high-end USB Audio Class 2 device with I2S output. Supports PCM up to 768 kHz and native DSD64-DSD512.

**Hardware:** LuckFox Pico MAX, Rockchip RV1106 (ARM Cortex-A7), 64MB RAM
**Kernel:** linux-rockchip rk-6.1-rkr6.1 (heavily patched)
**Target:** Audiophile USB DAC with multiple streaming protocols

## Build Commands

```bash
# Full build from scratch
./build.sh

# Buildroot commands (run from buildroot/ directory)
cd buildroot
make BR2_EXTERNAL=../ext_tree luckfox_pico_max_defconfig
make

# Rebuild specific package
make <package>-rebuild

# Clean build
make clean

# Configuration
make menuconfig
make linux-menuconfig
```

## Kernel Patch Workflow

Kernel sources are NOT in this repo. Patches are generated from comparison directories in `/opt/`:

**Source directories:**
- Original: `/opt/linux-rockchip-rk-6.1-rkr6.1_orig`
- Modified: `/opt/linux-rockchip-rk-6.1-rkr6.1_modify`

**Workflow:**
1. Edit kernel files in buildroot build tree: `/opt/PureFox_2.0/buildroot/output/build/linux-custom/`
2. Copy changed files to `_modify` directory
3. Generate patch with date-stamped filename:
   ```bash
   cd /opt
   diff -Naur --no-dereference \
     linux-rockchip-rk-6.1-rkr6.1_orig \
     linux-rockchip-rk-6.1-rkr6.1_modify \
     > linux_rv1106_rk6.1_YYYYMMDD.patch
   ```
4. Copy to buildroot with canonical name:
   ```bash
   cp /opt/linux_rv1106_rk6.1_YYYYMMDD.patch \
      /opt/PureFox_2.0/ext_tree/patches/linux_rv1106.patch
   ```

## Project Structure

```
/opt/PureFox_2.0/
├── buildroot/              # Buildroot source tree
│   └── output/
│       ├── build/          # Package build directories
│       ├── images/         # Final images (boot.img, rootfs.img)
│       └── target/         # Root filesystem staging
├── ext_tree/               # External tree (custom packages & config)
│   ├── board/luckfox/
│   │   ├── dts_max/        # Device tree sources (MAX variant)
│   │   ├── rootfs_overlay/ # Files copied to target rootfs
│   │   │   ├── etc/
│   │   │   │   ├── rc.pure/     # Service init scripts (S95*)
│   │   │   │   └── init.d/      # System init scripts
│   │   │   ├── opt/             # Helper scripts (2_*.sh for I2S modes)
│   │   │   └── var/www/         # Web interface (PHP)
│   │   └── scripts/
│   │       └── post-build.sh    # Post-build hook
│   ├── configs/            # Board defconfigs
│   ├── package/            # Custom packages
│   │   ├── uac2_router/    # USB→I2S audio router (C)
│   │   ├── librespot/      # Spotify Connect (Rust)
│   │   ├── buffer_daemon/  # ALSA buffer management
│   │   └── [others]/       # Tidal, Qobuz, Roon, etc.
│   └── patches/
│       └── linux_rv1106.patch  # Kernel patch (UAC2, I2S, DSD)
├── build.sh                # Main build script
└── sync_max_to_ultra.sh    # Sync MAX→Ultra branches
```

## Key Architecture

### USB Audio (UAC2)
- **Gadget mode:** RV1106 acts as USB audio device
- **Kernel driver:** `f_uac2.c` + `u_audio.c` (heavily modified)
- **Adaptive feedback:** PI controller in kernel, target = buffer_size/128
- **Rates:** 44.1-768 kHz PCM, DSD64-DSD512 (as raw PCM at LRCK rate)
- **Sysfs interface:** `/sys/class/u_audio/uac_card*/` (rate, format, channels, feedback)

### UAC2 Router (`uac2_router`)
- **Purpose:** Routes USB capture → I2S playback with format conversion
- **Architecture:** Single-threaded, blocking capture/playback loop
- **DSD handling:** Byte-swap (BSWAP32) for correct bit order
- **Buffer strategy:** Large capture buffer (65536 frames) for PI stability
- **Pre-buffering:** Accumulates data before first I2S write (DMA auto-start issue)

### I2S Hardware
- **Device:** `/sys/devices/platform/ffae0000.i2s/`
- **Modes:**
  - Clock: 512x/1024x MCLK, PLL/EXT
  - Channels: 2ch (LR), 8ch (TDM8)
  - Submode: std, lr, plr, 8ch
- **Mode scripts:** `/opt/2_*.sh` (switch DTB, ALSA config, restart services)
- **Config:** `/etc/i2s.conf` (MODE, MCLK, SUBMODE, swap flags)

### Streaming Services
All services in `/etc/rc.pure/S95*`:
- **Spotify:** librespot (nice -15, 320kbps, no normalization)
- **Tidal, Qobuz, Roon:** Proprietary binaries (SquashFS for space)
- **AirPlay:** shairport-sync
- **DLNA:** upmpdcli + dlna_bridge
- **Squeezebox:** squeezeliteR2

## Device Access

**SSH:** Port 222, password `purefox`
```bash
sshpass -p purefox ssh -p 222 root@<IP>
```

**File transfer:** Use `cat | ssh` (NO SCP available)
```bash
cat local_file | sshpass -p purefox ssh -p 222 root@<IP> 'cat > /remote/path'
```

**Flash deployment:**
```bash
# Boot image to MTD partition 3
sshpass -p purefox ssh -p 222 root@<IP> \
  'cat > /tmp/boot.img && flash_erase /dev/mtd3 0 0 && \
   mtd_debug write /dev/mtd3 0 4194304 /tmp/boot.img'
```

## Git Branches

- **MAX_6.X:** LuckFox Pico MAX (MTD flash, current branch)
- **ULTRA_6.X:** LuckFox Pico Ultra (eMMC storage)
- **master:** Legacy/upstream tracking

Use `sync_max_to_ultra.sh` to sync MAX→Ultra (excludes platform-specific files).

## Common Development Tasks

### Modify kernel driver
1. Edit in `/opt/PureFox_2.0/buildroot/output/build/linux-custom/`
2. Test: `cd buildroot && make linux-rebuild && make`
3. Copy changed files to `/opt/linux-rockchip-rk-6.1-rkr6.1_modify/`
4. Generate patch (see Kernel Patch Workflow above)

### Modify custom package (e.g., uac2_router)
1. Edit source in `ext_tree/package/uac2_router/uac2_router.c`
2. Rebuild: `cd buildroot && make uac2_router-rebuild`
3. Binary in `output/target/opt/uac2_router`
4. Deploy: `cat output/target/opt/uac2_router | sshpass -p purefox ssh -p 222 root@<IP> 'cat > /tmp/uac2_router.new && mv /tmp/uac2_router.new /opt/uac2_router && chmod +x /opt/uac2_router'`
5. Restart: `sshpass -p purefox ssh -p 222 root@<IP> 'killall uac2_router; /opt/uac2_router &'`

### Add new package
1. Create `ext_tree/package/<name>/` with `<name>.mk`, `Config.in`, source
2. Add to `ext_tree/Config.in`: `source "../ext_tree/package/<name>/Config.in"`
3. Enable in defconfig: `cd buildroot && make menuconfig` → External options
4. Build: `make <name>`

### Modify web interface
1. Edit PHP files in `ext_tree/board/luckfox/rootfs_overlay/var/www/`
2. Rebuild: `make` (or just copy files directly to device)
3. Web server: lighttpd with PHP-CGI (setuid root for system commands)

## Important Notes

- **Buffer sizes matter:** UAC2 capture buffer must be large (65536) for PI controller stability. Small buffers cause feedback to stick at min/max.
- **DSD byte order:** USB sends DSD oldest-first, ALSA DSD_U32_LE expects MSB-first. Always BSWAP32.
- **I2S DMA quirk:** Auto-starts on first `snd_pcm_writei()`, ignores `start_threshold`. Pre-buffer before writing.
- **UART performance:** 115200 baud = ~4ms per printf. Avoid logging in DSD512 hot paths.
- **PI freeze:** Kernel supports `fb_freeze` via sysfs to hold pitch (not used in current router).
- **Rate-dependent periods:** DSD uses 512-frame periods, high PCM (>192k) uses 1024, others 512.

## Debugging

**Check UAC2 status:**
```bash
cat /sys/class/u_audio/uac_card*/rate
cat /sys/class/u_audio/uac_card*/feedback
cat /proc/asound/card1/pcm0c/sub0/hw_params
```

**Check I2S status:**
```bash
cat /sys/devices/platform/ffae0000.i2s/i2s_channels
dmesg | grep i2s
```

**Monitor uac2_router:**
```bash
killall uac2_router
/opt/uac2_router  # Logs to stdout
```

**Check service status:**
```bash
ps aux | grep -E "librespot|uac2_router|shairport"
ls -l /etc/rc.pure/S95*
```
