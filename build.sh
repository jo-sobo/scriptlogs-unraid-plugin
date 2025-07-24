#!/bin/bash

# --- Konfiguration der Versionierung ---
BASE_VERSION=$(date +'%Y.%m.%d')
LETTER_SUFFIX="b" # Nimmt das erste Argument (z.B. "a")
STAGE_INPUT="alpha"   # Nimmt das zweite Argument (z.B. "beta")
STAGE_SUFFIX=""    # Standard ist ein Release ohne Zusatz

if [[ -n "$STAGE_INPUT" && "$STAGE_INPUT" != "release" ]]; then
  STAGE_SUFFIX="-$STAGE_INPUT"
fi

VERSION="${BASE_VERSION}${LETTER_SUFFIX}${STAGE_SUFFIX}"
# --- Ende der Konfiguration ---


# Konfiguration
PLUGIN_NAME="scriptlogs"
AUTHOR="jo-sobo"
PACKAGE_DIR_FINAL="packages"
PACKAGE_DIR_TEMP="package-temp"

# Aufräumen und Verzeichnisse erstellen
rm -rf ${PACKAGE_DIR_TEMP}
rm -rf ${PACKAGE_DIR_FINAL}
mkdir -p ${PACKAGE_DIR_TEMP}
mkdir -p ${PACKAGE_DIR_FINAL}

# Erstelle die komplette Zielstruktur im temporären Ordner
PLUGIN_DEST_PATH="${PACKAGE_DIR_TEMP}/usr/local/emhttp/plugins/${PLUGIN_NAME}"
mkdir -p "${PLUGIN_DEST_PATH}"
cp -R source/* "${PLUGIN_DEST_PATH}/"

# Erstelle das .tar.gz Archiv
FILENAME="${PLUGIN_NAME}-${VERSION}"
tar -C ${PACKAGE_DIR_TEMP} -czvf ${PACKAGE_DIR_FINAL}/$FILENAME.tar.gz usr

# --- KORRIGIERTER TEIL ZUM ERSTELLEN DER .PLG ---
# Definiere den Inhalt der .plg-Datei als Variable. Das ist sicherer.
read -r -d '' PLG_CONTENT << EOM
<?xml version="1.0" standalone="yes"?>
<!DOCTYPE PLUGIN [
<!ENTITY name "${PLUGIN_NAME}">
<!ENTITY author "${AUTHOR}">
<!ENTITY version="${VERSION}">
<!ENTITY pluginURL="https://raw.githubusercontent.com/jo-sobo/scriptlogs-unraid-plugin/master/scriptlogs.plg">
]>
<PLUGIN name="&name;" author="&author;" version="&version;" pluginURL="&pluginURL;" min="6.9.0">
<CHANGES>
</CHANGES>
<FILE Name="/boot/config/plugins/&name;/&name;-&version;.tar.gz" Run="upgradepkg --install-new">
<INLINE>
#remove old package
removepkg &name;-&VER_OLD;.txz
#install new package
install -d -m 0755 /usr/local/emhttp/plugins/&name;
upgradepkg --install-new /boot/config/plugins/&name;/&name;-&version;.tar.gz
</INLINE>
</FILE>
<FILE Name="/boot/config/plugins/&name;/&name;-&version;.tar.gz" Run="remove">
<INLINE>
#remove package
removepkg &name;-&version;.txz
rm -rf /usr/local/emhttp/plugins/&name;
</INLINE>
</FILE>
</PLUGIN>
EOM

# Schreibe den Inhalt der Variable in die .plg-Datei im Root-Verzeichnis
echo "$PLG_CONTENT" > "${PLUGIN_NAME}.plg"
# --- ENDE DER KORREKTUR ---


# Aufräumen
rm -rf ${PACKAGE_DIR_TEMP}

echo "Build erfolgreich abgeschlossen!"
echo "Neue Version: ${VERSION}"
echo "TAR-Datei liegt im Ordner '${PACKAGE_DIR_FINAL}'."
echo "PLG-Datei wurde im Hauptverzeichnis aktualisiert."