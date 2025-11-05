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
    STAGE_SUFFIX="-${STAGE_INPUT}"
fi

VERSION="${BASE_VERSION}${LETTER_SUFFIX}${STAGE_SUFFIX}"

# --- Branch & URL Logic based on 'dev' flag ---
if [[ "$STAGE_INPUT" == "dev" ]]; then
  # Settings for a 'dev' build
  BRANCH="dev"
  PLUGIN_URL_STRUCTURE="&gitURL;/raw/&branch;/packages/&name;-&version;.txz"
  CHANGES_TEXT="- Development build from the 'dev' branch. For testing purposes only."
elif [[ "$STAGE_INPUT" == "unraid720" ]]; then
  # Settings for the Unraid 7.20 migration test branch
  BRANCH="unraid720"
  PLUGIN_URL_STRUCTURE="&gitURL;/raw/&branch;/packages/&name;-&version;.txz"
  CHANGES_TEXT="- Test build from the 'unraid720' branch targeting the Unraid 7.20 webGUI."
else
  # Settings for a 'release' build
  BRANCH="main"
  PLUGIN_URL_STRUCTURE="&gitURL;/releases/download/&version;/&name;-&version;.txz"
  CHANGES_TEXT="- Automated build release."
fi

# --- Build Process ---
echo "Starting build for version ${VERSION} on branch ${BRANCH}..."

# Clean up
rm -rf ${PACKAGE_DIR_TEMP}
rm -rf ${PACKAGE_DIR_FINAL}
mkdir -p ${PACKAGE_DIR_TEMP}
mkdir -p ${PACKAGE_DIR_FINAL}

# Create target structure and copy files
PLUGIN_DEST_PATH="${PACKAGE_DIR_TEMP}/usr/local/emhttp/plugins/${PLUGIN_NAME}"
mkdir -p "${PLUGIN_DEST_PATH}"
cp -R source/* "${PLUGIN_DEST_PATH}/"

# Create branch metadata file
cat > "${PLUGIN_DEST_PATH}/branch.meta" << METAEOF
BRANCH="${BRANCH}"
IS_MAIN_BRANCH=$([[ "$BRANCH" == "main" ]] && echo "1" || echo "0")
METAEOF

# Set correct permissions before packaging
find "${PLUGIN_DEST_PATH}" -type d -exec chmod 755 {} \;
find "${PLUGIN_DEST_PATH}" -type f -exec chmod 644 {} \;
find "${PLUGIN_DEST_PATH}" -name "*.page" -exec chmod 755 {} \;

# Create .txz archive
FILENAME="${PLUGIN_NAME}-${VERSION}"
PACKAGE_PATH="${PACKAGE_DIR_FINAL}/${FILENAME}.txz"

echo "Creating package: ${FILENAME}.txz"
tar -C ${PACKAGE_DIR_TEMP} -cJf "${PACKAGE_PATH}" usr

if [ ! -f "${PACKAGE_PATH}" ]; then
    echo "❌ Error: Package creation failed!"
    exit 1
fi

echo "✅ Package created: $(du -h ${PACKAGE_PATH} | cut -f1)"

# --- Create .PLG file ---
echo "Generating ${PLUGIN_NAME}.plg for '${BRANCH}' target..."

cat > "${PLUGIN_NAME}.plg" << EOF
<?xml version='1.0' standalone='yes'?>
<!DOCTYPE PLUGIN [
 <!ENTITY name "${PLUGIN_NAME}">
 <!ENTITY author "${AUTHOR}">
 <!ENTITY version "${VERSION}">
 <!ENTITY branch "${BRANCH}">
 <!ENTITY gitURL "${GIT_URL}">
 <!ENTITY pluginURL "${PLUGIN_URL_STRUCTURE}">
 <!ENTITY selfURL "&gitURL;/raw/&branch;/&name;.plg">
]>

<PLUGIN name="&name;" author="&author;" version="&version;" pluginURL="&selfURL;" min="6.9.0" support="&gitURL;/issues">

<CHANGES>
### ${VERSION}
${CHANGES_TEXT}
</CHANGES>

<FILE Name="/boot/config/plugins/&name;/&name;-&version;.txz" Run="upgradepkg --install-new">
<URL>&pluginURL;</URL>
</FILE>

<FILE Name="/boot/config/plugins/&name;/&name;.cfg">
  <INLINE>
    REFRESH_ENABLED="1"
    REFRESH_INTERVAL="10"
    ENABLED_SCRIPTS=""
    SHOW_IDLE_LOGS="0"
    VERSION_OVERRIDE="auto"
  </INLINE>
</FILE>

<FILE Run="/bin/bash">
<INLINE>
# Fix ownership and permissions after unpacking on the Unraid server
chown -R root:root /usr/local/emhttp/plugins/&name;
chmod -R 755 /usr/local/emhttp/plugins/&name;
find /usr/local/emhttp/plugins/&name; -type f -exec chmod 644 {} \;
find /usr/local/emhttp/plugins/&name; -name "*.page" -exec chmod 755 {} \;

echo ""
echo "----------------------------------------------------"
echo " &name; (&branch; build) has been installed."
echo " Version: &version;"
echo "----------------------------------------------------"
echo ""
</INLINE>
</FILE>

<FILE Run="/bin/bash" Method="remove">
<INLINE>
removepkg &name;-&version;
rm -rf /usr/local/emhttp/plugins/&name;
rm -rf /boot/config/plugins/&name;

echo ""
echo "----------------------------------------------------"
echo " &name; has been removed."
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
echo "📁 Package: ${PACKAGE_PATH}"
echo "📄 PLG file: ${PLUGIN_NAME}.plg"