#!/bin/bash

# --- Configuration ---
PLUGIN_NAME="scriptlogs"
AUTHOR="jo-sobo"
GIT_URL="https://github.com/${AUTHOR}/scriptlogs-unraid-plugin"
PACKAGE_DIR_FINAL="packages"
PACKAGE_DIR_TEMP="package-temp"

# --- Versioning ---
BASE_VERSION=$(date +'%Y.%m.%d')
LETTER_SUFFIX="$1"
STAGE_INPUT="$2"
STAGE_SUFFIX=""

if [[ -n "$STAGE_INPUT" && "$STAGE_INPUT" != "release" ]]; then
  STAGE_SUFFIX="-$STAGE_INPUT"
fi

VERSION="${BASE_VERSION}${LETTER_SUFFIX}${STAGE_SUFFIX}"

# --- Build Process ---
echo "Starting build for version ${VERSION}..."

# Clean up
rm -rf ${PACKAGE_DIR_TEMP}
rm -rf ${PACKAGE_DIR_FINAL}
mkdir -p ${PACKAGE_DIR_TEMP}
mkdir -p ${PACKAGE_DIR_FINAL}

# Create target structure and copy files
PLUGIN_DEST_PATH="${PACKAGE_DIR_TEMP}/usr/local/emhttp/plugins/${PLUGIN_NAME}"
mkdir -p "${PLUGIN_DEST_PATH}"
cp -R source/* "${PLUGIN_DEST_PATH}/"

# Set proper permissions
echo "Setting proper permissions..."
find "${PLUGIN_DEST_PATH}" -type f -name "*.php" -exec chmod 644 {} \;
find "${PLUGIN_DEST_PATH}" -type f -name "*.page" -exec chmod 644 {} \;
find "${PLUGIN_DEST_PATH}" -type f -name "*.js" -exec chmod 644 {} \;
find "${PLUGIN_DEST_PATH}" -type f -name "*.css" -exec chmod 644 {} \;
find "${PLUGIN_DEST_PATH}" -type f -name "*.svg" -exec chmod 644 {} \;
find "${PLUGIN_DEST_PATH}" -type d -exec chmod 755 {} \;

# Create .txz archive
FILENAME="${PLUGIN_NAME}-${VERSION}"
echo "Creating package: ${FILENAME}.txz"
tar -C ${PACKAGE_DIR_TEMP} -cJf ${PACKAGE_DIR_FINAL}/${FILENAME}.txz usr

# Verify package creation
if [ ! -f "${PACKAGE_DIR_FINAL}/${FILENAME}.txz" ]; then
    echo "❌ Error: Package creation failed!"
    exit 1
fi

echo "✅ Package created: $(du -h ${PACKAGE_DIR_FINAL}/${FILENAME}.txz | cut -f1)"

# --- Create .PLG file ---
echo "Generating .plg file..."

cat > "${PLUGIN_NAME}.plg" << EOF
<?xml version='1.0' standalone='yes'?>
<!DOCTYPE PLUGIN [
  <!ENTITY name      "${PLUGIN_NAME}">
  <!ENTITY author    "${AUTHOR}">
  <!ENTITY version   "${VERSION}">
  <!ENTITY gitURL    "${GIT_URL}">
  <!ENTITY pluginURL "&gitURL;/releases/download/&version;/&name;-&version;.txz">
]>

<PLUGIN name="&name;" author="&author;" version="&version;" pluginURL="&pluginURL;" min="6.9.0" support="&gitURL;/issues">

<CHANGES>
##&name;

###${VERSION}
- Automated build release
- Dashboard integration for user script logs
- Tab-based interface for running scripts
- Real-time log monitoring
</CHANGES>

<!--
This plugin provides a dashboard widget to monitor running user scripts and display their logs in real-time.
-->

<FILE Run="/bin/bash">
<INLINE>
# Remove old source files
rm -f \$(ls /boot/config/plugins/&name;/&name;*.txz 2>/dev/null|head -n1)
echo ""
echo "----------------------------------------------------"
echo " &name; has been installed."
echo " Copyright 2025, &author;"
echo " Version: &version;"
echo "----------------------------------------------------"
echo ""
</INLINE>
</FILE>

<!--
The 'source' file.
-->
<FILE Name="/boot/config/plugins/&name;/&name;-&version;.txz" Run="upgradepkg --install-new">
<URL>&pluginURL;</URL>
</FILE>

<!--
The 'post-install' script
-->
<FILE Run="/bin/bash">
<INLINE>
echo ""
echo "----------------------------------------------------"
echo " &name; has been installed."
echo " This plugin requires Unraid version 6.9.0 or higher"
echo " Copyright 2025, &author;"
echo " Version: &version;"
echo "----------------------------------------------------"
echo ""
</INLINE>
</FILE>

<!--
The 'remove' script.
-->
<FILE Run="/bin/bash" Method="remove">
<INLINE>
removepkg &name;-&version;
rm -rf /usr/local/emhttp/plugins/&name;
rm -rf /boot/config/plugins/&name;

echo ""
echo "----------------------------------------------------"
echo " &name; has been removed."
echo " Copyright 2025, &author;"
echo " Version: &version;"
echo "----------------------------------------------------"
echo ""
</INLINE>
</FILE>

</PLUGIN>
EOF

# Clean up temp directory
rm -rf ${PACKAGE_DIR_TEMP}

echo ""
echo "🎉 Build completed successfully!"
echo "📦 Version: ${VERSION}"
echo "📁 Package: ${PACKAGE_DIR_FINAL}/${FILENAME}.txz"
echo "📄 PLG file: ${PLUGIN_NAME}.plg"
