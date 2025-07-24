#!/bin/bash

# --- Konfiguration ---
PLUGIN_NAME="scriptlogs"
AUTHOR="jo-sobo"
GIT_URL="https://github.com/${AUTHOR}/${PLUGIN_NAME}"
PACKAGE_DIR_FINAL="packages"
PACKAGE_DIR_TEMP="package-temp"

# --- Versionierung ---
BASE_VERSION=$(date +'%Y.%m.%d')
LETTER_SUFFIX="b"
STAGE_INPUT="alpha"
STAGE_SUFFIX=""
if [[ -n "$STAGE_INPUT" && "$STAGE_INPUT" != "release" ]]; then
  STAGE_SUFFIX="-$STAGE_INPUT"
fi
VERSION="${BASE_VERSION}${LETTER_SUFFIX}${STAGE_SUFFIX}"

# --- Build-Prozess ---
echo "Starte Build für Version ${VERSION}..."

# Aufräumen
rm -rf ${PACKAGE_DIR_TEMP}
rm -rf ${PACKAGE_DIR_FINAL}
mkdir -p ${PACKAGE_DIR_TEMP}
mkdir -p ${PACKAGE_DIR_FINAL}

# Erstelle die Zielstruktur und kopiere Dateien
PLUGIN_DEST_PATH="${PACKAGE_DIR_TEMP}/usr/local/emhttp/plugins/${PLUGIN_NAME}"
mkdir -p "${PLUGIN_DEST_PATH}"
cp -R source/* "${PLUGIN_DEST_PATH}/"

# Erstelle das .tar.gz Archiv (im Beispiel war es .txz, aber .tar.gz ist moderner und funktioniert)
FILENAME="${PLUGIN_NAME}-${VERSION}"
tar -C ${PACKAGE_DIR_TEMP} -czvf ${PACKAGE_DIR_FINAL}/$FILENAME.tar.gz usr

# --- .PLG-Datei erstellen (im korrekten, robusten Format) ---
read -r -d '' PLG_CONTENT << EOM
<?xml version="1.0" standalone="yes"?>
<!DOCTYPE PLUGIN [
<!ENTITY name "${PLUGIN_NAME}">
<!ENTITY author "${AUTHOR}">
<!ENTITY version="${VERSION}">
<!ENTITY gitURL="${GIT_URL}">
<!ENTITY pluginURL="&gitURL;/releases/download/&version;/&name;-&version;.tar.gz">
]>
<PLUGIN name="&name;" author="&author;" version="&version;" min="6.9.0" support="&gitURL;/issues">
<CHANGES>
###&version;
- Release
</CHANGES>
<FILE Name="/boot/config/plugins/&name;/&name;-&version;.tar.gz" Run="upgradepkg --install-new">
    <URL>&pluginURL;</URL>
</FILE>
<FILE Run="/bin/bash" Method="remove">
<INLINE>
removepkg &name;-&version;
rm -rf /usr/local/emhttp/plugins/&name;
rm -rf /boot/config/plugins/&name;
echo "&name; wurde entfernt."
</INLINE>
</FILE>
</PLUGIN>
EOM

echo "$PLG_CONTENT" > "${PLUGIN_NAME}.plg"

# Aufräumen
rm -rf ${PACKAGE_DIR_TEMP}

echo "Build erfolgreich abgeschlossen!"
echo "Neue Version: ${VERSION}"
echo "TAR-Datei: ${PACKAGE_DIR_FINAL}/${FILENAME}.tar.gz"
echo "PLG-Datei wurde im Hauptverzeichnis aktualisiert."