#!/bin/sh
# Post-build script to remove duplicate toolchain libraries

# Remove duplicate C++ standard library from /lib (keep only in /usr/lib)
echo "Removing duplicate libstdc++ libraries from /lib..."
rm -f "${TARGET_DIR}/lib/libstdc++.so.6"*
rm -f "${TARGET_DIR}/lib/libstdc++.so"

# Remove duplicate libgcc_s from /lib (keep only in /usr/lib)
echo "Removing duplicate libgcc_s from /lib..."
rm -f "${TARGET_DIR}/lib/libgcc_s.so.1"

# Remove duplicate libatomic from /lib (keep only in /usr/lib)
echo "Removing duplicate libatomic from /lib..."
rm -f "${TARGET_DIR}/lib/libatomic.so.1"*
rm -f "${TARGET_DIR}/lib/libatomic.so"

# Remove duplicate libgfortran from /lib (keep only in /usr/lib)
echo "Removing duplicate libgfortran from /lib..."
rm -f "${TARGET_DIR}/lib/libgfortran.so.5"*
rm -f "${TARGET_DIR}/lib/libgfortran.so"

# Remove duplicate libgomp from /lib (keep only in /usr/lib)
echo "Removing duplicate libgomp from /lib..."
rm -f "${TARGET_DIR}/lib/libgomp.so.1"*
rm -f "${TARGET_DIR}/lib/libgomp.so"
