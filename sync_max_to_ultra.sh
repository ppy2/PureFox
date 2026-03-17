#!/bin/bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

SRC_BRANCH="MAX_6.X"
DST_BRANCH="ULTRA_6.X"

echo "=== Syncing $SRC_BRANCH to $DST_BRANCH (preserving Ultra-specific files) ==="

# Check we're on source branch
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$SRC_BRANCH" ]; then
    echo "ERROR: Must be on $SRC_BRANCH branch. Currently on: $CURRENT_BRANCH"
    exit 1
fi

# Check working tree is clean
if ! git diff-index --quiet HEAD --; then
    echo "ERROR: Working tree has uncommitted changes. Commit or stash them first."
    exit 1
fi

# Ultra-specific files: storage type (eMMC vs MTD), DTS, boot, platform scripts
EXCLUDE_PATTERNS=(
    # Sync script itself (MAX only)
    "^sync_max_to_ultra.sh$"

    # Build scripts (different storage layout)
    "^build.sh$"
    "^buildroot/board/luckfox-pico/"
    "^ext_tree/configs/"
    "^ext_tree/external.mk$"

    # Platform-specific DTS (MAX vs Ultra)
    "^ext_tree/board/luckfox/dts_max/"

    # Build hooks (different post-build for MAX vs Ultra)
    "^ext_tree/board/luckfox/scripts/post-build.sh$"
    "^ext_tree/board/luckfox/scripts/post-image"
    "^ext_tree/board/luckfox/scripts/linux-post-build.sh$"

    # U-boot binaries (platform-specific)
    "^ext_tree/board/luckfox/uboot/"

    # Platform-specific rootfs (MTD vs eMMC)
    "^ext_tree/board/luckfox/rootfs_overlay/etc/fstab$"
    "^ext_tree/board/luckfox/rootfs_overlay/etc/fw_env.config$"

    # Platform-specific init scripts (MTD vs eMMC)
    "^ext_tree/board/luckfox/rootfs_overlay/etc/init.d/S00platform$"
    "^ext_tree/board/luckfox/rootfs_overlay/etc/init.d/S20linkmount$"
    "^ext_tree/board/luckfox/rootfs_overlay/etc/init.d/S94ioi2s$"
    "^ext_tree/board/luckfox/config/uboot-env.txt$"

    # Platform-specific helper scripts (DTB switching, update — different for MAX/Ultra)
    "^ext_tree/board/luckfox/rootfs_overlay/opt/2.*\.sh$"
    "^ext_tree/board/luckfox/rootfs_overlay/opt/export.sh$"
    "^ext_tree/board/luckfox/rootfs_overlay/opt/update.sh$"

    # Build output
    "^buildroot/output/"
)

echo "Step 1: Switching to $DST_BRANCH branch..."
git checkout "$DST_BRANCH"

SRC_HEAD=$(git rev-parse "$SRC_BRANCH")
DST_HEAD=$(git rev-parse "$DST_BRANCH")

echo "$SRC_BRANCH HEAD: $SRC_HEAD"
echo "$DST_BRANCH HEAD: $DST_HEAD"

echo "Step 2: Finding files that differ..."
CHANGED_FILES=$(git diff --name-only "$DST_BRANCH" "$SRC_BRANCH")

echo "Step 3: Filtering files (excluding Ultra-specific)..."
FILES_TO_SYNC=""
SKIPPED_COUNT=0
SYNCED_COUNT=0

while IFS= read -r file; do
    [ -z "$file" ] && continue

    SKIP=false
    for pattern in "${EXCLUDE_PATTERNS[@]}"; do
        if echo "$file" | grep -qE "$pattern"; then
            echo "  [SKIP] $file"
            SKIP=true
            SKIPPED_COUNT=$((SKIPPED_COUNT + 1))
            break
        fi
    done

    if [ "$SKIP" = false ]; then
        FILES_TO_SYNC="$FILES_TO_SYNC $file"
        SYNCED_COUNT=$((SYNCED_COUNT + 1))
    fi
done <<< "$CHANGED_FILES"

echo ""
echo "Files to sync: $SYNCED_COUNT"
echo "Files skipped: $SKIPPED_COUNT"
echo ""

if [ -z "$FILES_TO_SYNC" ]; then
    echo "No files to sync!"
    git checkout "$SRC_BRANCH"
    exit 0
fi

echo "Step 4: Syncing files from $SRC_BRANCH..."
for file in $FILES_TO_SYNC; do
    if git cat-file -e "$SRC_BRANCH":"$file" 2>/dev/null; then
        echo "  [SYNC] $file"
        mkdir -p "$(dirname "$file")"
        git checkout "$SRC_BRANCH" -- "$file"
    else
        echo "  [DELETE] $file (removed in $SRC_BRANCH)"
        git rm -f "$file" 2>/dev/null || rm -f "$file"
    fi
done

echo ""
echo "Step 5: Updating branding (MAX → Ultra)..."
INDEX_PHP="ext_tree/board/luckfox/rootfs_overlay/var/www/index.php"
if [ -f "$INDEX_PHP" ]; then
    if grep -q "MAX" "$INDEX_PHP"; then
        sed -i 's/MAX/Ultra/g' "$INDEX_PHP"
        git add "$INDEX_PHP"
        echo "  [UPDATED] $INDEX_PHP (MAX → Ultra)"
    fi
fi

echo ""
echo "Step 6: Reviewing changes..."
git status

echo ""
echo "=== Sync complete! ==="
echo ""
echo "Review the changes with: git diff --cached"
echo "Commit with: git commit -m 'Sync from $SRC_BRANCH'"
echo "Discard with: git checkout $SRC_BRANCH && git checkout $DST_BRANCH -- . && git checkout $SRC_BRANCH"
