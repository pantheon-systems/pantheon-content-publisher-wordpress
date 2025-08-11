#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

# Define files and directories to include in the zip archive.
INCLUDES=(
    "README.txt"
    "admin"
    "app"
    "assets"
    "dist"
    "pantheon-content-publisher.php"
    "vendor"
)

# Determine repository name from the current directory.
REPO=$(basename "$(pwd)")
# Zip file to match plugin name rather then repo name
PLUGIN_NAME="pantheon-content-publisher"
ZIP="${PLUGIN_NAME}.zip"

echo "Starting build process for $REPO..."

echo "Installing PHP dependencies (production)..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "Installing NPM dependencies..."
npm install

echo "Building production assets..."
npm run prod

echo "Creating release artifact: $ZIP..."

echo "Creating temporary directory for release files at: $REPO"
mkdir -p "$REPO"

# Copy all included files/directories to the temporary directory
for item in "${INCLUDES[@]}"; do
    if [[ -e "$item" ]]; then
        echo "Copying $item to temp directory..."
        cp -r "$item" "$REPO/"
    else
        echo "Warning: $item does not exist, skipping..."
    fi
done

echo "Creating zip archive..."

# Remove existing zip file if it exists
if [[ -f "$ZIP" ]]; then
    echo "Removing existing zip file..."
    rm "$ZIP"
fi

zip -r "$ZIP" "$REPO"/*

echo "Cleaning up temporary directory..."
rm -rf "$REPO"

echo "Build complete!"
echo "Artifact created at: $ZIP"

exit 0