#!/bin/bash

echo "Creating deployment package..."

# Create a build directory
rm -rf ./build
mkdir build

# Copy all necessary files
cp -r app build/
cp -r bootstrap build/
cp -r config build/
cp -r database build/
cp -r public build/
cp -r resources build/
cp -r routes build/
cp -r storage build/
cp -r vendor build/
cp .env.production build/.env
cp artisan build/
cp composer.json build/
cp composer.lock build/
cp package.json build/
cp vite.config.js build/

# Create storage directory structure
mkdir -p build/storage/framework/sessions
mkdir -p build/storage/framework/views
mkdir -p build/storage/framework/cache
mkdir -p build/storage/logs

# Set permissions
chmod -R 755 build
chmod -R 775 build/storage
chmod -R 775 build/bootstrap/cache

# Create zip file
cd build && zip -r ../microbelts_deploy.zip . && cd ..
rm -rf build

echo "Deployment package created: microbelts_deploy.zip"