#!/bin/bash
set -euo pipefail

PLUGIN_NAME="scriptlogs"
PLUGIN_DISPLAY_NAME="ScriptLogs"
AUTHOR="jo-sobo"
GIT_URL="https://github.com/${AUTHOR}/scriptlogs-unraid-plugin"
SUPPORT_URL="https://forums.unraid.net/topic/192397-plugin-scriptlogs"
PACKAGE_DIR_FINAL="packages"
PACKAGE_DIR_TEMP="package-temp"
CHANGELOG_FILE="CHANGELOG.md"

BASE_VERSION=$(date +'%Y.%m.%d')
LETTER_SUFFIX="${1:-}"
STAGE_INPUT="${2:-}"
STAGE_SUFFIX=""

if [[ ! -f "${CHANGELOG_FILE}" ]]; then
    echo "Error: ${CHANGELOG_FILE} is required."
    exit 1
fi

CHANGELOG_TEXT="$(<"${CHANGELOG_FILE}")"

if [[ -n "$STAGE_INPUT" && "$STAGE_INPUT" != "release" ]]; then
    STAGE_SUFFIX="-${STAGE_INPUT}"
fi

VERSION="${BASE_VERSION}${LETTER_SUFFIX}${STAGE_SUFFIX}"

if [[ "$STAGE_INPUT" == "dev" ]]; then
  BRANCH="dev"
  PLUGIN_URL_STRUCTURE="&gitURL;/raw/&branch;/packages/&name;-&version;.txz"
  CHANGES_TEXT="### ${VERSION}
- Development build from the 'dev' branch. For testing purposes only.

${CHANGELOG_TEXT}"
else
  BRANCH="main"
  PLUGIN_URL_STRUCTURE="&gitURL;/releases/download/&version;/&name;-&version;.txz"
  CHANGES_TEXT="${CHANGELOG_TEXT}"
fi

echo "Starting build for version ${VERSION} on branch ${BRANCH}..."

rm -rf "${PACKAGE_DIR_TEMP}"
# Previous builds in ${PACKAGE_DIR_FINAL} are kept intentionally, not wiped.
mkdir -p "${PACKAGE_DIR_TEMP}"
mkdir -p "${PACKAGE_DIR_FINAL}"

PLUGIN_DEST_PATH="${PACKAGE_DIR_TEMP}/usr/local/emhttp/plugins/${PLUGIN_NAME}"
mkdir -p "${PLUGIN_DEST_PATH}"
cp -R source/. "${PLUGIN_DEST_PATH}/"

find "${PLUGIN_DEST_PATH}" -type d -exec chmod 755 {} \;
find "${PLUGIN_DEST_PATH}" -type f -exec chmod 644 {} \;
find "${PLUGIN_DEST_PATH}" -name "*.page" -exec chmod 755 {} \;

FILENAME="${PLUGIN_NAME}-${VERSION}"
PACKAGE_PATH="${PACKAGE_DIR_FINAL}/${FILENAME}.txz"

echo "Creating package: ${FILENAME}.txz"
COPYFILE_DISABLE=1 tar -C "${PACKAGE_DIR_TEMP}" --uid 0 --gid 0 --numeric-owner -cJf "${PACKAGE_PATH}" usr

if [ ! -f "${PACKAGE_PATH}" ]; then
    echo "Error: Package creation failed!"
    exit 1
fi

if command -v md5sum >/dev/null 2>&1; then
    PACKAGE_MD5="$(md5sum "${PACKAGE_PATH}" | awk '{print $1}')"
elif command -v md5 >/dev/null 2>&1; then
    PACKAGE_MD5="$(md5 -q "${PACKAGE_PATH}")"
else
    echo "Error: md5sum or md5 is required to generate package checksums."
    exit 1
fi

echo "Package created: $(du -h "${PACKAGE_PATH}" | cut -f1)"
echo "Package MD5: ${PACKAGE_MD5}"

echo "Generating ${PLUGIN_NAME}.plg for '${BRANCH}' target..."

cat > "${PLUGIN_NAME}.plg" << EOF
<?xml version='1.0' standalone='yes'?>
<!DOCTYPE PLUGIN [
 <!ENTITY name "${PLUGIN_NAME}">
 <!ENTITY author "${AUTHOR}">
 <!ENTITY version "${VERSION}">
 <!ENTITY branch "${BRANCH}">
 <!ENTITY displayName "${PLUGIN_DISPLAY_NAME}">
 <!ENTITY gitURL "${GIT_URL}">
 <!ENTITY supportURL "${SUPPORT_URL}">
 <!ENTITY pluginURL "${PLUGIN_URL_STRUCTURE}">
 <!ENTITY selfURL "&gitURL;/raw/&branch;/&name;.plg">
]>

<PLUGIN name="&name;" author="&author;" version="&version;" pluginURL="&selfURL;" min="6.9.0" support="&supportURL;">

<CHANGES>
${CHANGES_TEXT}
</CHANGES>

<FILE Name="/boot/config/plugins/&name;/&name;-&version;.txz" Run="upgradepkg --install-new">
<URL>&pluginURL;</URL>
<MD5>${PACKAGE_MD5}</MD5>
</FILE>

<FILE Name="/boot/config/plugins/&name;/&name;.cfg">
  <INLINE>
    REFRESH_ENABLED="1"
    REFRESH_INTERVAL="10"
    ENABLED_SCRIPTS=""
    SHOW_IDLE_LOGS="0"
    LOG_FONT_SIZE="1rem"
    REMOVE_EMPTY_LOG_LINES="1"
  </INLINE>
</FILE>

<FILE Run="/bin/bash">
<INLINE>
chown -R root:root /usr/local/emhttp/plugins/&name;
find -P /usr/local/emhttp/plugins/&name; -type d -exec chmod 755 {} \;
find -P /usr/local/emhttp/plugins/&name; -type f -exec chmod 644 {} \;
find -P /usr/local/emhttp/plugins/&name; -name "*.page" -exec chmod 755 {} \;
find /boot/config/plugins/&name; -maxdepth 1 -type f -name "&name;-*.txz" ! -name "&name;-&version;.txz" -delete 2>/dev/null || true

echo ""
echo "----------------------------------------------------"
echo " &displayName; (&branch; build) has been installed."
echo " Version: &version;"
echo "----------------------------------------------------"
echo ""
</INLINE>
</FILE>

<FILE Run="/bin/bash" Method="remove">
<INLINE>
removepkg &name;-&version;
rm -rf /usr/local/emhttp/plugins/&name;
find /boot/config/plugins/&name; -maxdepth 1 -type f -name "&name;-*.txz" -delete 2>/dev/null || true

echo ""
echo "----------------------------------------------------"
echo " &displayName; has been removed."
echo "----------------------------------------------------"
echo ""
</INLINE>
</FILE>

</PLUGIN>
EOF

rm -rf "${PACKAGE_DIR_TEMP}"

echo ""
echo "Build completed successfully."
echo "Version: ${VERSION}"
echo "Package: ${PACKAGE_PATH}"
echo "PLG file: ${PLUGIN_NAME}.plg"
