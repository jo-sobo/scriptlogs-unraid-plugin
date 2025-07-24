#!/bin/bash

# ScriptLogs Plugin Build Script
# Creates a .txz package for Unraid plugin installation

PLUGIN_NAME="scriptlogs"
VERSION="2025.01.24a"
BUILD_DIR="build"
PACKAGE_NAME="${PLUGIN_NAME}-${VERSION}.txz"

echo "Building ${PLUGIN_NAME} plugin v${VERSION}..."

# Clean and create build directory
rm -rf ${BUILD_DIR}
mkdir -p ${BUILD_DIR}/${PLUGIN_NAME}

# Copy plugin files to build directory
echo "Copying plugin files..."
cp -r source/scriptlogs/* ${BUILD_DIR}/${PLUGIN_NAME}/

# Set proper permissions
echo "Setting file permissions..."
find ${BUILD_DIR}/${PLUGIN_NAME} -type f -name "*.php" -exec chmod 644 {} \;
find ${BUILD_DIR}/${PLUGIN_NAME} -type f -name "*.page" -exec chmod 644 {} \;
find ${BUILD_DIR}/${PLUGIN_NAME} -type f -name "*.js" -exec chmod 644 {} \;
find ${BUILD_DIR}/${PLUGIN_NAME} -type f -name "*.css" -exec chmod 644 {} \;
find ${BUILD_DIR}/${PLUGIN_NAME} -type f -name "*.svg" -exec chmod 644 {} \;
find ${BUILD_DIR}/${PLUGIN_NAME} -type d -exec chmod 755 {} \;

# Create the package (Unraid expects .txz = tar with xz compression)
echo "Creating package..."
cd ${BUILD_DIR}
tar -cJf ../${PACKAGE_NAME} ${PLUGIN_NAME}/
cd ..

# Clean up build directory
rm -rf ${BUILD_DIR}

# Verify package was created
if [ -f ${PACKAGE_NAME} ]; then
    echo "✅ Package created successfully: ${PACKAGE_NAME}"
    echo "📦 Package size: $(du -h ${PACKAGE_NAME} | cut -f1)"
    echo ""
    echo "Next steps:"
    echo "1. Test the package locally if needed"
    echo "2. Create a GitHub release"
    echo "3. Upload ${PACKAGE_NAME} to the release"
    echo "4. Update the .plg file with the correct download URL"
else
    echo "❌ Package creation failed!"
    exit 1
fi